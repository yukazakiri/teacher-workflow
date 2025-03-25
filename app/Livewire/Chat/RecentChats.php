<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RecentChats extends Component
{
    public array $recentChats = [];

    public function mount()
    {
        $this->loadRecentChats();
    }

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

    public function loadConversation(int $conversationId): void
    {
        $this->dispatch('loadConversation', conversationId: $conversationId);
    }

    public function deleteConversation(int $conversationId): void
    {
        $this->dispatch('deleteConversation', conversationId: $conversationId);
    }

    public function newConversation(): void
    {
        $this->dispatch('newConversation');
    }

    public function render()
    {
        return view('livewire.chat.recent-chats');
    }
}
