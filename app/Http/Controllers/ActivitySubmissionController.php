<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResource;
use App\Models\ActivitySubmission;
use App\Models\Student;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        // Get activity resources that are public or owned by the teacher
        $resources = ActivityResource::where('activity_id', $activity->id)
            ->where(function ($query) use ($activity) {
                $query->where('is_public', true)
                    ->orWhere('user_id', $activity->teacher_id);
            })
            ->get();

        return view('activities.submit', [
            'activity' => $activity,
            'submission' => $submission,
            'resources' => $resources,
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

        // Validate the request based on submission type
        if ($activity->isFormActivity()) {
            $validated = $this->validateFormSubmission($request, $activity);
        } elseif ($activity->isResourceActivity()) {
            $validated = $this->validateResourceSubmission($request, $activity);
        } else {
            $validated = $request->validate([
                'content' => 'nullable|string',
                'status' => 'required|in:draft,submitted',
            ]);
        }

        // Handle group submissions
        $groupId = null;
        if ($activity->isGroupActivity()) {
            $group = $student->groups()
                ->where('activity_id', $activity->id)
                ->first();

            if (! $group) {
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
                'form_responses' => $validated['form_responses'] ?? null,
                'status' => $validated['status'],
                'submitted_at' => $validated['status'] === 'submitted' ? now() : null,
            ]
        );

        // Handle file attachments for resource submissions
        if ($activity->isResourceActivity() && $activity->allowsFileUploads() && $request->hasFile('attachments')) {
            $this->handleFileAttachments($request, $submission);
        }

        if ($validated['status'] === 'submitted') {
            return redirect()->route('activities.index')
                ->with('success', 'Your submission has been received.');
        }

        return redirect()->route('activities.submit', $activity)
            ->with('success', 'Your draft has been saved.');
    }

    /**
     * Store a submission made by a teacher on behalf of a student.
     */
    public function storeTeacherSubmission(Request $request, Activity $activity, Student $student)
    {
        $this->authorize('submitForStudent', [$activity, $student]);

        // Validate the request based on submission type
        if ($activity->isFormActivity()) {
            $validated = $this->validateFormSubmission($request, $activity);
        } elseif ($activity->isResourceActivity()) {
            $validated = $this->validateResourceSubmission($request, $activity);
        } else {
            $validated = $request->validate([
                'content' => 'nullable|string',
                'status' => 'required|in:draft,submitted',
            ]);
        }

        // Handle group submissions
        $groupId = null;
        if ($activity->isGroupActivity()) {
            $group = $student->groups()
                ->where('activity_id', $activity->id)
                ->first();

            if (! $group) {
                throw ValidationException::withMessages([
                    'group' => 'The student is not assigned to a group for this activity.',
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
                'form_responses' => $validated['form_responses'] ?? null,
                'status' => $validated['status'],
                'submitted_at' => $validated['status'] === 'submitted' ? now() : null,
                'submitted_by_teacher' => true,
            ]
        );

        // Handle file attachments for resource submissions
        if ($activity->isResourceActivity() && $activity->allowsFileUploads() && $request->hasFile('attachments')) {
            $this->handleFileAttachments($request, $submission);
        }

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Submission for '.$student->name.' has been saved.');
    }

    /**
     * Upload a resource for an activity.
     */
    public function uploadResource(Request $request, Activity $activity)
    {
        $this->authorize('uploadResource', $activity);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:'.($activity->max_file_size * 1024),
            'is_public' => 'boolean',
        ]);

        $file = $request->file('file');
        $path = $file->store('resources/'.$activity->id, 'public');

        ActivityResource::create([
            'activity_id' => $activity->id,
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Resource uploaded successfully.');
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

                if (! $isInGroup) {
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

        if (! isset($attachments[$index])) {
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
     * Delete a resource from an activity.
     */
    public function deleteResource(ActivityResource $resource)
    {
        $activity = $resource->activity;
        $this->authorize('manageResources', $activity);

        // Delete the file from storage
        Storage::disk('public')->delete($resource->file_path);

        // Delete the resource record
        $resource->delete();

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Resource deleted successfully.');
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
            $student = new Student;
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

    /**
     * Validate form submission data.
     */
    private function validateFormSubmission(Request $request, Activity $activity)
    {
        $rules = [
            'form_responses' => 'required|array',
            'status' => 'required|in:draft,submitted',
        ];

        // Add validation rules based on the form structure
        if ($activity->form_structure) {
            foreach ($activity->form_structure as $field) {
                if (isset($field['required']) && $field['required']) {
                    $rules['form_responses.'.$field['name']] = 'required';
                }
            }
        }

        return $request->validate($rules);
    }

    /**
     * Validate resource submission data.
     */
    private function validateResourceSubmission(Request $request, Activity $activity)
    {
        $rules = [
            'content' => 'nullable|string',
            'status' => 'required|in:draft,submitted',
        ];

        if ($activity->allowsFileUploads()) {
            $rules['attachments'] = 'nullable|array';
            $rules['attachments.*'] = 'file|max:'.($activity->max_file_size * 1024);

            // Add file type validation if specific types are allowed
            if ($activity->allowed_file_types && count($activity->allowed_file_types) > 0) {
                $mimeTypes = implode(',', $activity->allowed_file_types);
                $rules['attachments.*'] .= '|mimetypes:'.$mimeTypes;
            }
        }

        return $request->validate($rules);
    }

    /**
     * Handle file attachments for a submission.
     */
    private function handleFileAttachments(Request $request, ActivitySubmission $submission)
    {
        $attachments = [];

        if ($submission->attachments) {
            $attachments = $submission->attachments;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('submissions/'.$submission->id, 'public');
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
}
