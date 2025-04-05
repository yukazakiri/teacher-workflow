<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class ChatToolbar extends Component
{
    public array $availableModels = [];

    public array $availableStyles = [];

    public string $selectedModel;

    public string $selectedStyle;

    public function mount(array $availableModels, array $availableStyles, string $selectedModel, string $selectedStyle)
    {
        $this->availableModels = $availableModels;
        $this->availableStyles = $availableStyles;
        $this->selectedModel = $selectedModel;
        $this->selectedStyle = $selectedStyle;
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
        return view('livewire.chat.chat-toolbar');
    }
}
