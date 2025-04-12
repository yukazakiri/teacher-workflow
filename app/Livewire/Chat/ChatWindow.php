<?php

declare(strict_types=1);

namespace App\Livewire\Chat;

use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatWindow extends Component
{
    public ?string $selectedChannelId = null;

    public ?Channel $selectedChannel = null;

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $newMessageContent = '';
    
    /**
     * Initialize the component.
     * Dispatch event to check localStorage for a previously selected channel.
     */
    public function mount(): void
    {
        $this->messages = [];
        // Trigger the client-side check for a stored channel ID
        $this->dispatch('checkStoredChannel');
    }
    
    /**
     * Restore the channel based on the ID found in localStorage.
     * If the channel is invalid or doesn't belong to the team, load the default.
     */
    #[On('restoreChannel')]
    public function restoreChannel(?string $channelId): void
    {
        if ($channelId) {
            $channel = Channel::with('team')->find($channelId);
            // Validate if channel exists and user belongs to its team
            if ($channel && Auth::user()?->belongsToTeam($channel->team)) {
                $this->loadChannel($channelId);
                return; // Successfully restored
            }
        }
        
        // If restore failed or no ID provided, load the default
        $this->loadDefaultChannel();
    }

    /**
     * Load the "general" channel as the default.
     */
    public function loadDefaultChannel(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $team = $user->currentTeam; // Assuming you have the currentTeam relationship

        if ($team) {
            $defaultChannel = $team->channels()->where('name', 'general')->first();
            
            if ($defaultChannel) {
                $this->loadChannel($defaultChannel->id);
            } else {
                // Handle case where even the general channel doesn't exist for the team
                $this->resetState();
                // Optionally, add a flash message or error display
            }
        } else {
            $this->resetState(); // No team context
        }
    }

    /**
     * Listen for the channel selection event from the sidebar and load the channel.
     */
    #[On('channelSelected')]
    public function loadChannel(string $channelId): void
    {
        $newChannel = Channel::with('team')->find($channelId);

        // Validate channel exists and user belongs to the team
        if (! $newChannel || ! Auth::user()?->belongsToTeam($newChannel->team)) {
            // If the selected channel is invalid, maybe load default instead of just resetting?
            // $this->loadDefaultChannel(); 
            $this->resetState(); // Current behavior: just reset
            $this->dispatch('clearStoredChannel'); // Tell Alpine to clear invalid stored ID
            return;
        }
        
        // Only proceed if the channel is actually different or not yet loaded
        if ($this->selectedChannelId !== $channelId) {
            $this->selectedChannelId = $channelId;
            $this->selectedChannel = $newChannel;
            $this->loadMessages();
            
            // Store the valid channel ID in localStorage
            $this->dispatch('storeChannelId', $channelId);
            
            // Notify Alpine to scroll and potentially update sidebar state
            $this->dispatch('messageReceived'); 
        }
    }

    /**
     * Load messages for the currently selected channel.
     */
    public function loadMessages(): void
    {
        if (! $this->selectedChannel) {
            $this->messages = [];
            return;
        }

        // Fetch, map to structured arrays, then convert the mapped collection to an array
        $messagesArray = $this->selectedChannel->messages()
            ->with('user:id,name,profile_photo_path') // Select only needed user fields
            ->latest()
            ->take(50) // Consider pagination for very active channels
            ->get()
            ->map(function (Message $message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'user_id' => $message->user_id,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'), 
                    'created_at_human' => $message->created_at->diffForHumans(),
                    'is_edited' => $message->edited_at !== null,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->profile_photo_url,
                    ],
                ];
            })
            ->toArray();

        // Reverse the plain PHP array
        $this->messages = array_reverse($messagesArray);
    }

    /**
     * Send a new message.
     */
    public function sendMessage(): void
    {
        if (! $this->selectedChannel) {
            return;
        }

        $validated = $this->validate([
            'newMessageContent' => 'required|string|max:2000',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $message = Message::create([
            'channel_id' => $this->selectedChannel->id,
            'user_id' => $user->id,
            'content' => $validated['newMessageContent'],
        ]);
        
        // Load user relationship for broadcasting
        $message->load('user');

        // Immediately add this message to the messages array
        $this->messages[] = [
            'id' => $message->id,
            'content' => $message->content,
            'user_id' => $message->user_id,
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $message->created_at->diffForHumans(),
            'is_edited' => false,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->profile_photo_url,
            ],
        ];

        // Reset the input field
        $this->reset('newMessageContent');
        
        // Broadcast the event
        MessageSent::dispatch($message);
        
        // Dispatch event for scrolling to bottom
        $this->dispatch('messageReceived');
    }

    /**
     * Scrolls to the bottom of the chat window.
     * This is triggered from the frontend when needed.
     */
    #[On('scrollToBottom')]
    public function scrollToBottom(): void
    {
        $this->dispatch('messageReceived');
    }

    /**
     * Define Echo listeners dynamically based on selectedChannelId.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if (! $this->selectedChannelId) {
            return [
                'echo-private:channel.*.MessageSent' => 'handleNewMessage', // Listen broadly initially?
            ];
        }
        
        // Dynamically listen to the specific private channel
        $listenerKey = "echo-private:channel.{$this->selectedChannelId}.MessageSent";
        
        return [
            $listenerKey => 'handleNewMessage',
        ];
    }

    /**
     * Handle incoming broadcasted messages.
     */
    public function handleNewMessage(array $payload): void
    {
        // Ensure the message is for the currently selected channel
        if (!isset($payload['channel_id']) || $payload['channel_id'] !== $this->selectedChannelId) {
            // Optional: Show notification for messages in other channels?
            return;
        }

        // Avoid adding own messages again (already added optimistically)
        if (isset($payload['user']['id']) && $payload['user']['id'] === Auth::id()) {
            return;
        }
        
        // Check if the message already exists
        foreach ($this->messages as $existingMessage) {
            if (isset($existingMessage['id']) && $existingMessage['id'] === $payload['id']) {
                return; 
            }
        }
        
        // Add the new message
        $this->messages[] = [
            'id' => $payload['id'],
            'content' => $payload['content'],
            'channel_id' => $payload['channel_id'], // Make sure channel_id is broadcasted
            'user_id' => $payload['user']['id'],
            'created_at' => $payload['created_at'],
            'created_at_human' => $payload['created_at_human'],
            'is_edited' => $payload['is_edited'] ?? false,
            'user' => [
                'id' => $payload['user']['id'],
                'name' => $payload['user']['name'],
                'avatar' => $payload['user']['avatar'],
            ],
        ];
        
        // Sort messages if needed (might not be necessary if adding to end and broadcasting ensures order)
        usort($this->messages, fn ($a, $b) => strtotime($a['created_at']) <=> strtotime($b['created_at']));
        
        // Notify Alpine.js to scroll
        $this->dispatch('messageReceived');
    }

    private function resetState(): void
    {
        $this->selectedChannelId = null;
        $this->selectedChannel = null;
        $this->messages = [];
        $this->reset('newMessageContent');
    }

    public function render(): View
    {
        return view('livewire.chat.chat-window');
    }
}
