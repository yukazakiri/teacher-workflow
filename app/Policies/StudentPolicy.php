<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        // Both teachers and students can view the student list
        return $user->hasTeamRole($user->currentTeam, 'teacher') || 
               $user->hasTeamRole($user->currentTeam, 'student');
    }

    /**
     * Determine whether the user can view the student.
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
            return $user->id === $student->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create students.
     */
    public function create(User $user): bool
    {
        // Only teachers can create students
        return $user->hasTeamRole($user->currentTeam, 'teacher');
    }

    /**
     * Determine whether the user can update the student.
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

        // Students can update their own profile only
        if ($user->hasTeamRole($student->team, 'student')) {
            return $user->id === $student->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        // Only teachers can delete students
        return $user->belongsToTeam($student->team) && 
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can restore the student.
     */
    public function restore(User $user, Student $student): bool
    {
        // Only teachers can restore students
        return $user->belongsToTeam($student->team) && 
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can permanently delete the student.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        // Only teachers can force delete students
        return $user->belongsToTeam($student->team) && 
               $user->hasTeamRole($student->team, 'teacher');
    }

    /**
     * Determine whether the user can link or unlink a student to a user.
     */
    public function manageUserLinks(User $user, Student $student): bool
    {
        // Only team owners can link/unlink users
        return $user->belongsToTeam($student->team) && 
               $user->ownsTeam($student->team);
    }

    /**
     * Determine whether the user can view the student's attendance.
     */
    public function viewAttendance(User $user, Student $student): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($student->team)) {
            return false;
        }

        // Teachers can view any student's attendance
        if ($user->hasTeamRole($student->team, 'teacher')) {
            return true;
        }

        // Students can only view their own attendance
        if ($user->hasTeamRole($student->team, 'student')) {
            return $user->id === $student->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view the student's progress.
     */
    public function viewProgress(User $user, Student $student): bool
    {
        // Ensure they're on the same team
        if (!$user->belongsToTeam($student->team)) {
            return false;
        }

        // Teachers can view any student's progress
        if ($user->hasTeamRole($student->team, 'teacher')) {
            return true;
        }

        // Students can only view their own progress
        if ($user->hasTeamRole($student->team, 'student')) {
            return $user->id === $student->user_id;
        }

        return false;
    }
} 