<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use Laravel\Jetstream\Contracts\DeletesTeams;

class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Team $team): void
    {
        // Update current_team_id for owner if it matches the deleted team
        $team->owner()->where('current_team_id', $team->id)
                ->update(['current_team_id' => null]);

        // Update current_team_id for users if it matches the deleted team
        $team->users()->where('current_team_id', $team->id)
                ->update(['current_team_id' => null]);

        // Detach all users from the team
        $team->users()->detach();
        
        // Use softDelete instead of permanent delete
        $team->delete();
    }
}