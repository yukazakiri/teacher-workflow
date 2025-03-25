<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class WelcomeScreen extends Component
{
    public function setPrompt(string $promptText): void
    {
        $this->dispatch('setPrompt', prompt: $promptText);
    }

    public function render()
    {
        return view('livewire.chat.welcome-screen');
    }
}
