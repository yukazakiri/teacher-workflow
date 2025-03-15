<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ActivitySubmissionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the submission form for an activity.
     */
    public function showSubmissionForm(Activity $activity)
    {
        $this->authorize('submit', $activity);

        $user = Auth::user();
        
        // Find or create a student record for the user
        $student = $this->findOrCreateStudent($user);
        
        // Check if the student already has a submission
        $submission = ActivitySubmission::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();
            
        // For group activities, check if the student's group has a submission
        if ($activity->isGroupActivity()) {
            $group = $student->groups()
                ->where('activity_id', $activity->id)
                ->first();
                
            if ($group) {
                $groupSubmission = ActivitySubmission::where('activity_id', $activity->id)
                    ->where('group_id', $group->id)
                    ->first();
                    
                if ($groupSubmission) {
                    $submission = $groupSubmission;
                }
            }
        }

        return view('activities.submit', [
            'activity' => $activity,
            'submission' => $submission,
        ]);
    }

    /**
     * Store a new submission or update an existing one.
     */
    public function storeSubmission(Request $request, Activity $activity)
    {
        $this->authorize('submit', $activity);

        $user = Auth::user();
        
        // Find or create a student record for the user
        $student = $this->findOrCreateStudent($user);
        
        // Validate the request
        $validated = $request->validate([
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
            'status' => 'required|in:draft,submitted',
        ]);
        
        // Handle group submissions
        $groupId = null;
        if ($activity->isGroupActivity()) {
            $group = $student->groups()
                ->where('activity_id', $activity->id)
                ->first();
                
            if (!$group) {
                throw ValidationException::withMessages([
                    'group' => 'You are not assigned to a group for this activity.',
                ]);
            }
            
            $groupId = $group->id;
        }
        
        // Find existing submission or create a new one
        $submission = ActivitySubmission::updateOrCreate(
            [
                'activity_id' => $activity->id,
                'student_id' => $student->id,
                'group_id' => $groupId,
            ],
            [
                'content' => $validated['content'] ?? null,
                'status' => $validated['status'],
                'submitted_at' => $validated['status'] === 'submitted' ? now() : null,
            ]
        );
        
        // Handle file attachments
        if ($request->hasFile('attachments')) {
            $attachments = [];
            
            if ($submission->attachments) {
                $attachments = $submission->attachments;
            }
            
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('submissions/' . $submission->id, 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
            
            $submission->attachments = $attachments;
            $submission->save();
        }
        
        if ($validated['status'] === 'submitted') {
            return redirect()->route('activities.index')
                ->with('success', 'Your submission has been received.');
        }
        
        return redirect()->route('activities.submit', $activity)
            ->with('success', 'Your draft has been saved.');
    }

    /**
     * Delete an attachment from a submission.
     */
    public function deleteAttachment(Request $request, ActivitySubmission $submission, int $index)
    {
        $activity = $submission->activity;
        $this->authorize('submit', $activity);
        
        $user = Auth::user();
        
        // Find or create a student record for the user
        $student = $this->findOrCreateStudent($user);
        
        // Verify the submission belongs to the student
        if ($submission->student_id !== $student->id) {
            // For group submissions, check if the student is in the group
            if ($submission->group_id) {
                $isInGroup = $student->groups()
                    ->where('id', $submission->group_id)
                    ->exists();
                    
                if (!$isInGroup) {
                    abort(403, 'You do not have permission to modify this submission.');
                }
            } else {
                abort(403, 'You do not have permission to modify this submission.');
            }
        }
        
        // Check if the submission is already graded
        if ($submission->isGraded()) {
            abort(403, 'You cannot modify a submission that has already been graded.');
        }
        
        $attachments = $submission->attachments;
        
        if (!isset($attachments[$index])) {
            abort(404, 'Attachment not found.');
        }
        
        $attachment = $attachments[$index];
        
        // Delete the file from storage
        if (isset($attachment['path'])) {
            Storage::disk('public')->delete($attachment['path']);
        }
        
        // Remove the attachment from the array
        unset($attachments[$index]);
        
        // Reindex the array
        $attachments = array_values($attachments);
        
        // Update the submission
        $submission->attachments = $attachments;
        $submission->save();
        
        return redirect()->route('activities.submit', $activity)
            ->with('success', 'Attachment deleted successfully.');
    }
    
    /**
     * Find or create a student record for the user.
     */
    private function findOrCreateStudent($user)
    {
        // Check if the user already has a student record
        if ($user->student) {
            return $user->student;
        }
        
        // Create a new student record for the user if they have the student role
        if ($user->hasTeamRole($user->currentTeam, 'student')) {
            $student = new Student();
            $student->user_id = $user->id;
            $student->team_id = $user->currentTeam->id;
            $student->name = $user->name;
            $student->email = $user->email;
            $student->save();
            
            return $student;
        }
        
        // If we get here, something is wrong - the user should have a student role
        abort(403, 'You must have a student role to submit work.');
    }
} 