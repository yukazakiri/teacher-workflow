<?php

declare(strict_types=1);

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
        return true; // Everyone can access the index, but what they see will be filtered
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Team owners can view any activity in their team
        if ($user->ownsTeam($activity->team)) {
            return true;
        }

        // Teachers can view any activity in their team
        if ($user->hasTeamRole($activity->team, 'teacher')) {
            return true;
        }

        // Students can only view published activities
        if ($user->hasTeamRole($activity->team, 'student')) {
            return $activity->isPublished();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only team owners and teachers can create activities
        return $user->ownsTeam($user->currentTeam) || 
               $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Team owners can update any activity in their team
        if ($user->ownsTeam($activity->team)) {
            return true;
        }

        // Teachers can update activities they created
        if ($user->hasTeamRole($activity->team, 'teacher')) {
            return $activity->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Team owners can delete any activity in their team
        if ($user->ownsTeam($activity->team)) {
            return true;
        }

        // Teachers can delete activities they created
        if ($user->hasTeamRole($activity->team, 'teacher')) {
            return $activity->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate the model.
     */
    public function duplicate(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Team owners can duplicate any activity in their team
        if ($user->ownsTeam($activity->team)) {
            return true;
        }

        // Teachers can duplicate activities in their team
        if ($user->hasTeamRole($activity->team, 'teacher')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can track progress of the model.
     */
    public function trackProgress(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only published activities can have their progress tracked
        if (!$activity->isPublished()) {
            return false;
        }

        // Team owners can track progress of any activity in their team
        if ($user->ownsTeam($activity->team)) {
            return true;
        }

        // Teachers can track progress of activities in their team
        if ($user->hasTeamRole($activity->team, 'teacher')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can submit to the activity.
     */
    public function submit(User $user, Activity $activity): bool
    {
        // First verify the user belongs to the team
        if (!$user->belongsToTeam($activity->team)) {
            return false;
        }

        // Only published activities can be submitted to
        if (!$activity->isPublished()) {
            return false;
        }

        // Only students can submit to activities
        if ($user->hasTeamRole($activity->team, 'student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function edit(User $user, Activity $activity): bool
    {
        // // Only team owners and teachers can edit activities in their team
        // if ($user->id === $activity->team->user_id || $user->hasTeamRole($activity->team, 'teacher')) {
        //     return $activity->team_id === $user->currentTeam->id;
        // }

        return true;
    }
}
