<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Services\PrismChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;
class Chat extends Component
{
    public ?Conversation $conversation = null;
    public string $message = "";
    public string $selectedModel = "Gemini 2.0 Flash";
    public string $selectedStyle = "default";
    public bool $isProcessing = false;
    public array $availableModels = [];
    public array $availableStyles = [];
    public array $quickActions = [];
    public array $recentChats = [];
    public bool $isStreaming = false;
    /**
     * Mount the component.
     */
    public function mount(
        PrismChatService $chatService,
        ?int $conversationId = null
    ) {
        // Load conversation if ID is provided
        if ($conversationId) {
            $this->conversation = Conversation::with("messages")->findOrFail(
                $conversationId
            );
            $this->selectedModel = $this->conversation->model;
            $this->selectedStyle = $this->conversation->style;
        }

        // Load available models and styles
        $this->availableModels = $chatService->getAvailableModels();
        $this->availableStyles = $chatService->getAvailableStyles();

        // Define quick actions
        $this->quickActions = [
            [
                "name" => "Polish prose",
                "description" => "Improve writing style and clarity",
                "prompt" =>
                    "Please polish the following text to improve its clarity, style, and professionalism: ",
            ],
            [
                "name" => "Generate questions",
                "description" => "Create discussion questions for students",
                "prompt" =>
                    "Generate 5 thought-provoking discussion questions for students about the following topic: ",
            ],
            [
                "name" => "Write a memo",
                "description" => "Create a professional memo",
                "prompt" =>
                    "Write a professional memo about the following topic: ",
            ],
            [
                "name" => "Summarize",
                "description" => "Create a concise summary",
                "prompt" =>
                    "Please summarize the following text in a clear and concise manner: ",
            ],
            [
                "name" => "Create lesson plan",
                "description" => "Generate a detailed lesson plan",
                "prompt" =>
                    "Create a detailed lesson plan for a class on the following topic: ",
            ],
            [
                "name" => "Grade assignment",
                "description" => "Provide feedback and grading",
                "prompt" =>
                    "Provide detailed feedback and a grade for the following student work: ",
            ],
        ];

        // Load recent chats
        $this->loadRecentChats();
    }

    /**
     * Load recent chats for the user's current team.
     */
    protected function loadRecentChats(): void
    {
        $user = Auth::user();
        $currentTeamId = $user?->currentTeam?->id;

        if (!$currentTeamId) {
            $this->recentChats = []; // No team, no chats for the team
            return;
        }

        $this->recentChats = Conversation::where("team_id", $currentTeamId) // Filter by team_id
            ->where("user_id", $user->id) // Optionally keep filtering by user if needed, or remove if chats are team-wide
            ->orderBy("last_activity_at", "desc")
            ->limit(5)
            ->get()
            ->map(function ($chat) {
                return [
                    "id" => $chat->id,
                    "title" => $chat->title,
                    "model" => $chat->model,
                    "last_activity" => $chat->last_activity_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get all chats for the user, ordered by last activity.
     */
    public function getAllChats(): array
    {
        return Conversation::where("user_id", Auth::id())
            ->orderBy("last_activity_at", "desc")
            ->get()
            ->map(function ($chat) {
                return [
                    "id" => $chat->id,
                    "title" => $chat->title,
                    "model" => $chat->model,
                    "last_activity" => $chat->last_activity_at->diffForHumans(),
                ];
            })
            ->toArray();
    }
    /**
     * Save an edited user message and regenerate the following response.
     */
    public function saveEditedMessage(int $messageId, string $newContent)
    {
        $newContent = trim($newContent);
        if (empty($newContent)) {
            // Optionally add validation feedback
            $this->dispatch("show-alert", [
                "type" => "error",
                "message" => "Message cannot be empty.",
            ]);
            return;
        }

        $message = ChatMessage::findOrFail($messageId);

        // Authorization: Ensure it's the user's own message and it's a 'user' role
        if (
            $message->user_id !== Auth::id() ||
            $message->role !== "user" ||
            !$this->conversation ||
            $message->conversation_id !== $this->conversation->id
        ) {
            $this->dispatch("show-alert", [
                "type" => "error",
                "message" => "Unauthorized action.",
            ]);
            return;
        }

        // Check if content actually changed
        if ($message->content === $newContent) {
            return; // No changes, do nothing
        }

        $this->isProcessing = true;
        $this->isStreaming = true;

        try {
            // 1. Update the message content
            $message->update(["content" => $newContent]);
            $message->conversation->updateLastActivity(); // Update conversation timestamp

            // 2. Delete all subsequent messages
            ChatMessage::where("conversation_id", $message->conversation_id)
                ->where("created_at", ">", $message->created_at)
                ->delete();

            // 3. Refresh the conversation model to get the current state of messages
            $this->conversation->refresh();

            // 4. Get the history up to the edited message
            $history = $this->conversation
                ->messages()
                ->where("created_at", "<=", $message->created_at) // Use the timestamp of the *updated* message
                ->orderBy("created_at", "asc")
                ->get();

            // 5. Trigger regeneration from the service
            $chatService = app(PrismChatService::class);
            $chatService->generateResponseFromHistory(
                $this->conversation,
                $history
            );

            // 6. Refresh again to include the new response (placeholder)
            $this->conversation->refresh();
            $this->dispatch("refreshChat"); // Tell Alpine to scroll
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            $this->checkStreamingState();
            $this->isProcessing = false;
        }
    }
    /**
     * Send a message to the AI.
     */
    public function sendMessage()
    {
        $trimmedMessage = trim($this->message);
        if (empty($trimmedMessage)) {
            return;
        }

        $this->isProcessing = true;
        $this->isStreaming = true; // Expecting a stream

        // Create a new conversation if one doesn't exist
        $isNewConversation = false;
        if (!$this->conversation) {
            $chatService = app(PrismChatService::class);
            $title =
                strlen($trimmedMessage) > 50
                    ? substr($trimmedMessage, 0, 47) . "..."
                    : $trimmedMessage;
            $this->conversation = $chatService->createConversation(
                $title,
                $this->selectedModel,
                $this->selectedStyle
            );
            $isNewConversation = true;
        }

        // Store the message temporarily before clearing the input
        $messageToSend = $trimmedMessage;
        $this->message = ""; // Clear input immediately

        try {
            $chatService = app(PrismChatService::class);
            // Pass the actual message content
            $chatService->sendMessage($this->conversation, $messageToSend);

            // Refresh necessary data
            $this->conversation->refresh(); // Reload messages relation
            if ($isNewConversation) {
                $this->loadRecentChats();
            }

            // Dispatch event AFTER processing potentially starts
            // The UI update for streaming will happen via service/model events if implemented
            // Or simply rely on the next full refresh after streaming stops
            $this->dispatch("refreshChat");
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            // We set isStreaming based on ChatMessage state now
            $this->checkStreamingState();
            $this->isProcessing = false; // General processing ends, but streaming might continue
        }
    }

    /**
     * Change the AI model.
     */
    public function changeModel(string $model): void
    {
        $this->selectedModel = $model;

        if ($this->conversation) {
            $this->conversation->update(["model" => $model]);
        }
    }

    /**
     * Change the chat style.
     */
    public function changeStyle(string $style): void
    {
        $this->selectedStyle = $style;

        if ($this->conversation) {
            $this->conversation->update(["style" => $style]);
        }
    }

    /**
     * Apply a quick action to the message.
     */
    public function applyQuickAction(string $actionName): void
    {
        // Find the action by name
        $action = collect($this->quickActions)->firstWhere("name", $actionName);

        if (!$action || empty($this->message)) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Apply the action
            $chatService = app(PrismChatService::class);
            $result = $chatService->applyQuickAction(
                $action["prompt"],
                $this->message
            );

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
        // Use findOrFail which eager loads messages or use load() after finding
        $this->conversation = Conversation::with([
            "messages" => function ($query) {
                $query->orderBy("created_at", "asc"); // Ensure messages are ordered correctly
            },
        ])->findOrFail($conversationId);

        $this->selectedModel = $this->conversation->model;
        $this->selectedStyle = $this->conversation->style;
        $this->message = ""; // Clear input when loading
        $this->checkStreamingState(); // Check if the loaded convo has a streaming message
        $this->dispatch("refreshChat"); // Ensure scroll happens on load
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
        if (
            $this->conversation &&
            $this->conversation->id === $conversationId
        ) {
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
        $this->message = "";
        $this->selectedModel = "GPT-4o";
        $this->selectedStyle = "default";
    }

    /**
     * Set a prompt in the message input.
     */
    public function setPrompt(string $promptText): void
    {
        $this->message = $promptText;
    }

    /**
     * Regenerate the last assistant message based on the preceding user message.
     */
    public function regenerateLastMessage(): void
    {
        if (!$this->conversation) {
            return;
        }

        $this->isProcessing = true;
        $this->isStreaming = true; // Expecting a stream

        try {
            // Get messages ordered by creation time descending
            $messages = $this->conversation
                ->messages()
                ->latest()
                ->take(2)
                ->get();

            $lastMessage = $messages->first(); // Potentially the assistant message
            $secondLastMessage = $messages->get(1); // Potentially the user message

            // Validate: Need at least two messages, last is assistant, second last is user
            if (
                !$lastMessage ||
                $lastMessage->role !== "assistant" ||
                !$secondLastMessage ||
                $secondLastMessage->role !== "user"
            ) {
                $this->dispatch("show-alert", [
                    "type" => "warning",
                    "message" => "Cannot regenerate response.",
                ]);
                $this->isProcessing = false;
                $this->isStreaming = false;
                return;
            }

            // 1. Delete the last assistant message
            $lastMessage->delete();

            // 2. Refresh the conversation model to reflect the deletion
            $this->conversation->refresh();

            // 3. Get the history *up to the user message*
            $history = $this->conversation
                ->messages()
                ->where("created_at", "<=", $secondLastMessage->created_at) // <= user message time
                ->orderBy("created_at", "asc")
                ->get();

            // 4. Trigger regeneration from the service
            $chatService = app(PrismChatService::class);
            $chatService->generateResponseFromHistory(
                $this->conversation,
                $history
            );

            // 5. Refresh again to include the new response (placeholder)
            $this->conversation->refresh();
            $this->dispatch("refreshChat");
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            $this->checkStreamingState();
            $this->isProcessing = false;
        }
    }

    /**
     * Rename the current conversation.
     */
    public function renameConversation(string $newTitle): void
    {
        $newTitle = trim($newTitle);
        if (
            !$this->conversation ||
            empty($newTitle) ||
            $this->conversation->title === $newTitle
        ) {
            return;
        }

        $this->conversation->update(["title" => $newTitle]);
        $this->loadRecentChats(); // Refresh recent chats list
        // The getAllChats() method will fetch the updated title for the sidebar automatically on next render
    }

    /**
     * Handle errors that occur during AI processing.
     */
    public function handleError(\Exception $e): void
    {
        Log::error("Chat error: " . $e->getMessage(), [
            "conversation_id" => $this->conversation?->id,
            "user_id" => Auth::id(),
            "trace" => $e->getTraceAsString(),
        ]);

        // Create an error message for the user
        if ($this->conversation) {
            ChatMessage::create([
                "conversation_id" => $this->conversation->id,
                "role" => "assistant",
                "content" =>
                    "Sorry, I encountered an error: " . $e->getMessage(),
                "user_id" => null,
            ]);
        }

        $this->isProcessing = false;
        $this->dispatch("refreshChat");
    }
    /**
     * Check if the last message is currently streaming and update the component state.
     */
    protected function checkStreamingState(): void
    {
        if ($this->conversation) {
            $lastMessage = $this->conversation->messages()->latest()->first();
            $this->isStreaming =
                $lastMessage &&
                $lastMessage->role === "assistant" &&
                ($lastMessage->is_streaming ?? false);
        } else {
            $this->isStreaming = false;
        }
    }

    /**
     * Periodically check streaming state if a stream is active.
     * Use wire:poll for this.
     */
    public function pollStreamingState()
    {
        if ($this->isStreaming) {
            $this->conversation->refresh(); // Reload messages
            $this->checkStreamingState();
            if (!$this->isStreaming) {
                $this->dispatch("refreshChat"); // One last scroll after streaming finishes
            }
        }
    }
    /**
     * Render the component.
     */
    public function render()
    {
        // Check streaming state on every render cycle as a fallback
        $this->checkStreamingState();

        // Conditionally add polling if streaming is active
        $view = view("livewire.chat");
        if ($this->isStreaming) {
            // Poll every 500ms while streaming
            $view->with(["pollingInterval" => "500ms"]);
        }
        return $view;
    }
}
