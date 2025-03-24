<?php

namespace App\Services;

use Prism\Prism\Prism;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Prism\Prism\Enums\Provider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;

class PrismChatService
{
    /**
     * Send a message to the AI and update the conversation with the response.
     *
     * @param Conversation $conversation
     * @param string $content
     * @return ChatMessage
     */
    public function sendMessage(Conversation $conversation, string $content): ChatMessage
    {
        // Create user message
        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $content,
            'user_id' => Auth::id(),
        ]);

        try {
            // Get message history
            $messages = $this->buildMessageHistory($conversation);
            
            // Get the model configuration
            [$provider, $model] = $this->mapModelToProviderAndModel($conversation->model);
            
            // Check if we should use streaming (only for OpenAI)
            $useStreaming = $provider === 'openai';
            
            // Create assistant message placeholder
            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => '',
                'user_id' => null,
                'is_streaming' => $useStreaming,
            ]);
            
            // Get AI response
            if ($useStreaming) {
                // Use streaming for OpenAI
                $response = Prism::text()
                    ->using($provider, $model)
                    ->withMessages($messages)
                    ->asStream();

                // Process each chunk as it arrives
                foreach ($response as $chunk) {
                    $assistantMessage->update([
                        'content' => $assistantMessage->content . $chunk->text
                    ]);
                    // Flush the output buffer
                    ob_flush();
                    flush();
                }
                
                // Mark streaming as complete
                $assistantMessage->is_streaming = false;
                $assistantMessage->save();
            } else {
                // Use regular completion for other providers
                $response = Prism::text()
                    ->using($provider, $model)
                    ->withMessages($messages)
                    ->generate();
                
                // Update message with complete response
                $assistantMessage->content = $response->text;
                $assistantMessage->save();
            }
            
            // Update conversation last activity
            $this->updateConversationActivity($conversation);
            
            return $assistantMessage;
        } catch (\Exception $e) {
            Log::error('AI response error: ' . $e->getMessage(), [
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Create error message
            return ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error: ' . $e->getMessage(),
                'user_id' => null,
            ]);
        }
    }

    /**
     * Build the message history for a conversation.
     *
     * @param Conversation $conversation
     * @return array
     */
    private function buildMessageHistory(Conversation $conversation): array
    {
        $messages = [];

        // Add user context information
        $user = Auth::user();
        $team = $user->currentTeam;
        
        $userContext = "## User Context Information\n";
        $userContext .= "- Current User: {$user->name} (Email: {$user->email})\n";
        
        if ($team) {
            $userContext .= "- Current Team: {$team->name}\n";
            
            // Add team members information
            $teamMembers = $team->allUsers();
            if ($teamMembers->count() > 0) {
                $userContext .= "- Team Members:\n";
                foreach ($teamMembers as $member) {
                    try {
                        // Get the team member's role safely
                        $teamMember = $team->users()->where('user_id', $member->id)->first();
                        $role = $teamMember && $teamMember->membership ? $teamMember->membership->role : 'member';
                        $userContext .= "  - {$member->name} ({$role})\n";
                    } catch (\Exception $e) {
                        // If there's any error, just use a default role
                        $userContext .= "  - {$member->name} (member)\n";
                    }
                }
            }
            
            // Add students information if available
            $students = $team->students;
            if ($students && $students->count() > 0) {
                $userContext .= "- Students in Team:\n";
                foreach ($students->take(5) as $student) {
                    $userContext .= "  - {$student->name}\n";
                }
                
                if ($students->count() > 5) {
                    $userContext .= "  - And " . ($students->count() - 5) . " more students\n";
                }
            }
        }
        
        // Add context if available
        if ($conversation->context) {
            $messages[] = new SystemMessage($conversation->context . "\n\n" . $userContext);
        } else {
            // Add default system prompt based on conversation style with user context
            $messages[] = new SystemMessage($this->getSystemPromptForStyle($conversation->style) . "\n\n" . $userContext);
        }

        // Get the last 10 messages
        $chatMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse();

        // Add the messages to the history
        foreach ($chatMessages as $message) {
            if ($message->role === 'user') {
                $messages[] = new UserMessage($message->content);
            } elseif ($message->role === 'assistant') {
                $messages[] = new AssistantMessage($message->content);
            }
        }

        return $messages;
    }

    /**
     * Create a new conversation.
     *
     * @param string $title
     * @param string $model
     * @param string $style
     * @param string|null $context
     * @return Conversation
     */
    public function createConversation(string $title, string $model = 'gpt-4o', string $style = 'default', ?string $context = null): Conversation
    {
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
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        return PrismServer::prisms()->pluck('name')->toArray();
    }

    /**
     * Get available chat styles.
     *
     * @return array
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
     *
     * @param string $modelIdentifier
     * @return array
     */
    private function mapModelToProviderAndModel(string $modelIdentifier): array
    {
        // Get all registered prisms
        $prisms = PrismServer::prisms();
        
        // Find the prism with the matching name
        $prism = $prisms->firstWhere('name', $modelIdentifier);
        
        if ($prism) {
            // Extract provider and model from the prism configuration
            $provider = $prism['provider'] ?? 'openai';
            $model = $prism['model'] ?? 'gpt-4o';
            return [$provider, $model];
        }
        
        // Fallback mappings for models not registered through PrismServer
        $providerMap = [
            'GPT-4o' => ['openai', 'gpt-4o'],
            'GPT-4 Turbo' => ['openai', 'gpt-4-turbo'],
            'GPT-3.5 Turbo' => ['openai', 'gpt-3.5-turbo'],
            'Gemini Pro' => ['gemini', 'gemini-pro'],
            'Gemini 1.5 Pro' => ['gemini', 'gemini-1.5-pro'],
            'Claude 3 Opus' => ['anthropic', 'claude-3-opus'],
            'Claude 3 Sonnet' => ['anthropic', 'claude-3-sonnet'],
            'Claude 3 Haiku' => ['anthropic', 'claude-3-haiku'],
            'Gemini 1.5 Flass' => ['gemini', 'gemini-1.5-flash'],
            'Gemini 2.0 Flash' => ['gemini', 'gemini-2.0-flash'],
            'GPT-4o Mini' => ['openai', 'gpt-4o-mini'],
        ];
        
        return $providerMap[$modelIdentifier] ?? ['openai', 'gpt-4o'];
    }

    /**
     * Get the system prompt for a specific style.
     *
     * @param string $style
     * @return string
     */
    private function getSystemPromptForStyle(string $style): string
    {
        $prompts = [
            'default' => 'You are a helpful assistant. Provide clear and concise responses.',
            'creative' => 'You are a creative assistant. Think outside the box and provide imaginative responses.',
            'precise' => 'You are a precise assistant. Focus on accuracy and factual information. Be concise and to the point.',
            'balanced' => 'You are a balanced assistant. Provide comprehensive yet accessible responses that balance detail with clarity.',
        ];

        return $prompts[$style] ?? $prompts['default'];
    }

    /**
     * Apply a quick action to a message.
     *
     * @param string $actionPrompt The prompt for the quick action
     * @param string $content The content to apply the action to
     * @return string The result of applying the action
     */
    public function applyQuickAction(string $actionPrompt, string $content): string
    {
        // Combine the action prompt with the content
        $fullPrompt = $actionPrompt . $content;

        // Get the provider and model
        $provider = 'openai';
        $model = 'gpt-4o';

        // Generate the response
        $response = Prism::text()
            ->using($provider, $model)
            ->withPrompt($fullPrompt)
            ->usingTemperature(0.7)
            ->generate();

        return $response->text;
    }

    /**
     * Update the conversation's last activity timestamp.
     *
     * @param Conversation $conversation
     */
    private function updateConversationActivity(Conversation $conversation): void
    {
        $conversation->update([
            'last_activity_at' => now(),
        ]);
    }
}
