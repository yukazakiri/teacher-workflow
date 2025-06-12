@php
    use Illuminate\Support\Facades\Gate;
@endphp
<x-filament-panels::page>
    <!-- Team Join Code Manager Component -->
    @livewire(\App\Livewire\TeamJoinCodeManager::class, ['team' => $team])
    <x-section-border/>
    @livewire(App\Livewire\TeamJoinQrCodeManager::class, ['team' => $team])
    <x-section-border/>
    <!-- Team Basic Information -->
    @livewire(Laravel\Jetstream\Http\Livewire\UpdateTeamNameForm::class, compact('team'))

 
    <!-- Team Members Management -->
    <x-section-border/>
    @livewire(Laravel\Jetstream\Http\Livewire\TeamMemberManager::class, compact('team'))

    @if (Gate::check('delete', $team) && ! $team->personal_team)
        <x-section-border/>

        @livewire(\App\Livewire\DeleteTeamCustomForm::class, compact('team'))
    @endif
</x-filament-panels::page>
