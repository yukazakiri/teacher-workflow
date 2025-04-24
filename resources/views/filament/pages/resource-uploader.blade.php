<x-filament-panels::page>
    <form wire:submit.prevent="create">
        {{ $this->form }}

        <div class="fi-form-actions mt-6">
            <x-filament::button type="submit">
                Upload Resource
            </x-filament::button>
        </div>
    </form>

    {{-- Add this if you need Livewire component state debugging --}}
    {{-- @json($this->data) --}}
</x-filament-panels::page> 