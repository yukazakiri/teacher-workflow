<?php

namespace App\Filament\Pages;

use App\Models\Chat;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class ChatPage extends Page 
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.chat-page';
    
    protected static ?string $navigationLabel = 'Chat Assistant';
    
    protected static ?string $title = 'AI Chat Assistant';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationGroup = 'Communication';
    
    protected static ?string $slug = 'chat';
    
    // Define a custom route to handle the chat parameter
    public static function getRoutes(): \Closure
    {
        return function () {
            // Default route
            \Illuminate\Support\Facades\Route::get('/chat', static::class)
                ->name(static::getSlug());
            
            // Route with chat parameter
            \Illuminate\Support\Facades\Route::get('/chat/{chat}', static::class)
                ->name(static::getSlug() . '.show');
        };
    }

    public ?Chat $chat = null;

    public function mount(Request $request, ?Chat $chat = null): void
    {
        $this->setTitle('AI Chat Assistant');
        
        if ($chat && $chat->exists) {
            $this->chat = $chat;
        }
    }

    public function getChatProperty()
    {
        return $this->chat;
    }
} 