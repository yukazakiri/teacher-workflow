<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class ActionShortcuts extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function addStudent(): Action
    {
        return Action::make('addStudent')
            ->color('info')
            ->label('Add Student')
            ->keyBindings(['command+a', 'ctrl+a'])
            ->extraAttributes(['class' => 'w-full'])
            ->visible(fn () => auth()->user()->currentTeam->userIsOwner(auth()->user()))
            ->url(route('filament.app.resources.students.create', ['tenant' => auth()->user()->currentTeam->id]));
    }

    public function schedule(): Action
    {
        return Action::make('schedule')
            ->outlined()
            ->color('gray')
            ->label('Class Schedule')
            ->extraAttributes(['class' => 'w-full'])
            ->url(route('filament.app.pages.weekly-schedule', ['tenant' => auth()->user()->currentTeam->id]));
    }

    public function render(): string
    {
        return <<<'HTML'
            <div class="space-y-2">
                {{ $this->addStudent }}
 
                {{ $this->schedule }}
            </div>
        HTML;
    }
}
