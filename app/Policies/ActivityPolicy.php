<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activity;
use App\Models\Student;
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
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        // Teachers can view activities they created or in their team
        if ($user->hasTeamRole($user->currentTeam, 'teacher') || $user->hasTeamRole($user->currentTeam, 'admin')) {
            return $user->id === $activity->teacher_id || $user->currentTeam->id === $activity->team_id;
        }

        // Students can view published activities in their team
        if ($user->hasTeamRole($user->currentTeam, 'student')) {
            return $activity->isPublished() && $user->currentTeam->id === $activity->team_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasTeamRole($user->currentTeam, 'teacher') || $user->hasTeamRole($user->currentTeam, 'admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        // Only the teacher who created the activity or an admin can update it
        return $user->id === $activity->teacher_id || 
               ($user->hasTeamRole($user->currentTeam, 'admin') && $user->currentTeam->id === $activity->team_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        // Only the teacher who created the activity or an admin can delete it
        return $user->id === $activity->teacher_id || 
               ($user->hasTeamRole($user->currentTeam, 'admin') && $user->currentTeam->id === $activity->team_id);
    }

    /**
     * Determine whether the user can submit work for the activity.
     */
    public function submit(User $user, Activity $activity): bool
    {
        // Only students can submit work, and only for published activities in their team
        if (!$user->hasTeamRole($user->currentTeam, 'student')) {
            return false;
        }

        // Cannot submit to draft or archived activities
        if (!$activity->isPublished()) {
            return false;
        }

        // Must be in the same team
        if ($user->currentTeam->id !== $activity->team_id) {
            return false;
        }

        // Check if the activity is manual scoring only
        if ($activity->isManualActivity()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can grade submissions for the activity.
     */
    public function grade(User $user, Activity $activity): bool
    {
        // Only teachers can grade submissions
        if (!$user->hasTeamRole($user->currentTeam, 'teacher') && !$user->hasTeamRole($user->currentTeam, 'admin')) {
            return false;
        }

        // Must be in the same team
        if ($user->currentTeam->id !== $activity->team_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can submit work on behalf of a student.
     */
    public function submitForStudent(User $user, Activity $activity, Student $student): bool
    {
        // Only teachers can submit on behalf of students
        if (!$user->hasTeamRole($user->currentTeam, 'teacher') && !$user->hasTeamRole($user->currentTeam, 'admin')) {
            return false;
        }

        // Must be in the same team
        if ($user->currentTeam->id !== $activity->team_id) {
            return false;
        }

        // The activity must allow teacher submissions
        if (!$activity->allowsTeacherSubmission()) {
            return false;
        }

        // The student must be in the same team
        if ($student->team_id !== $activity->team_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can upload resources for the activity.
     */
    public function uploadResource(User $user, Activity $activity): bool
    {
        // Only teachers can upload resources
        if (!$user->hasTeamRole($user->currentTeam, 'teacher') && !$user->hasTeamRole($user->currentTeam, 'admin')) {
            return false;
        }

        // Must be in the same team
        if ($user->currentTeam->id !== $activity->team_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage resources for the activity.
     */
    public function manageResources(User $user, Activity $activity): bool
    {
        // Only the teacher who created the activity or an admin can manage resources
        return $user->id === $activity->teacher_id || 
               ($user->hasTeamRole($user->currentTeam, 'admin') && $user->currentTeam->id === $activity->team_id);
    }
}
