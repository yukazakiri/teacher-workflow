<?php

namespace App\Listeners;

use Filament\Events\TenantSet;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Session;
use Laravel\Jetstream\Features;

class SwitchTeam
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenantSet $event): void
    {
        if (Features::hasTeamFeatures()) {
            $user = $event->getUser();
            $team = $event->getTenant();

            // Get the previous team ID from session
            $previousTeamId = Session::get('current_team_id');

            // Only show notification if switching to a different team
            if ($previousTeamId !== $team->id) {
                $user->switchTeam($team);

                Notification::make()
                    ->title('Team Switched')
                    ->body("You've switched to team: {$team->name}")
                    ->success()
                    ->send();

                // Store the new team ID in session
                Session::put('current_team_id', $team->id);
            }
        }
    }
}
