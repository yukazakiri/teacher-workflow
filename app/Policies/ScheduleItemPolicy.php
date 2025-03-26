<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduleItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduleItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any schedule items.
     */
    public function viewAny(User $user): bool
    {
        // Both teachers and students can view schedule items
        return $user->hasTeamRole($user->currentTeam, 'teacher') || 
               $user->hasTeamRole($user->currentTeam, 'student');
    }

    /**
     * Determine whether the user can view the schedule item.
     */
    public function view(User $user, ScheduleItem $scheduleItem): bool
    {
        // Only team members can view their own team's schedule
        return $user->belongsToTeam($scheduleItem->team);
    }

    /**
     * Determine whether the user can create schedule items.
     */
    public function create(User $user): bool
    {
        // Only teachers can create schedule items
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the schedule item.
     */
    public function update(User $user, ScheduleItem $scheduleItem): bool
    {
        // Only teachers can update schedule items
        return $user->belongsToTeam($scheduleItem->team) && 
               $user->hasTeamRole($scheduleItem->team, 'teacher');
    }

    /**
     * Determine whether the user can delete the schedule item.
     */
    public function delete(User $user, ScheduleItem $scheduleItem): bool
    {
        // Only team owners can delete schedule items
        return $user->belongsToTeam($scheduleItem->team) && 
               $user->ownsTeam($scheduleItem->team);
    }

    /**
     * Determine whether the user can restore the schedule item.
     */
    public function restore(User $user, ScheduleItem $scheduleItem): bool
    {
        // Only team owners can restore schedule items
        return $user->belongsToTeam($scheduleItem->team) && 
               $user->ownsTeam($scheduleItem->team);
    }

    /**
     * Determine whether the user can permanently delete the schedule item.
     */
    public function forceDelete(User $user, ScheduleItem $scheduleItem): bool
    {
        // Only team owners can permanently delete schedule items
        return $user->belongsToTeam($scheduleItem->team) && 
               $user->ownsTeam($scheduleItem->team);
    }
} 