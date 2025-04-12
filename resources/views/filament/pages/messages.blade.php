<x-filament-panels::page>
    <div class="flex h-[calc(100vh-10rem)]"> {{-- Adjust height as needed --}}
        {{-- Sidebar for Channels --}}
        <div class="w-1/4 border-r border-gray-200 dark:border-gray-700 pr-4 overflow-y-auto">
            @livewire('chat.chat-sidebar')
        </div>

        {{-- Chat Window --}}
        <div class="w-3/4 pl-4 flex flex-col">
           @livewire('chat.chat-window')
        </div>
    </div>
</x-filament-panels::page>
