<?php

namespace App\Policies;

use App\Models\ActivitySubmission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivitySubmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Both teachers and students can view lists of submissions (students typically see their own filtered list)
        return $user->hasTeamRole($user->currentTeam, "teacher") ||
            $user->hasTeamRole($user->currentTeam, "student");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }

        // Teachers can view any submission in their team
        if (
            $user->hasTeamRole($activitySubmission->activity->team, "teacher")
        ) {
            return true;
        }

        // Students can only view their own submissions
        return $user->hasTeamRole(
            $activitySubmission->activity->team,
            "student"
        ) && $activitySubmission->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only students can create (submit) activity submissions
        return $user->hasTeamRole($user->currentTeam, "student");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }

        // Teachers can update any submission (e.g., for grading)
        if (
            $user->hasTeamRole($activitySubmission->activity->team, "teacher")
        ) {
            return true;
        }

        // Students can update their own submissions (e.g., if resubmission is allowed)
        return $user->hasTeamRole(
            $activitySubmission->activity->team,
            "student"
        ) && $activitySubmission->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }

        // Teachers can delete any submission in their team
        if (
            $user->hasTeamRole($activitySubmission->activity->team, "teacher")
        ) {
            return true;
        }

        // Students can delete their own submissions
        if (
            $user->hasTeamRole(
                $activitySubmission->activity->team,
                "student"
            ) &&
            $activitySubmission->user_id === $user->id
        ) {
            return true;
        }

        // Team owners can also delete any submission
        return $user->ownsTeam($activitySubmission->activity->team);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Only teachers can bulk delete submissions
        return $user->hasTeamRole($user->currentTeam, "teacher");
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }
        // Only team owners can permanently delete submissions
        return $user->ownsTeam($activitySubmission->activity->team);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Only team owners can permanently bulk delete submissions
        return $user->ownsTeam($user->currentTeam);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }

        // Teachers or team owners can restore submissions
        return $user->hasTeamRole(
            $activitySubmission->activity->team,
            "teacher"
        ) || $user->ownsTeam($activitySubmission->activity->team);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // Only teachers can bulk restore submissions
        return $user->hasTeamRole($user->currentTeam, "teacher");
    }

    /**
     * Determine whether the user can replicate.
     * Replicating a submission is less common, but if needed, only teachers.
     */
    public function replicate(
        User $user,
        ActivitySubmission $activitySubmission
    ): bool {
        // Ensure they're on the same team via the activity
        if (!$user->belongsToTeam($activitySubmission->activity->team)) {
            return false;
        }

        // Only teachers can replicate submissions (if such an action is defined)
        return $user->hasTeamRole(
            $activitySubmission->activity->team,
            "teacher"
        );
    }

    /**
     * Determine whether the user can reorder.
     * Reordering submissions is typically a teacher's view/management task.
     */
    public function reorder(User $user): bool
    {
        // Only teachers can reorder submissions (e.g., in a list view)
        return $user->hasTeamRole($user->currentTeam, "teacher");
    }
}
