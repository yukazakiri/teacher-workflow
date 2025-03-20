<x-filament-panels::page>
    <div class="h-full">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold tracking-tight">AI Chat Assistant</h1>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                Powered by OpenAI & Gemini
            </span>
        </div>
        
        <livewire:chat-interface :chat="$this->chat" />
    </div>
</x-filament-panels::page> 