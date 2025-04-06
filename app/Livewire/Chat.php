<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\PrismChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\ClassResource;
use Illuminate\Support\Facades\DB; // Import DB facade
use Illuminate\Support\Str; // Import Str facade

class Chat extends Component
{
    public ?Conversation $conversation = null;

    public string $message = '';

    // Property to store selected resource mentions for the current message
    // Format: [ 'Resource Title' => 'resource_id' ]
    public array $selectedResources = [];

    public string $selectedModel = 'Gemini 2.0 Flash';

    public string $selectedStyle = 'default';

    public bool $isProcessing = false;

    public array $availableModels = [];

    public array $availableStyles = [];

    public array $quickActions = [];

    public array $recentChats = [];

    public bool $isStreaming = false;

    // --- Mention Feature Properties ---
    public string $mentionQuery = '';
    public array $mentionResults = [];
    public bool $showMentionResults = false;
    // -------------------------------

    /**
     * Mount the component.
     */
    public function mount(
        PrismChatService $chatService,
        ?int $conversationId = null
    ) {
        // Load conversation if ID is provided
        if ($conversationId) {
            $this->conversation = Conversation::with('messages')->findOrFail(
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
     * Load recent chats for the user's current team.
     */
    protected function loadRecentChats(): void
    {
        $user = Auth::user();
        $currentTeamId = $user?->currentTeam?->id;

        if (! $currentTeamId) {
            $this->recentChats = []; // No team, no chats for the team

            return;
        }

        $this->recentChats = Conversation::where('team_id', $currentTeamId) // Filter by team_id
            ->where('user_id', $user->id) // Optionally keep filtering by user if needed, or remove if chats are team-wide
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
     * Get all chats for the user, ordered by last activity.
     */
    public function getAllChats(): array
    {
        return Conversation::where('user_id', Auth::id())
            ->orderBy('last_activity_at', 'desc')
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
     * Save an edited user message and regenerate the following response.
     */
    public function saveEditedMessage(int $messageId, string $newContent)
    {
        $newContent = trim($newContent);
        if (empty($newContent)) {
            // Optionally add validation feedback
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Message cannot be empty.',
            ]);

            return;
        }

        $message = ChatMessage::findOrFail($messageId);

        // Authorization: Ensure it's the user's own message and it's a 'user' role
        if (
            $message->user_id !== Auth::id() ||
            $message->role !== 'user' ||
            ! $this->conversation ||
            $message->conversation_id !== $this->conversation->id
        ) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Unauthorized action.',
            ]);

            return;
        }

        // Check if content actually changed
        if ($message->content === $newContent) {
            return; // No changes, do nothing
        }

        $this->isProcessing = true;
        // $this->isStreaming = true; // Streaming state will be set by the service/placeholder message

        try {
            // 1. Update the message content
            $message->update(['content' => $newContent]);
            $message->conversation->updateLastActivity(); // Update conversation timestamp

            // 2. Delete all subsequent messages
            ChatMessage::where('conversation_id', $message->conversation_id)
                ->where('created_at', '>', $message->created_at)
                ->delete();

            // 3. Refresh the conversation model to get the current state of messages
            $this->conversation->refresh();

            // 4. Get the history up to the edited message
            $history = $this->conversation
                ->messages()
                ->where('created_at', '<=', $message->created_at) // Use the timestamp of the *updated* message
                ->orderBy('created_at', 'asc')
                ->get();

            // 5. Trigger regeneration from the service
            $chatService = app(PrismChatService::class);
            $chatService->generateResponseFromHistory(
                $this->conversation,
                $history
            );

            // 6. Refresh again to include the new response (placeholder)
            $this->conversation->refresh();
            $this->dispatch('refreshChat'); // Tell Alpine to scroll
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            // Check state AFTER potential changes
            $this->checkStreamingState();
            // Only set processing to false if streaming has definitively stopped
            if (!$this->isStreaming) {
                 $this->isProcessing = false;
             }
        }
    }

    /**
     * Stores the details of a resource selected via mention.
     *
     * @param string $id The UUID of the resource.
     * @param string $title The title of the resource (used as the key).
     */
    public function addSelectedResource(string $id, string $title): void
    {
        $this->selectedResources[$title] = $id;
        Log::debug('Resource added for mention:', ['title' => $title, 'id' => $id, 'current_selected' => $this->selectedResources]);
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

        // --- Mention Processing --- START ---
        // Create a map for faster lookups
        $resourceMap = $this->selectedResources;
        Log::debug('Processing message with selected resources:', ['map' => $resourceMap, 'original_message' => $trimmedMessage]);

        // Replace @ResourceTitle with [Resource: Title ID: X]
        $processedMessage = preg_replace_callback('/@([\w\s.-]+)(?=\s|$)/', function ($matches) use ($resourceMap) {
            $title = trim($matches[1]);
            if (isset($resourceMap[$title])) {
                 $id = $resourceMap[$title];
                 Log::debug('Mention replacement found:', ['title' => $title, 'id' => $id]);
                 return sprintf('resource_uuid:%s', $id);
             } else {
                 Log::warning('Mention replacement not found for title:', ['title' => $title, 'map' => $resourceMap]);
                 // Keep the original @mention if no ID is found (or remove it, depending on desired behavior)
                 return $matches[0]; // Keep original mention
             }
         }, $trimmedMessage);

         $this->selectedResources = []; // Clear after processing
         // --- Mention Processing --- END ---

        // Use the processed message from now on
        $messageToSend = $processedMessage;
        Log::debug('Processed message content:', ['processed' => $messageToSend]);
        $this->message = ''; // Clear input immediately

        // Create a new conversation if one doesn't exist
        $isNewConversation = false;
        if (! $this->conversation) {
            $chatService = app(PrismChatService::class);
            $title =
                strlen($messageToSend) > 50
                    ? substr($messageToSend, 0, 47).'...'
                    : $messageToSend;
            $this->conversation = $chatService->createConversation(
                $title,
                $this->selectedModel,
                $this->selectedStyle
            );
            $isNewConversation = true;
        }

        // --- Create User Message First ---
        $userMessage = ChatMessage::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $messageToSend,
        ]);
        $this->conversation->updateLastActivity();
        // --- Refresh and Notify UI Immediately ---
        $this->conversation->refresh(); // Reload messages relation to include the user message
        $this->dispatch('refreshChat'); // Trigger scroll/UI update


        try {
            $chatService = app(PrismChatService::class);
            // Pass the conversation and the already created user message content (or ID if needed by service)
            // Service will handle creating the assistant placeholder and generating response
            $chatService->sendMessage($this->conversation, $userMessage->content); // Pass content again, or adjust service

            // Refresh necessary data after service call (might be redundant if polling handles it)
            // $this->conversation->refresh(); // Reload messages relation
            if ($isNewConversation) {
                $this->loadRecentChats();
            }

            // $this->dispatch('refreshChat'); // Already dispatched after user message
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            // Check state AFTER the service call might have changed it
            $this->checkStreamingState();
            // Only set processing to false if we are sure streaming isn't happening
            if (!$this->isStreaming) {
                $this->isProcessing = false;
            }
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

        if (! $action || empty($this->message)) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Apply the action
            $chatService = app(PrismChatService::class);
            $result = $chatService->applyQuickAction(
                $action['prompt'],
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
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc'); // Ensure messages are ordered correctly
            },
        ])->findOrFail($conversationId);

        $this->selectedModel = $this->conversation->model;
        $this->selectedStyle = $this->conversation->style;
        $this->message = ''; // Clear input when loading
        $this->checkStreamingState(); // Check if the loaded convo has a streaming message
        $this->dispatch('refreshChat'); // Ensure scroll happens on load
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
     * Regenerate the last assistant message based on the preceding user message.
     */
    public function regenerateLastMessage(): void
    {
        if (! $this->conversation) {
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
                ! $lastMessage ||
                $lastMessage->role !== 'assistant' ||
                ! $secondLastMessage ||
                $secondLastMessage->role !== 'user'
            ) {
                $this->dispatch('show-alert', [
                    'type' => 'warning',
                    'message' => 'Cannot regenerate response.',
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
                ->where('created_at', '<=', $secondLastMessage->created_at) // <= user message time
                ->orderBy('created_at', 'asc')
                ->get();

            // 4. Trigger regeneration from the service
            $chatService = app(PrismChatService::class);
            $chatService->generateResponseFromHistory(
                $this->conversation,
                $history
            );

            // 5. Refresh again to include the new response (placeholder)
            $this->conversation->refresh();
            $this->dispatch('refreshChat');
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
            ! $this->conversation ||
            empty($newTitle) ||
            $this->conversation->title === $newTitle
        ) {
            return;
        }

        $this->conversation->update(['title' => $newTitle]);
        $this->loadRecentChats(); // Refresh recent chats list
        // The getAllChats() method will fetch the updated title for the sidebar automatically on next render
    }

    /**
     * Handle errors that occur during AI processing.
     */
    public function handleError(\Exception $e): void
    {
        Log::error('Chat error: '.$e->getMessage(), [
            'conversation_id' => $this->conversation?->id,
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Create an error message for the user
        if ($this->conversation) {
            ChatMessage::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error: '.$e->getMessage(),
                'user_id' => null,
            ]);
        }

        $this->isProcessing = false;
        $this->dispatch('refreshChat');
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
                $lastMessage->role === 'assistant' &&
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
            if (! $this->isStreaming) {
                $this->dispatch('refreshChat'); // One last scroll after streaming finishes
                $this->isProcessing = false; // Mark processing as finished when streaming stops
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
        $view = view('livewire.chat');
        if ($this->isStreaming) {
            // Poll every 500ms while streaming
            $view->with(['pollingInterval' => '500ms']);
        }

        return $view;
    }

    // --- Mention Feature Methods ---

    /**
     * Searches for class resources based on the mention query.
     */
    public function searchResourceMentions(string $query)
    {
        Log::info('searchResourceMentions called', ['query' => $query]); // Add log
        $this->mentionQuery = trim($query);

        if (empty($this->mentionQuery)) {
            Log::info('Mention query empty, clearing results.'); // Add log
            $this->clearMentionResults();
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user || ! $team) {
             Log::warning('Mention search failed: No user or team context.'); // Add log
            $this->clearMentionResults();
            return;
        }

        Log::debug('Team ID used in mention search:', ['team_id' => $team->id]); // <-- ADD THIS LOG

        // Use a basic query optimized for typeahead - search title only for speed
        // Apply permission filtering
        $resourceQuery = ClassResource::query()
            ->where('team_id', $team->id)
            ->where(DB::raw('LOWER(title)'), 'like', '%' . strtolower($this->mentionQuery) . '%') // Corrected case-insensitive
            ->where(function ($q) { // Exclude archived
                $q->where('is_archived', false)->orWhereNull('is_archived');
            })
            ->select('id', 'title', 'category_id') // Select only needed fields
            ->with('category:id,name') // Eager load category name
            ->orderBy(DB::raw('CASE WHEN LOWER(title) LIKE '.DB::connection()->getPdo()->quote(strtolower($this->mentionQuery).'%').' THEN 0 ELSE 1 END')) // Case-insensitive order
            ->orderBy('updated_at', 'desc')
            ->limit(5); // Limit results for dropdown

        // Apply Permission Filtering
        $isOwner = $team->userIsOwner($user);
        if (! $isOwner) {
             if ($user->hasTeamRole($team, 'teacher')) {
                 $resourceQuery->where(function ($q) use ($user) {
                     $q->where('access_level', 'all')
                       ->orWhere('access_level', 'teacher')
                       ->orWhere('created_by', $user->id);
                 });
             } else {
                  $resourceQuery->where(function ($q) use ($user) {
                     $q->where('access_level', 'all')
                        ->orWhere('created_by', $user->id);
                 });
             }
        }

        // Log the SQL query before execution
        Log::debug('Mention Search SQL:', ['sql' => $resourceQuery->toSql(), 'bindings' => $resourceQuery->getBindings()]);

        $results = $resourceQuery->get();
        Log::info('Mention search results count:', ['count' => $results->count()]); // Restore original log

        $this->mentionResults = $results->map(function ($resource) {
            // Prepare data for the dropdown
            return [
                'id' => $resource->id, // May not be needed unless inserting ID
                'title' => $resource->title,
                'category' => $resource->category?->name ?? 'Uncategorized',
                // Format how you want to insert it - e.g., just title or a markdown link
                'insert_text' => $resource->title, // Simple title insertion for now
                // 'insert_text' => '[@resource:'.$resource->id.']('.$resource->file_url.')', // Example markdown link
            ];
        })->toArray();

        $this->showMentionResults = !empty($this->mentionResults);
    }

    /**
     * Clears mention results and hides the dropdown.
     */
    public function clearMentionResults(): void
    {
        Log::info('clearMentionResults called'); // Add log
        $this->mentionQuery = '';
        $this->mentionResults = [];
        $this->showMentionResults = false;
    }

    // --- End Mention Feature Methods ---
}
