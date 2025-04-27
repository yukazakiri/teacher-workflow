<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityProgress;
use App\Models\ActivityRole;
use App\Models\ActivitySubmission;
use App\Models\Group;
use App\Models\GroupRoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /**
     * Display the progress tracking page for an activity.
     */
    public function progress(Activity $activity): View
    {
        // Check if user has access to this activity
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to access this activity.');
        }

        // Get all submissions for this activity
        $submissions = ActivitySubmission::with('student')
            ->where('activity_id', $activity->id)
            ->get();

        // Get progress records for this activity
        $progressRecords = ActivityProgress::with('student')
            ->where('activity_id', $activity->id)
            ->get();

        // Calculate completion rate
        $totalStudents = Auth::user()->currentTeam->allUsers()->count() - 1; // Exclude the teacher
        $completedSubmissions = $submissions->where('status', 'completed')->count();
        $completionRate = $totalStudents > 0 ? ($completedSubmissions / $totalStudents) * 100 : 0;

        // Calculate average score
        $averageScore = $submissions->where('status', 'completed')->avg('score') ?? 0;

        // Group students by progress status
        $studentsByStatus = [
            'not_started' => [],
            'in_progress' => [],
            'completed' => [],
        ];

        foreach (Auth::user()->currentTeam->allUsers() as $user) {
            if ($user->id === Auth::id()) {
                continue; // Skip the teacher
            }

            $submission = $submissions->where('student_id', $user->id)->first();
            $progress = $progressRecords->where('student_id', $user->id)->first();

            if ($submission && $submission->status === 'completed') {
                $studentsByStatus['completed'][] = [
                    'user' => $user,
                    'submission' => $submission,
                    'progress' => $progress,
                ];
            } elseif ($progress && $progress->percentage > 0) {
                $studentsByStatus['in_progress'][] = [
                    'user' => $user,
                    'submission' => $submission,
                    'progress' => $progress,
                ];
            } else {
                $studentsByStatus['not_started'][] = [
                    'user' => $user,
                    'submission' => null,
                    'progress' => null,
                ];
            }
        }

        return view('activities.progress', [
            'activity' => $activity,
            'submissions' => $submissions,
            'progressRecords' => $progressRecords,
            'completionRate' => $completionRate,
            'averageScore' => $averageScore,
            'studentsByStatus' => $studentsByStatus,
        ]);
    }

    /**
     * Generate a report for an activity.
     */
    public function generateReport(Request $request, Activity $activity)
    {
        // Check if user has access to this activity
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to access this activity.');
        }

        // Get all submissions for this activity
        $submissions = ActivitySubmission::with('student')
            ->where('activity_id', $activity->id)
            ->get();

        // Calculate statistics
        $totalStudents = Auth::user()->currentTeam->allUsers()->count() - 1; // Exclude the teacher
        $completedSubmissions = $submissions->where('status', 'completed')->count();
        $completionRate = $totalStudents > 0 ? ($completedSubmissions / $totalStudents) * 100 : 0;
        $averageScore = $submissions->where('status', 'completed')->avg('score') ?? 0;

        // Generate the report based on the requested format
        $format = $request->input('format', 'pdf');

        $reportData = [
            'activity' => $activity,
            'submissions' => $submissions,
            'totalStudents' => $totalStudents,
            'completedSubmissions' => $completedSubmissions,
            'completionRate' => $completionRate,
            'averageScore' => $averageScore,
        ];

        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('exports.activity_report', $reportData);

            return $pdf->download("activity_report_{$activity->id}.pdf");
        } elseif ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=activity_report_{$activity->id}.csv",
            ];

            $callback = function () use ($reportData): void {
                $handle = fopen('php://output', 'w');

                // Add header row
                fputcsv($handle, ['Student Name', 'Status', 'Score', 'Submission Date']);

                // Add data rows
                foreach ($reportData['submissions'] as $submission) {
                    fputcsv($handle, [
                        $submission->student->name,
                        $submission->status,
                        $submission->score,
                        $submission->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } elseif ($format === 'excel') {
            // For Excel export, you would typically use a package like Laravel Excel
            // This is a simplified example
            return back()->with('error', 'Excel export is not implemented yet.');
        }

        return back()->with('error', 'Unsupported export format.');
    }

    /**
     * Grade an activity submission.
     */
    public function gradeSubmission(Request $request, ActivitySubmission $submission)
    {
        // Validate the request
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:'.$submission->activity->total_points,
            'feedback' => 'nullable|string',
        ]);

        // Check if user has permission to grade this submission
        $activity = $submission->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to grade this submission.');
        }

        // Update the submission
        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'],
            'status' => 'completed',
            'graded_by' => Auth::id(),
            'graded_at' => now(),
        ]);

        return back()->with('success', 'Submission graded successfully.');
    }

    /**
     * Create a new group for an activity.
     */
    public function createGroup(Request $request, Activity $activity)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if user has permission to manage this activity
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this activity.');
        }

        // Create the group
        $group = Group::create([
            'activity_id' => $activity->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        return back()->with('success', 'Group created successfully.');
    }

    /**
     * Add a student to a group.
     */
    public function addStudentToGroup(Request $request, Group $group)
    {
        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if user has permission to manage this group
        $activity = $group->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this group.');
        }

        // Check if the student is in the same team
        $user = Auth::user()->currentTeam->users()->where('id', $validated['user_id'])->first();
        if (! $user) {
            abort(403, 'The selected student is not in your team.');
        }

        // Add the student to the group
        $group->members()->syncWithoutDetaching([$validated['user_id']]);

        return back()->with('success', 'Student added to group successfully.');
    }

    /**
     * Remove a student from a group.
     */
    public function removeStudentFromGroup(Request $request, Group $group)
    {
        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if user has permission to manage this group
        $activity = $group->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this group.');
        }

        // Remove the student from the group
        $group->members()->detach($validated['user_id']);

        // Also remove any role assignments for this student in this group
        GroupRoleAssignment::where('group_id', $group->id)
            ->where('user_id', $validated['user_id'])
            ->delete();

        return back()->with('success', 'Student removed from group successfully.');
    }

    /**
     * Create a new role for an activity.
     */
    public function createRole(Request $request, Activity $activity)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if user has permission to manage this activity
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this activity.');
        }

        // Create the role
        $role = ActivityRole::create([
            'activity_id' => $activity->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        return back()->with('success', 'Role created successfully.');
    }

    /**
     * Assign a role to a student in a group.
     */
    public function assignRole(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'user_id' => 'required|exists:users,id',
            'activity_role_id' => 'required|exists:activity_roles,id',
            'notes' => 'nullable|string',
        ]);

        // Check if user has permission to manage this group
        $group = Group::findOrFail($validated['group_id']);
        $activity = $group->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this group.');
        }

        // Check if the student is in the group
        if (! $group->members()->where('user_id', $validated['user_id'])->exists()) {
            abort(403, 'The selected student is not in this group.');
        }

        // Check if the role belongs to the activity
        $role = ActivityRole::findOrFail($validated['activity_role_id']);
        if ($role->activity_id !== $activity->id) {
            abort(403, 'The selected role does not belong to this activity.');
        }

        // Create or update the role assignment
        GroupRoleAssignment::updateOrCreate(
            [
                'group_id' => $validated['group_id'],
                'user_id' => $validated['user_id'],
            ],
            [
                'activity_role_id' => $validated['activity_role_id'],
                'notes' => $validated['notes'],
            ]
        );

        return back()->with('success', 'Role assigned successfully.');
    }

    /**
     * Remove a role assignment.
     */
    public function removeRoleAssignment(GroupRoleAssignment $assignment)
    {
        // Check if user has permission to manage this assignment
        $group = $assignment->group;
        $activity = $group->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this role assignment.');
        }

        // Delete the assignment
        $assignment->delete();

        return back()->with('success', 'Role assignment removed successfully.');
    }

    /**
     * View a specific submission.
     */
    public function viewSubmission(ActivitySubmission $submission): View
    {
        // Check if user has permission to view this submission
        $activity = $submission->activity;
        if ($activity->team_id !== Auth::user()->currentTeam->id && $activity->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to view this submission.');
        }

        return view('activities.submission', [
            'submission' => $submission,
            'activity' => $activity,
        ]);
    }
}
