<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Both teachers and students can view activities
        return $user->hasTeamRole($user->currentTeam, 'teacher') ||
               $user->hasTeamRole($user->currentTeam, 'student');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Both teachers and students can view activities in their team
        return $user->hasTeamRole($activity->team, 'teacher') ||
               $user->hasTeamRole($activity->team, 'student');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers can create activities
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only teachers can update activities
        // Additionally, teachers can only update activities they created or if they're the team owner
        return $user->hasTeamRole($activity->team, 'teacher') &&
               ($activity->teacher_id === $user->id || $user->ownsTeam($activity->team));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only teachers can delete activities
        // Additionally, teachers can only delete activities they created or if they're the team owner
        return $user->hasTeamRole($activity->team, 'teacher') &&
               ($activity->teacher_id === $user->id || $user->ownsTeam($activity->team));
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Only teachers can bulk delete activities
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        // Only team owners can permanently delete activities
        return $user->belongsToTeam($activity->team) &&
               $user->ownsTeam($activity->team);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Only team owners can permanently bulk delete activities
        return $user->ownsTeam($user->currentTeam);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Activity $activity): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only teachers can restore activities
        // Additionally, teachers can only restore activities they created or if they're the team owner
        return $user->hasTeamRole($activity->team, 'teacher') &&
               ($activity->teacher_id === $user->id || $user->ownsTeam($activity->team));
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // Only teachers can bulk restore activities
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Activity $activity): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only teachers can replicate activities
        return $user->hasTeamRole($activity->team, 'teacher');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        // Only teachers can reorder activities
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }
}
