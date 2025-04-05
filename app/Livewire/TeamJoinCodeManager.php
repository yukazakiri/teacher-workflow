<?php

namespace App\Livewire;

use App\Models\Team;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamJoinCodeManager extends Component
{
    public Team $team;

    public bool $isLoading = false;

    /**
     * Mount the component
     *
     * @return void
     */
    public function mount(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Regenerate the team's join code
     */
    public function regenerateJoinCode(): void
    {
        $this->isLoading = true;

        // Check if the user is authorized to update the team
        if (Auth::user()->id !== $this->team->user_id) {
            Notification::make()
                ->title('Permission denied')
                ->body('Only the class owner can regenerate the join code.')
                ->danger()
                ->send();

            $this->isLoading = false;

            return;
        }

        // Generate a new join code
        $this->team->generateJoinCode();
        $this->team->save();

        // Reset loading state
        $this->isLoading = false;

        Notification::make()
            ->title('Join code regenerated successfully')
            ->success()
            ->send();
    }

    /**
     * Copy join code to clipboard
     */
    public function copyJoinCode(): void
    {
        $this->dispatch('copy-to-clipboard', ['text' => $this->team->join_code]);

        Notification::make()
            ->title('Join code copied to clipboard')
            ->success()
            ->send();
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.team-join-code-manager');
    }
}
