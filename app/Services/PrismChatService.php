<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Tools\DataAccessTool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class PrismChatService
{
    /**
     * Send a message to the AI and update the conversation with the response.
     *
     * @param  string  $content
     */
    public function sendMessage(
        Conversation $conversation,
        string $userMessageContent
    ): ChatMessage {
        // Create user message
        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $userMessageContent,
        ]);
        $conversation->updateLastActivity();

        try {
            $messages = $this->buildMessageHistory($conversation);
            [$provider, $model] = $this->mapModelToProviderAndModel(
                $conversation->model
            );
            $dataAccessTool = new DataAccessTool;
            $maxSteps = 5;
            $providerSupportsTools = in_array(strtolower($provider), [
                'openai',
                'anthropic',
                'gemini',
            ]);
            $useTools = $providerSupportsTools;
            // Check if we should use streaming (only for OpenAI)
            $useStreaming = strtolower($provider) === 'openai';

            // Create assistant message placeholder
            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => '',
                'user_id' => null,
                'is_streaming' => $useStreaming && $useTools, // Indicate streaming only if actually streaming
            ]);
            $prismRequest = Prism::text()
                ->using($provider, $model)
                ->withMessages($messages);

            if ($useTools) {
                $prismRequest = $prismRequest
                    ->withTools([$dataAccessTool])
                    ->withMaxSteps($maxSteps);
            }
            if ($useStreaming && $useTools) {
                // Use streaming WITH tools
                $stream = $prismRequest->asStream();
                $fullResponseText = '';

                foreach ($stream as $chunk) {
                    $fullResponseText .= $chunk->text;
                    // Update message content progressively
                    $assistantMessage->update(['content' => $fullResponseText]);

                    // Log tool interactions during streaming (optional debugging)
                    if ($chunk->toolCalls) {
                        Log::info('Tool Call Chunk:', [
                            'calls' => $chunk->toolCalls,
                        ]);
                    }
                    if ($chunk->toolResults) {
                        Log::info('Tool Result Chunk:', [
                            'results' => $chunk->toolResults,
                        ]);
                    }

                    // Flush output buffer if running in a context that needs it (like raw PHP script)
                    // In Laravel streaming responses, this might not be necessary depending on setup.
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                // Mark streaming as complete
                $assistantMessage->is_streaming = false;
                $assistantMessage->save(); // Save final content
            } else {
                // Use regular completion (potentially with tools if $useTools is true)
                $response = $prismRequest->generate(); // Use generate() instead of asText() to access tool info

                // Update message with complete response
                $assistantMessage->content = $response->text;
                $assistantMessage->is_streaming = false; // Ensure streaming flag is false
                $assistantMessage->save();

                // Log tool interactions after generation (optional debugging)
                if ($useTools) {
                    if ($response->toolCalls) {
                        Log::info('Tool Calls (Generate):', [
                            'calls' => $response->toolCalls,
                        ]);
                    }
                    if ($response->toolResults) {
                        Log::info('Tool Results (Generate):', [
                            'results' => $response->toolResults,
                        ]);
                    }
                }
            }

            // Update conversation last activity
            $this->updateConversationActivity($conversation);

            return $assistantMessage;
        } catch (\Exception $e) {
            // ... (Keep existing error handling)
            Log::error('AI response error: '.$e->getMessage(), [
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Create error message if not already created
            if (! isset($assistantMessage) || ! $assistantMessage->exists) {
                $assistantMessage = ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => 'Sorry, I encountered an error before I could respond.',
                    'user_id' => null,
                    'is_streaming' => false,
                ]);
            } else {
                // Update existing placeholder with error
                $assistantMessage->update([
                    'content' => $assistantMessage->content.
                        "\n\n[Error: ".
                        $e->getMessage().
                        ']',
                    'is_streaming' => false,
                ]);
            }
            $this->updateConversationActivity($conversation);

            return $assistantMessage; // Return the message (even if it contains an error)
        }
    }

    /**
     * Sends a specific message history to the AI and saves the response.
     * Used for regeneration after edits or standard regeneration.
     *
     * @param  Collection  $history  A collection of ChatMessage objects or arrays representing the history.
     * @return ChatMessage The newly created assistant message.
     */
    public function generateResponseFromHistory(
        Conversation $conversation,
        Collection $history
    ): ChatMessage {
        // 1. Format the history collection (Keep this part)
        $formattedHistory = $history
            ->map(function ($msg) {
                return ['role' => $msg->role, 'content' => $msg->content];
            })
            ->toArray();

        // Add system prompt / style (Keep this part)
        $systemPrompt = $this->getSystemPromptForStyle($conversation->style);
        if ($systemPrompt) {
            array_unshift($formattedHistory, [
                'role' => 'system',
                'content' => $systemPrompt,
            ]);
        }

        // Create a temporary streaming message placeholder (Keep this part)
        $assistantMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '', // Start empty
            'is_streaming' => true, // Indicate streaming
            'user_id' => null, // Assistant messages don't have a user_id
        ]);
        $conversation->updateLastActivity(); // Update timestamp

        // --- START REPLACEMENT ---
        try {
            // Map model identifier to provider and model name
            [$provider, $model] = $this->mapModelToProviderAndModel(
                $conversation->model
            );

            // Convert the formatted history array back to Prism Message objects
            $prismMessages = collect($formattedHistory)
                ->map(function ($msg) {
                    return match ($msg['role']) {
                        'system' => new SystemMessage($msg['content']),
                        'user' => new UserMessage($msg['content']),
                        'assistant' => new AssistantMessage($msg['content']),
                        default => null, // Handle potential unknown roles if necessary
                    };
                })
                ->filter()
                ->values()
                ->all(); // Remove nulls and re-index

            // Check if the provider supports streaming (like OpenAI)
            $useStreaming = strtolower($provider) === 'openai';
            // Decide if tools should be used during regeneration (optional, keeping it simple for now)
            $useTools = false; // Set to true if you want tools active here
            $dataAccessTool = null;
            if ($useTools) {
                // Ensure provider supports tools
                $providerSupportsTools = in_array(strtolower($provider), [
                    'openai',
                    'anthropic',
                    'gemini',
                ]);
                $useTools = $providerSupportsTools;
                if ($useTools) {
                    $dataAccessTool = new DataAccessTool;
                }
            }

            $prismRequest = Prism::text()
                ->using($provider, $model)
                ->withMessages($prismMessages);

            // Add tools if needed for regeneration
            if ($useTools && $dataAccessTool) {
                $prismRequest = $prismRequest
                    ->withTools([$dataAccessTool])
                    ->withMaxSteps(5); // Or your desired max steps
            }

            if ($useStreaming) {
                // Use streaming if supported (adjust condition if other providers add streaming)
                // Ensure the placeholder reflects streaming status correctly
                $assistantMessage->update(['is_streaming' => true]);

                $stream = $prismRequest->asStream();
                $fullResponse = '';

                foreach ($stream as $responseChunk) {
                    $contentChunk = $responseChunk->text; // Assuming text chunk property
                    if (! is_null($contentChunk)) {
                        $fullResponse .= $contentChunk;
                        // Update the message content incrementally
                        $assistantMessage->update(['content' => $fullResponse]);
                        // Optional: Broadcast update for real-time UI
                        // broadcast(new ChatMessageUpdated($assistantMessage))->toOthers();
                        usleep(50000); // Simulate delay if not using websockets
                    }
                    // Log tool info if needed during stream
                    if (
                        $useTools &&
                        ($responseChunk->toolCalls ||
                            $responseChunk->toolResults)
                    ) {
                        Log::info('Regen Tool Chunk:', [
                            'calls' => $responseChunk->toolCalls,
                            'results' => $responseChunk->toolResults,
                        ]);
                    }
                }
                // Finalize the message
                $assistantMessage->update([
                    'content' => $fullResponse,
                    'is_streaming' => false, // Mark streaming as complete
                ]);
            } else {
                // Use non-streaming generation
                // Ensure the placeholder reflects streaming status correctly
                $assistantMessage->update(['is_streaming' => false]); // Not streaming

                $response = $prismRequest->generate(); // Use generate()
                $assistantMessage->update([
                    'content' => $response->text, // Get text content
                    'is_streaming' => false,
                ]);
                // Log tool info if needed after generation
                if (
                    $useTools &&
                    ($response->toolCalls || $response->toolResults)
                ) {
                    Log::info('Regen Tool Generate:', [
                        'calls' => $response->toolCalls,
                        'results' => $response->toolResults,
                    ]);
                }
            }

            return $assistantMessage;
        } catch (\Exception $e) {
            // Handle API error, update the placeholder message with error info
            $errorMessage =
                'Sorry, I encountered an error while generating the response: '.
                $e->getMessage();
            $assistantMessage->update([
                'content' => $errorMessage,
                'is_streaming' => false,
            ]);
            Log::error('AI Generation Error (History): '.$e->getMessage(), [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Include trace for debugging
            ]);

            // Decide if you want to re-throw the exception or just return the error message
            // throw $e; // Uncomment if you want the Livewire component to catch it via handleError
            return $assistantMessage; // Return the message containing the error
        }
        // --- END REPLACEMENT ---
    }

    /**
     * Build the message history for a conversation.
     * NOTE: Consider if the extensive context added here is still needed
     * now that the AI can *fetch* data via the tool. You might simplify this.
     * For now, we leave it as is.
     */
    private function buildMessageHistory(Conversation $conversation): array
    {
        // ... (Existing buildMessageHistory logic remains unchanged for now)
        $messages = [];

        // Add user context information
        $user = Auth::user();
        $team = $user->currentTeam;

        // Reduce initial context slightly, let the tool provide details on demand
        $userContext = "## User & Team Context\n";
        $userContext .= "- Current User: {$user->name}\n";
        if ($team) {
            $userContext .= "- Current Team: {$team->name}\n";
            // Advise AI to use the tool for specifics
            $userContext .=
                "- Use the 'data_access' tool to get specific details about students, team members, activities, grading, etc.\n";
        }

        // Add context if available
        if ($conversation->context) {
            $messages[] = new SystemMessage(
                $conversation->context."\n\n".$userContext
            );
        } else {
            // Add default system prompt based on conversation style with user context
            $messages[] = new SystemMessage(
                $this->getSystemPromptForStyle($conversation->style).
                    "\n\n".
                    $userContext
            );
        }

        // Get the last messages (Keep this part)
        $chatMessages = $conversation
            ->messages()
            ->orderBy('created_at', 'desc')
            ->take(10) // Limit history size
            ->get()
            ->reverse();

        // Add the messages to the history
        foreach ($chatMessages as $message) {
            // Skip empty/placeholder messages that might exist from previous errors/streaming issues
            if (
                empty(trim($message->content)) &&
                $message->role === 'assistant'
            ) {
                continue;
            }

            if ($message->role === 'user') {
                $messages[] = new UserMessage($message->content);
            } elseif ($message->role === 'assistant') {
                // Check for tool calls/results if Prism stored them previously (future enhancement)
                // For now, just add the text content
                $messages[] = new AssistantMessage($message->content);
            }
            // NOTE: Need to eventually handle ToolCallMessage and ToolResultMessage if
            // Prism persists these or if you want to reconstruct full tool history manually.
            // For now, we rely on Prism handling the history implicitly within the single request.
        }

        return $messages;
    }

    /**
     * Create a new conversation.
     */
    public function createConversation(
        string $title,
        string $model = 'gpt-4o',
        string $style = 'default',
        ?string $context = null
    ): Conversation {
        return Conversation::create([
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam?->id,
            'title' => $title,
            'model' => $model,
            'style' => $style,
            'context' => $context,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Get available AI models.
     */
    public function getAvailableModels(): array
    {
        return PrismServer::prisms()->pluck('name')->toArray();
    }

    /**
     * Get available chat styles.
     */
    public function getAvailableStyles(): array
    {
        return [
            'default' => 'Default',
            'creative' => 'Creative',
            'precise' => 'Precise',
            'balanced' => 'Balanced',
        ];
    }

    /**
     * Map a model identifier to a provider and model name.
     */
    private function mapModelToProviderAndModel(string $modelIdentifier): array
    {
        // Get all registered prisms
        $prisms = PrismServer::prisms();

        // Find the prism with the matching name
        $prism = $prisms->firstWhere('name', $modelIdentifier);

        if ($prism) {
            // Extract provider and model from the prism configuration
            $provider = $prism['provider'] ?? 'openai'; // Default to openai if not specified
            $model = $prism['model'] ?? 'gpt-4o'; // Default model

            return [$provider, $model];
        }

        // Fallback mappings (Keep this for models not explicitly registered via PrismServer)
        $providerMap = [
            'GPT-4o' => ['openai', 'gpt-4o'],
            'GPT-4 Turbo' => ['openai', 'gpt-4-turbo'],
            'GPT-3.5 Turbo' => ['openai', 'gpt-3.5-turbo'],
            'Gemini Pro' => ['gemini', 'gemini-pro'],
            'Gemini 1.5 Pro' => ['gemini', 'gemini-1.5-pro'],
            'Claude 3 Opus' => ['anthropic', 'claude-3-opus-20240229'], // Use full model names
            'Claude 3 Sonnet' => ['anthropic', 'claude-3-sonnet-20240229'],
            'Claude 3 Haiku' => ['anthropic', 'claude-3-haiku-20240307'],
            'Gemini 1.5 Flash' => ['gemini', 'gemini-1.5-flash'], // Ensure these match registered names if used
            // "Gemini 2.0 Flash" => ["gemini", "gemini-2.0-flash"], // Assuming this exists
            'GPT-4o Mini' => ['openai', 'gpt-4o-mini'], // Ensure this matches registered name
        ];

        // Handle potential case mismatch or slightly different naming if needed
        foreach ($providerMap as $name => $details) {
            if (strcasecmp($modelIdentifier, $name) == 0) {
                return $details;
            }
        }

        Log::warning(
            'Model identifier not found in registered prisms or fallback map. Defaulting to OpenAI GPT-4o.',
            ['identifier' => $modelIdentifier]
        );

        return ['openai', 'gpt-4o']; // Default fallback
    }

    private function getSystemPromptForStyle(string $style): string
    {
        $basePrompts = [
            'default' => 'You are a helpful Teacher Assistant. Provide clear and concise responses.',
            'creative' => 'You are a creative Teacher Assistant. Think outside the box and provide imaginative responses.',
            'precise' => 'You are a precise Teacher Assistant. Focus on accuracy and factual information. Be concise and to the point.',
            'balanced' => 'You are a balanced Teacher Assistant. Provide comprehensive yet accessible responses that balance detail with clarity.',
        ];

        $toolInstructions =
            "\nYou have access to a 'data_access' tool. Use it ONLY when the user asks specific questions about their data (students, activities, grades, schedule, team info). Ask clarifying questions if the user's request is ambiguous before using the tool. State that you are retrieving data when you use the tool. Format lists or tables clearly if returning multiple items.";

        return ($basePrompts[$style] ?? $basePrompts['default']).
            $toolInstructions;
    }

    /**
     * Apply a quick action to a message.
     *
     * @param  string  $actionPrompt  The prompt for the quick action
     * @param  string  $content  The content to apply the action to
     * @return string The result of applying the action
     */
    public function applyQuickAction(
        string $actionPrompt,
        string $content
    ): string {
        // Ensure quick actions don't accidentally trigger the data tool unless intended
        // This implementation assumes quick actions are purely text manipulation
        $fullPrompt = $actionPrompt."\n\n---\n\n".$content;

        // Use a capable model for these tasks, maybe separate from the main chat model if needed
        // Consider using a model registered via PrismServer for consistency
        [$provider, $model] = $this->mapModelToProviderAndModel('GPT-4o Mini'); // Example: Use 4o-mini

        try {
            $response = Prism::text()
                ->using($provider, $model)
                ->withPrompt($fullPrompt)
                ->usingTemperature(0.5) // Lower temp for more predictable actions
                ->withMaxTokens(1000) // Adjust as needed
                ->generate(); // Use generate to get the result object

            return $response->text;
        } catch (\Exception $e) {
            Log::error('Quick action error: '.$e->getMessage(), [
                'actionPrompt' => $actionPrompt,
                'error' => $e->getMessage(),
            ]);

            return 'Sorry, I encountered an error while trying to apply the action: '.
                $e->getMessage();
        }
    }

    /**
     * Update the conversation's last activity timestamp.
     */
    private function updateConversationActivity(
        Conversation $conversation
    ): void {
        $conversation->update([
            'last_activity_at' => now(),
        ]);
    }
}
