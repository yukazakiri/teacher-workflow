<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Livewire\Component;

class ChatMessages extends Component
{
    public ?Conversation $conversation = null;

    public bool $isProcessing = false;

    public function mount(?Conversation $conversation = null, bool $isProcessing = false)
    {
        $this->conversation = $conversation;
        $this->isProcessing = $isProcessing;
    }

    public function regenerateLastMessage(): void
    {
        if (! $this->conversation) {
            return;
        }

        $this->dispatch('regenerateLastMessage');
    }

    public function renameConversation(string $newTitle): void
    {
        if (! $this->conversation) {
            return;
        }

        $this->dispatch('renameConversation', newTitle: $newTitle);
    }

    public function newConversation(): void
    {
        $this->dispatch('newConversation');
    }

    public function render()
    {
        return view('livewire.chat.chat-messages');
    }
}
