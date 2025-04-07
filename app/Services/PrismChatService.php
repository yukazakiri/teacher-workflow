<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Tools\ActivityTool;
use App\Tools\ClassResourceTool;
use App\Tools\ExamTool;
use App\Tools\ScheduleTool;
use App\Tools\StudentTool;
use App\Tools\TeamInfoTool;
use App\Services\GradingService;
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
        string $userMessageContent // Argument kept for signature compatibility, but not used for creation
    ): ChatMessage {
        // --- REMOVED: User message creation is now done in Chat.php before calling this service --- 
        // $userMessage = ChatMessage::create([
        //     'conversation_id' => $conversation->id,
        //     'user_id' => Auth::id(),
        //     'role' => 'user',
        //     'content' => $userMessageContent,
        // ]);
        // $conversation->updateLastActivity(); // Also done in Chat.php

        try {
            $messages = $this->buildMessageHistory($conversation);
            [$provider, $model] = $this->mapModelToProviderAndModel(
                $conversation->model
            );

            // Instantiate all available tools
            $tools = $this->getAvailableTools();

            $maxSteps = 5;
            $providerSupportsTools = in_array(strtolower($provider), [
                'openai',
                'anthropic',
                'gemini',
            ]);
            $useTools = $providerSupportsTools && !empty($tools);
            $useStreaming = strtolower($provider) === 'openai'; // Adjust if other providers support streaming

            // Create assistant message placeholder
            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => '',
                'user_id' => null,
                // Indicate streaming only if supported AND tools are used (as streaming often affects tool UX)
                'is_streaming' => $useStreaming && $useTools,
            ]);

            $prismRequest = Prism::text()
                ->using($provider, $model)
                ->withMessages($messages);

            if ($useTools) {
                $prismRequest = $prismRequest
                    ->withTools($tools) // Pass array of tool instances
                    ->withMaxSteps($maxSteps);
            }

            if ($useStreaming && $useTools) {
                // Use streaming WITH tools
                $stream = $prismRequest->asStream();
                $fullResponseText = '';

                foreach ($stream as $chunk) {
                    $fullResponseText .= $chunk->text;
                    $assistantMessage->update(['content' => $fullResponseText]);

                    if ($chunk->toolCalls) {
                        Log::info('Tool Call Chunk:', ['calls' => $chunk->toolCalls]);
                    }
                    if ($chunk->toolResults) {
                        Log::info('Tool Result Chunk:', ['results' => $chunk->toolResults]);
                    }

                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
                $assistantMessage->is_streaming = false;
                $assistantMessage->save();
            } else {
                // Use regular completion (potentially with tools)
                $response = $prismRequest->generate();

                $assistantMessage->content = $response->text;
                $assistantMessage->is_streaming = false;
                $assistantMessage->save();

                if ($useTools) {
                    if ($response->toolCalls) {
                        Log::info('Tool Calls (Generate):', ['calls' => $response->toolCalls]);
                    }
                    if ($response->toolResults) {
                        Log::info('Tool Results (Generate):', ['results' => $response->toolResults]);
                    }
                }
            }

            $this->updateConversationActivity($conversation);
            return $assistantMessage;

        } catch (\Exception $e) {
            Log::error('AI response error: '.$e->getMessage(), [
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (! isset($assistantMessage) || ! $assistantMessage->exists) {
                $assistantMessage = ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => 'Sorry, I encountered an error before I could respond.',
                    'user_id' => null,
                    'is_streaming' => false,
                ]);
            } else {
                $assistantMessage->update([
                    'content' => $assistantMessage->content . "\n\n[Error: " . $e->getMessage() . ']',
                    'is_streaming' => false,
                ]);
            }
            $this->updateConversationActivity($conversation);
            return $assistantMessage;
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
        $formattedHistory = $history
            ->map(fn ($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->toArray();

        $systemPrompt = $this->getSystemPromptForStyle($conversation->style);
        if ($systemPrompt) {
            array_unshift($formattedHistory, ['role' => 'system', 'content' => $systemPrompt]);
        }

        $assistantMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '', // Start empty
            'is_streaming' => true, // Assume streaming initially
            'user_id' => null,
        ]);
        $conversation->updateLastActivity();

        try {
            [$provider, $model] = $this->mapModelToProviderAndModel($conversation->model);

            $prismMessages = collect($formattedHistory)
                ->map(function ($msg) {
                    return match ($msg['role']) {
                        'system' => new SystemMessage($msg['content']),
                        'user' => new UserMessage($msg['content']),
                        'assistant' => new AssistantMessage($msg['content']),
                        default => null,
                    };
                })
                ->filter()
                ->values()
                ->all();

            // Instantiate tools for regeneration as well
            $tools = $this->getAvailableTools();
            $providerSupportsTools = in_array(strtolower($provider), ['openai', 'anthropic', 'gemini']);
            $useTools = $providerSupportsTools && !empty($tools);
            $useStreaming = strtolower($provider) === 'openai'; // Check streaming support

            $assistantMessage->update(['is_streaming' => $useStreaming && $useTools]); // Update streaming status

            $prismRequest = Prism::text()
                ->using($provider, $model)
                ->withMessages($prismMessages);

            if ($useTools) {
                $prismRequest = $prismRequest
                    ->withTools($tools)
                    ->withMaxSteps(5);
            }

            if ($useStreaming && $useTools) {
                $stream = $prismRequest->asStream();
                $fullResponse = '';

                foreach ($stream as $responseChunk) {
                    $contentChunk = $responseChunk->text;
                    if (! is_null($contentChunk)) {
                        $fullResponse .= $contentChunk;
                        $assistantMessage->update(['content' => $fullResponse]);
                        usleep(50000); // Optional delay
                    }
                    if ($useTools && ($responseChunk->toolCalls || $responseChunk->toolResults)) {
                        Log::info('Regen Tool Chunk:', ['calls' => $responseChunk->toolCalls, 'results' => $responseChunk->toolResults]);
                    }
                }
                $assistantMessage->update([
                    'content' => $fullResponse,
                    'is_streaming' => false,
                ]);
            } else {
                $response = $prismRequest->generate();
                $assistantMessage->update([
                    'content' => $response->text,
                    'is_streaming' => false,
                ]);
                if ($useTools && ($response->toolCalls || $response->toolResults)) {
                    Log::info('Regen Tool Generate:', ['calls' => $response->toolCalls, 'results' => $response->toolResults]);
                }
            }

            return $assistantMessage;
        } catch (\Exception $e) {
            $errorMessage = 'Sorry, I encountered an error while generating the response: '.$e->getMessage();
            $assistantMessage->update([
                'content' => $errorMessage,
                'is_streaming' => false,
            ]);
            Log::error('AI Generation Error (History): '.$e->getMessage(), [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $assistantMessage;
        }
    }

    /**
     * Build the message history for a conversation.
     */
    private function buildMessageHistory(Conversation $conversation): array
    {
        $messages = [];
        $user = Auth::user();
        $team = $user?->currentTeam;

        $userContext = "## User & Team Context\n";
        $userContext .= "- Current User: {$user->name}\n";
        if ($team) {
            $userContext .= "- Current Team: {$team->name}\n";
            // Updated advice about tools
            $userContext .= "- Use the available tools (student_data, activity_data, exam_data, schedule_data, user_team_info, class_resource_data) to get specific details when asked.";
        } else {
             $userContext .= "- No active team context.";
        }

        // Combine context and system prompt
        $systemContent = $this->getSystemPromptForStyle($conversation->style);
        if ($conversation->context) {
            $systemContent = $conversation->context . "\n\n" . $systemContent; // Prepend specific context if exists
        }
        $messages[] = new SystemMessage($systemContent . "\n\n" . $userContext);


        $chatMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(10) // Limit history
            ->get()
            ->reverse();

        foreach ($chatMessages as $message) {
            if (empty(trim($message->content)) && $message->role === 'assistant') {
                continue; // Skip empty placeholders
            }

            if ($message->role === 'user') {
                $messages[] = new UserMessage($message->content);
            } elseif ($message->role === 'assistant') {
                // TODO: Handle potential persisted tool call/result messages if Prism supports it
                $messages[] = new AssistantMessage($message->content);
            }
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
        // Prefer registered models via PrismServer if configured
        $registeredModels = PrismServer::prisms()->pluck('name')->toArray();
        if (!empty($registeredModels)) {
            return $registeredModels;
        }

        // Fallback to hardcoded list if PrismServer doesn't provide them
        return [
            'GPT-4o', 'GPT-4 Turbo', 'GPT-3.5 Turbo',
            'Gemini 1.5 Pro', 'Gemini 1.5 Flash', // 'Gemini Pro' likely older
            'Claude 3 Opus', 'Claude 3 Sonnet', 'Claude 3 Haiku',
            'GPT-4o Mini',
        ];
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
        $prisms = PrismServer::prisms();
        $prism = $prisms->firstWhere('name', $modelIdentifier);

        if ($prism) {
            // Use PascalCase for enum constants
            $provider = $prism['provider'] ?? Provider::OpenAI->value;
            $model = $prism['model'] ?? 'gpt-4o';
            return [$provider, $model];
        }

        // Fallback map with more robust model names and correct PascalCase
        $providerMap = [
            'GPT-4o' => [Provider::OpenAI->value, 'gpt-4o'],
            'GPT-4o Mini' => [Provider::OpenAI->value, 'gpt-4o-mini'],
            'GPT-4 Turbo' => [Provider::OpenAI->value, 'gpt-4-turbo'],
            'GPT-3.5 Turbo' => [Provider::OpenAI->value, 'gpt-3.5-turbo'],
            'Gemini 1.5 Pro' => [Provider::Gemini->value, 'gemini-1.5-pro-latest'],
            'Gemini 1.5 Flash' => [Provider::Gemini->value, 'gemini-1.5-flash-latest'],
            'Gemini Pro' => [Provider::Gemini->value, 'gemini-pro'],
            'Claude 3 Opus' => [Provider::Anthropic->value, 'claude-3-opus-20240229'],
            'Claude 3 Sonnet' => [Provider::Anthropic->value, 'claude-3-sonnet-20240229'],
            'Claude 3 Haiku' => [Provider::Anthropic->value, 'claude-3-haiku-20240307'],
        ];

        foreach ($providerMap as $name => $details) {
            if (strcasecmp($modelIdentifier, $name) == 0) {
                return $details;
            }
        }

        Log::warning('Model identifier not found. Defaulting to OpenAI GPT-4o.', [
            'identifier' => $modelIdentifier
        ]);
        // Use PascalCase for the default fallback
        return [Provider::OpenAI->value, 'gpt-4o'];
    }

    /**
     * Gets the system prompt instructions based on the selected style,
     * including updated tool usage guidelines.
     */
    private function getSystemPromptForStyle(string $style): string
    {
        $basePrompts = [
            'default' => 'You are a helpful Teacher Assistant. Provide clear and concise responses.',
            'creative' => 'You are a creative Teacher Assistant. Think outside the box and provide imaginative responses.',
            'precise' => 'You are a precise Teacher Assistant. Focus on accuracy and factual information. Be concise and to the point.',
            'balanced' => 'You are a balanced Teacher Assistant. Provide comprehensive yet accessible responses that balance detail with clarity.',
        ];

        // Updated tool instructions reflecting the new specialized tools
        $toolInstructions = <<<PROMPT

You have access to the following tools to retrieve specific user data:
*   `student_data`: Use for questions about students (listing, details, summaries, counts).
*   `activity_data`: Use for questions about activities (listing, details, counts).
*   `exam_data`: Use for questions about exams (listing, details, counts).
*   `schedule_data`: Use to fetch the weekly schedule.
*   `user_team_info`: Use for questions about the current user or team settings (like grading system).
*   `class_resource_data`: Use to find class resources/documents (`find_resources`) or get the content of a specific resource by its ID (`get_resource_content`).

Use these tools ONLY when the user explicitly asks about THEIR specific data related to these categories. Ask clarifying questions if the request is ambiguous. State clearly when you are retrieving data using a tool.

**Using `class_resource_data` for Content:**
If the user's message contains a string formatted EXACTLY like `resource_uuid:SOME_UUID_VALUE` (where SOME_UUID_VALUE is the actual UUID):
1.  You MUST use the `class_resource_data` tool.
2.  Set the tool's `query_type` parameter to the string value `get_resource_content`.
3.  Extract **ONLY** the UUID part (`SOME_UUID_VALUE`) from the `resource_uuid:SOME_UUID_VALUE` string.
4.  Set the tool's `resource_id` parameter to the extracted UUID value (e.g., `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`). **DO NOT** include the `resource_uuid:` prefix in the `resource_id` parameter value you send to the tool.

Example: If the input message contains `resource_uuid:123e4567-e89b-12d3-a456-426614174000\nWhat is this?`, your FIRST action MUST be to call the tool with parameters: `query_type="get_resource_content"` and `resource_id="123e4567-e89b-12d3-a456-426614174000"`. AFTER retrieving the content, THEN address the rest of the user's query ("What is this?") using the retrieved information.
PROMPT;

        return ($basePrompts[$style] ?? $basePrompts['default']) . $toolInstructions;
    }

    /**
     * Apply a quick action to a message.
     */
    public function applyQuickAction(string $actionPrompt, string $content): string
    {
        $fullPrompt = $actionPrompt . "\n\n---
\n" . $content;
        [$provider, $model] = $this->mapModelToProviderAndModel('GPT-4o Mini'); // Use a suitable model

        try {
            // Quick actions generally shouldn't use data tools
            $response = Prism::text()
                ->using($provider, $model)
                ->withPrompt($fullPrompt)
                ->usingTemperature(0.5)
                ->withMaxTokens(1000)
                ->generate();

            return $response->text;
        } catch (\Exception $e) {
            Log::error('Quick action error: ' . $e->getMessage(), [
                'actionPrompt' => $actionPrompt,
                'error' => $e->getMessage(),
            ]);
            return 'Sorry, I encountered an error: ' . $e->getMessage();
        }
    }

    /**
     * Update the conversation's last activity timestamp.
     */
    private function updateConversationActivity(Conversation $conversation): void
    {
        $conversation->update(['last_activity_at' => now()]);
    }

    /**
     * Get instances of all available data access tools.
     *
     * @return array An array of tool instances.
     */
    private function getAvailableTools(): array
    {
        // Use the service container to resolve GradingService for StudentTool
        $gradingService = app(GradingService::class);

        return [
            new StudentTool($gradingService),
            new ActivityTool(),
            new ExamTool(),
            new ScheduleTool(),
            new TeamInfoTool(),
            new ClassResourceTool(),
        ];
    }
}
