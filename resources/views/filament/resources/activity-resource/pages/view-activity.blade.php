<x-filament-panels::page>
    {{-- Display the main infolist for the activity record --}}
    {{ $this->infolist }}

    {{-- This is crucial for actions that open modals (like our submit_work action) --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
