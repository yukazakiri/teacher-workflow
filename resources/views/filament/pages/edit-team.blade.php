<x-filament-panels::page>
    <!-- Team Join Code Manager Component -->
    @livewire(\App\Livewire\TeamJoinCodeManager::class, ['team' => $team])

    <!-- Team Basic Information -->
    @livewire(Laravel\Jetstream\Http\Livewire\UpdateTeamNameForm::class, compact('team'))

    <!-- Team Members Management -->
    @livewire(Laravel\Jetstream\Http\Livewire\TeamMemberManager::class, compact('team'))

    @if (Gate::check('delete', $team) && ! $team->personal_team)
        <x-section-border/>

        @livewire(Laravel\Jetstream\Http\Livewire\DeleteTeamForm::class, compact('team'))
    @endif
</x-filament-panels::page>
