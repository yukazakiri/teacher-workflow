<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Both teachers and students can view students
        return $user->hasTeamRole($user->currentTeam, 'teacher') ||
               $user->hasTeamRole($user->currentTeam, 'student');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($student->team)) {
            return false;
        }

        // Teachers can view any student in their team
        if ($user->hasTeamRole($student->team, 'teacher')) {
            return true;
        }

        // Students can only view themselves
        if ($user->hasTeamRole($student->team, 'student')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers can create students
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($student->team)) {
            return false;
        }

        // Teachers can update any student in their team
        if ($user->hasTeamRole($student->team, 'teacher')) {
            return true;
        }

        // Students can only update themselves
        if ($user->hasTeamRole($student->team, 'student')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        // Only teachers can delete students and they must be on the same team
        return $user->belongsToTeam($student->team) &&
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Only teachers can bulk delete students
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        // Only team owners can permanently delete students
        return $user->belongsToTeam($student->team) &&
               $user->ownsTeam($student->team);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Only team owners can permanently bulk delete students
        return $user->ownsTeam($user->currentTeam);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Student $student): bool
    {
        // Only teachers can restore students and they must be on the same team
        return $user->belongsToTeam($student->team) &&
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // Only teachers can bulk restore students
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Student $student): bool
    {
        // Only teachers can replicate students and they must be on the same team
        return $user->belongsToTeam($student->team) &&
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        // Only teachers can reorder students
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }
}
