<?php

namespace App\Providers;

use App\Livewire\Chat\ChatInputWithFileUpload;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Livewire::component('chat.chat-input-with-file-upload', ChatInputWithFileUpload::class);
    }
}
