<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\Attributes\On;

class ChatInput extends Component
{
    public string $message = '';
    public bool $isProcessing = false;
    public array $availableModels = [];
    public array $availableStyles = [];
    public array $quickActions = [];
    public string $selectedModel;
    public string $selectedStyle;

    public function mount(array $availableModels, array $availableStyles, array $quickActions, string $selectedModel, string $selectedStyle)
    {
        $this->availableModels = $availableModels;
        $this->availableStyles = $availableStyles;
        $this->quickActions = $quickActions;
        $this->selectedModel = $selectedModel;
        $this->selectedStyle = $selectedStyle;
    }

    public function sendMessage()
    {
        if (empty($this->message)) {
            return;
        }

        $this->dispatch('sendMessage', message: $this->message);
        $this->message = '';
    }

    public function applyQuickAction(string $actionName): void
    {
        $action = collect($this->quickActions)->firstWhere('name', $actionName);
        
        if (!$action || empty($this->message)) {
            return;
        }

        $this->dispatch('applyQuickAction', action: $action, message: $this->message);
    }

    public function changeModel(string $model): void
    {
        $this->dispatch('changeModel', model: $model);
    }

    public function changeStyle(string $style): void
    {
        $this->dispatch('changeStyle', style: $style);
    }

    public function newConversation(): void
    {
        $this->dispatch('newConversation');
    }

    public function render()
    {
        return view('livewire.chat.chat-input');
    }
}
