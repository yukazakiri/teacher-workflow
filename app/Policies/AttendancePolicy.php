<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        // Teachers can view all attendance records
        if ($user->hasTeamRole($user->currentTeam, 'teacher')) {
            return true;
        }
        
        // Students can only view their own attendance records
        return $user->hasTeamRole($user->currentTeam, 'student');
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($attendance->team)) {
            return false;
        }

        // Teachers can view any attendance record in their team
        if ($user->hasTeamRole($attendance->team, 'teacher')) {
            return true;
        }

        // Students can only view their own attendance
        if ($user->hasTeamRole($attendance->team, 'student')) {
            return $attendance->student && $user->id === $attendance->student->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        // Only teachers can create attendance records
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Only teachers can update attendance records and they must be on the same team
        return $user->belongsToTeam($attendance->team) && 
               $user->hasTeamRole($attendance->team, 'teacher');
    }

    /**
     * Determine whether the user can delete the attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // Only team owners can delete attendance records
        return $user->belongsToTeam($attendance->team) && 
               $user->ownsTeam($attendance->team);
    }

    /**
     * Determine whether the user can restore the attendance record.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        // Only team owners can restore attendance records
        return $user->belongsToTeam($attendance->team) && 
               $user->ownsTeam($attendance->team);
    }

    /**
     * Determine whether the user can permanently delete the attendance record.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        // Only team owners can permanently delete attendance records
        return $user->belongsToTeam($attendance->team) && 
               $user->ownsTeam($attendance->team);
    }
    
    /**
     * Determine whether the user can update attendance status in bulk.
     */
    public function updateStatus(User $user, Attendance $attendance): bool
    {
        // Only teachers can update attendance status
        return $user->belongsToTeam($attendance->team) && 
               $user->hasTeamRole($attendance->team, 'teacher');
    }
} 