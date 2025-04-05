<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ChatSidebarNavigation extends Component
{
    public function render()
    {
        $tenant = Auth::user()->currentTeam->id;

        return <<<HTML
        <div class="px-2 py-3">
            <div class="flex items-center justify-between mb-2 px-2">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recent Chats</h3>
                <a href="{{ route('filament.app.pages.chat', ['tenant' => {$tenant}]) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    <x-heroicon-s-plus-circle class="w-5 h-5" />
                </a>
            </div>
            
            <div class="space-y-1">
                @foreach(\$this->recentChats as \$chat)
                    <a 
                        href="{{ route('filament.app.pages.chat.show', ['tenant' => {$tenant}, 'chat' => \$chat->id]) }}" 
                        @class([
                            'flex items-center px-2 py-2 text-sm rounded-lg transition-colors group',
                            'bg-primary-500/10 text-primary-600 dark:text-primary-400' => request()->routeIs('filament.app.pages.chat.show') && request()->route('chat') == \$chat->id,
                            'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' => !(request()->routeIs('filament.app.pages.chat.show') && request()->route('chat') == \$chat->id),
                        ])
                    >
                        <x-heroicon-s-chat-bubble-left-ellipsis class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500 group-hover:text-primary-500 dark:group-hover:text-primary-400" />
                        <span class="truncate flex-1">{{ \$chat->title }}</span>
                    </a>
                @endforeach
                
                @if(\$this->recentChats->isEmpty())
                    <div class="text-xs text-center text-gray-500 dark:text-gray-400 py-2">
                        No recent chats
                    </div>
                @endif
                
                @if(\$this->hasMoreChats)
                    <a href="{{ route('filament.app.pages.dashboard', ['tenant' => {$tenant}]) }}" class="flex items-center justify-center px-2 py-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                        View all chats
                    </a>
                @endif
            </div>
        </div>
        HTML;
    }

    #[Computed]
    public function recentChats()
    {
        return Chat::where('user_id', Auth::id())
            ->where('team_id', Auth::user()->currentTeam->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function hasMoreChats()
    {
        return Chat::where('user_id', Auth::id())
            ->where('team_id', Auth::user()->currentTeam->id)
            ->count() > 5;
    }
}
