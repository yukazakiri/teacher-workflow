<x-filament-panels::page>
    <div class="h-full">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold tracking-tight">Chat History</h1>
            <x-filament::button
                color="primary"
                icon="heroicon-o-plus"
                tag="a"
                href="{{ route('filament.app.pages.chat', ['tenant' => auth()->user()->currentTeam->id]) }}"
            >
                New Chat
            </x-filament::button>
        </div>
        
        <div>
            <livewire:chat-history-grid />
        </div>
    </div>
</x-filament-panels::page>
