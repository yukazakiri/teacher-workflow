<x-filament-panels::page>
    <div class="">
        <!-- Minimalist header with star icon -->
      
        {{-- @dd($availableModels) --}}
        <!-- Livewire Chat Component -->
        @livewire('chat', ['conversationId' => $conversationId ?? null])
    </div>
</x-filament-panels::page>
