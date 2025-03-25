<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Services\PrismChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;

class Chat extends Component
{
    public ?Conversation $conversation = null;
    public string $message = '';
    public string $selectedModel = 'GPT-4o';
    public string $selectedStyle = 'default';
    public bool $isProcessing = false;
    public array $availableModels = [];
    public array $availableStyles = [];
    public array $quickActions = [];
    public array $recentChats = [];

    /**
     * Mount the component.
     */
    public function mount(PrismChatService $chatService, ?int $conversationId = null)
    {
        // Load conversation if ID is provided
        if ($conversationId) {
            $this->conversation = Conversation::with('messages')->findOrFail($conversationId);
            $this->selectedModel = $this->conversation->model;
            $this->selectedStyle = $this->conversation->style;
        }

        // Load available models and styles
        $this->availableModels = $chatService->getAvailableModels();
        $this->availableStyles = $chatService->getAvailableStyles();
        
        // Define quick actions
        $this->quickActions = [
            [
                'name' => 'Polish prose',
                'description' => 'Improve writing style and clarity',
                'prompt' => 'Please polish the following text to improve its clarity, style, and professionalism: ',
            ],
            [
                'name' => 'Generate questions',
                'description' => 'Create discussion questions for students',
                'prompt' => 'Generate 5 thought-provoking discussion questions for students about the following topic: ',
            ],
            [
                'name' => 'Write a memo',
                'description' => 'Create a professional memo',
                'prompt' => 'Write a professional memo about the following topic: ',
            ],
            [
                'name' => 'Summarize',
                'description' => 'Create a concise summary',
                'prompt' => 'Please summarize the following text in a clear and concise manner: ',
            ],
            [
                'name' => 'Create lesson plan',
                'description' => 'Generate a detailed lesson plan',
                'prompt' => 'Create a detailed lesson plan for a class on the following topic: ',
            ],
            [
                'name' => 'Grade assignment',
                'description' => 'Provide feedback and grading',
                'prompt' => 'Provide detailed feedback and a grade for the following student work: ',
            ],
        ];
        
        // Load recent chats
        $this->loadRecentChats();
    }

    /**
     * Load recent chats for the user.
     */
    protected function loadRecentChats(): void
    {
        $this->recentChats = Conversation::where('user_id', Auth::id())
            ->orderBy('last_activity_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'model' => $chat->model,
                    'last_activity' => $chat->last_activity_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Send a message to the AI.
     */
    public function sendMessage()
    {
        if (empty($this->message)) {
            return;
        }

        $this->isProcessing = true;

        // Create a new conversation if one doesn't exist
        if (!$this->conversation) {
            $chatService = app(PrismChatService::class);
            $title = strlen($this->message) > 50 ? substr($this->message, 0, 47) . '...' : $this->message;
            $this->conversation = $chatService->createConversation(
                $title,
                $this->selectedModel,
                $this->selectedStyle
            );
        }

        try {
            // Send the message
            $chatService = app(PrismChatService::class);
            $chatService->sendMessage($this->conversation, $this->message);

            // Clear the message input and refresh the component
            $this->message = '';
            $this->isProcessing = false;
            $this->loadRecentChats();
            $this->dispatch('refreshChat');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Change the AI model.
     */
    public function changeModel(string $model): void
    {
        $this->selectedModel = $model;
        
        if ($this->conversation) {
            $this->conversation->update(['model' => $model]);
        }
    }

    /**
     * Change the chat style.
     */
    public function changeStyle(string $style): void
    {
        $this->selectedStyle = $style;
        
        if ($this->conversation) {
            $this->conversation->update(['style' => $style]);
        }
    }

    /**
     * Apply a quick action to the message.
     */
    public function applyQuickAction(string $actionName): void
    {
        // Find the action by name
        $action = collect($this->quickActions)->firstWhere('name', $actionName);
        
        if (!$action || empty($this->message)) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Apply the action
            $chatService = app(PrismChatService::class);
            $result = $chatService->applyQuickAction($action['prompt'], $this->message);
            
            // Update the message with the result
            $this->message = $result;
            $this->isProcessing = false;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Load a conversation.
     */
    public function loadConversation(int $conversationId): void
    {
        $this->conversation = Conversation::with('messages')->findOrFail($conversationId);
        $this->selectedModel = $this->conversation->model;
        $this->selectedStyle = $this->conversation->style;
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        if ($conversation->user_id !== Auth::id()) {
            return;
        }
        
        $conversation->delete();
        
        // If we're currently viewing this conversation, reset to new conversation
        if ($this->conversation && $this->conversation->id === $conversationId) {
            $this->newConversation();
        }
        
        // Refresh the recent chats list
        $this->loadRecentChats();
    }

    /**
     * Create a new conversation.
     */
    public function newConversation(): void
    {
        $this->conversation = null;
        $this->message = '';
        $this->selectedModel = 'GPT-4o';
        $this->selectedStyle = 'default';
    }

    /**
     * Set a prompt in the message input.
     */
    public function setPrompt(string $promptText): void
    {
        $this->message = $promptText;
    }

    /**
     * Regenerate the last assistant message.
     */
    public function regenerateLastMessage(): void
    {
        if (!$this->conversation) {
            return;
        }

        // Get the last user message
        $lastUserMessage = $this->conversation->messages()
            ->where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastUserMessage) {
            return;
        }

        // Delete the last assistant message
        $this->conversation->messages()
            ->where('role', 'assistant')
            ->orderBy('created_at', 'desc')
            ->first()?->delete();

        // Re-send the user message
        $this->isProcessing = true;
        $chatService = app(PrismChatService::class);
        $chatService->sendMessage($this->conversation, $lastUserMessage->content);
        $this->isProcessing = false;
        $this->dispatch('refreshChat');
    }

    /**
     * Rename the current conversation.
     */
    public function renameConversation(string $newTitle): void
    {
        if (!$this->conversation) {
            return;
        }

        $this->conversation->update([
            'title' => $newTitle
        ]);

        $this->loadRecentChats();
    }

    /**
     * Handle errors that occur during AI processing.
     */
    public function handleError(\Exception $e): void
    {
        Log::error('Chat error: ' . $e->getMessage(), [
            'conversation_id' => $this->conversation?->id,
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Create an error message for the user
        if ($this->conversation) {
            ChatMessage::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error: ' . $e->getMessage(),
                'user_id' => null,
            ]);
        }

        $this->isProcessing = false;
        $this->dispatch('refreshChat');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.chat');
    }
}
