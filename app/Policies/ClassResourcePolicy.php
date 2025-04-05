<?php

namespace App\Policies;

use App\Models\ClassResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can see the resources list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClassResource $classResource): bool
    {
        // Check if user can access this resource based on access level and team membership
        return $classResource->canBeAccessedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers and owners can create resources
        $team = $user->currentTeam;
        if (! $team) {
            return false;
        }

        return $team->userIsOwner($user) ||
               $user->hasTeamRole($team, 'teacher') ||
               $user->hasTeamRole($team, 'admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClassResource $classResource): bool
    {
        $team = $user->currentTeam;

        // Check team membership
        if (! $team || $team->id !== $classResource->team_id) {
            return false;
        }

        // Owner can edit all resources
        if ($team->userIsOwner($user)) {
            return true;
        }

        // Teachers can edit resources they created
        if ($user->hasTeamRole($team, 'teacher') && $classResource->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClassResource $classResource): bool
    {
        $team = $user->currentTeam;

        // Check team membership
        if (! $team || $team->id !== $classResource->team_id) {
            return false;
        }

        // Owner can delete all resources
        if ($team->userIsOwner($user)) {
            return true;
        }

        // Teachers can delete resources they created
        if ($user->hasTeamRole($team, 'teacher') && $classResource->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClassResource $classResource): bool
    {
        // Same as delete policy
        return $this->delete($user, $classResource);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClassResource $classResource): bool
    {
        // Only owners can permanently delete
        $team = $user->currentTeam;

        if (! $team || $team->id !== $classResource->team_id) {
            return false;
        }

        return $team->userIsOwner($user);
    }

    /**
     * Determine if user can manage categories.
     */
    public function manageCategories(User $user): bool
    {
        $team = $user->currentTeam;
        if (! $team) {
            return false;
        }

        return $team->userIsOwner($user);
    }
}
