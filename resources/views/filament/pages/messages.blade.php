<x-filament-panels::page class="!p-0 max-w-full"> {{-- Remove padding, ensure full width --}}
    {{-- Main container using screen height --}}
    <div
        class="flex h-screen overflow-hidden rounded-lg" {{-- Use h-screen for full viewport height --}}
        x-data="{
            view: 'sidebar', // Can be 'sidebar' or 'chat'
            isMobile: window.innerWidth < 768, // md breakpoint
            init() {
                // Ensure sidebar is shown initially on mobile if no channel selected, 
                // or if a channel IS selected but we load on mobile.
                // ChatWindow should maybe dispatch an event if it loads a channel?
                // For simplicity, start with sidebar on mobile.
                if(this.isMobile) {
                    this.view = 'sidebar'; 
                }
                
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                    if (!this.isMobile) {
                        this.view = 'both'; // On desktop, view doesn't matter, both shown
                    }
                });
                
                // Listen for event from sidebar/window to switch view on mobile
                Livewire.on('channelSelected', (channelId) => {
                    if (this.isMobile && channelId) {
                        this.view = 'chat';
                    }
                });
            }
        }"
    >
        {{-- Sidebar --}}
        <div
            class="overflow-y-auto shrink-0 transition-all duration-300 ease-in-out" {{-- Removed border, added transition --}}
            :class="{
                'w-full md:w-80 xl:w-96': !isMobile || view === 'sidebar', {{-- Show full width on mobile sidebar view, fixed on desktop --}}
                'w-0': isMobile && view === 'chat', {{-- Collapse on mobile chat view --}}
                'hidden md:block': isMobile && view === 'chat' {{-- Hide completely on mobile chat view, but keep structure on desktop --}}
            }"
             x-show="!isMobile || view === 'sidebar'" {{-- Simplified show logic --}}
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0" 
             x-transition:leave="transition ease-in duration-300" 
             x-transition:leave-start="opacity-100 transform translate-x-0" 
             x-transition:leave-end="opacity-0 transform -translate-x-full"
        >
             {{-- Pass channelId, let Livewire handle initial load logic --}}
            @livewire('chat.chat-sidebar', ['channelId' => $channelId], key('chat-sidebar'))
        </div>

        {{-- Chat Window --}}
        <div
            class="flex flex-col flex-grow transition-all duration-300 ease-in-out" {{-- Use flex-grow, add transition --}}
             :class="{
                'w-full': !isMobile || view === 'chat', {{-- Show full width on mobile chat view and desktop --}}
                'w-0': isMobile && view === 'sidebar', {{-- Collapse on mobile sidebar view --}}
                'hidden md:flex': isMobile && view === 'sidebar' {{-- Hide completely on mobile sidebar view, but keep structure on desktop --}}
            }"
            x-show="!isMobile || view === 'chat'" {{-- Simplified show logic --}}
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-full"
            x-transition:enter-end="opacity-100 transform translate-x-0" 
            x-transition:leave="transition ease-in duration-300" 
            x-transition:leave-start="opacity-100 transform translate-x-0" 
            x-transition:leave-end="opacity-0 transform translate-x-full"
        >
            {{-- Pass channelId for initial load, but let the component's listener handle subsequent updates --}}
           @livewire('chat.chat-window', ['channelId' => $channelId], key('chat-window')) {{-- Removed dynamic part of key --}}
        </div>
    </div>
</x-filament-panels::page>
