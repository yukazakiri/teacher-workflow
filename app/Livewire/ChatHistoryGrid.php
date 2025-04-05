<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ChatHistoryGrid extends Component
{
    public function render()
    {
        return view('livewire.chat-history-grid', [
            'chats' => $this->chats,
        ]);
    }

    #[Computed]
    public function chats()
    {
        return Chat::where('user_id', Auth::id())
            ->where('team_id', Auth::user()->currentTeam->id)
            ->latest()
            ->get();
    }

    public function deleteChat(Chat $chat)
    {
        $chat->delete();
    }
}
