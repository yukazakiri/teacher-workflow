<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;

class ExamActivityHelper
{
    /**
     * Create or update the associated Activity for an Exam.
     */
    public function syncActivityForExam(Exam $exam): ?Activity
    {
        // Find the 'Exam' ActivityType ID
        $examActivityType = ActivityType::where('name', 'Exam')->first();

        if (! $examActivityType) {
            Log::error("ActivityType 'Exam' not found. Cannot sync activity for Exam ID: {$exam->id}");
            return null;
        }

        // Determine the status for the Activity based on the Exam's status
        // Map 'published' exam -> 'published' activity, others -> 'draft' activity
        $activityStatus = $exam->isPublished() ? 'published' : 'draft';

        // Map Exam category/term (if applicable) - Placeholder for now
        // You might want logic here if Exams have components/terms
        $componentType = null; // Or determine based on team->grading_system_type if needed
        $term = null; // Or determine based on team->grading_system_type if needed

        // Prepare data for the Activity record
        $activityData = [
            'team_id' => $exam->team_id,
            'teacher_id' => $exam->teacher_id,
            'activity_type_id' => $examActivityType->id,
            'exam_id' => $exam->id, // Link back to the Exam
            'title' => $exam->title, // Use Exam title
            'description' => $exam->description, // Use Exam description
            'instructions' => 'Complete the linked exam.', // Generic instructions
            'category' => 'written', // Exams are typically 'written'
            'component_type' => $componentType,
            'term' => $term,
            'total_points' => $exam->total_points, // Sync total points
            'credit_units' => null, // Exams usually don't have GWA units directly
            'due_date' => null, // Exams might not have a typical due date here
            'status' => $activityStatus,
            'mode' => 'individual', // Exams are typically individual
            'submission_type' => 'manual', // Grades will be entered manually based on ExamSubmissions
            'allow_file_uploads' => false,
            'allow_text_entry' => false,
            'allow_teacher_submission' => true, // Allow teacher to manage gradesheet entry
        ];

        // Use updateOrCreate to handle both creation and updates
        $activity = Activity::updateOrCreate(
            ['exam_id' => $exam->id], // Find existing Activity by exam_id
            $activityData          // Data to update or create with
        );

        Log::info("Synced Activity ID: {$activity->id} for Exam ID: {$exam->id}");
        return $activity;
    }

    /**
     * Delete the associated Activity when an Exam is deleted.
     */
    public function deleteActivityForExam(Exam $exam): void
    {
        $activity = Activity::where('exam_id', $exam->id)->first();
        if ($activity) {
            // Consider deleting submissions first if needed, or let DB cascade handle it
            // $activity->submissions()->delete();
            $activity->delete();
            Log::info("Deleted Activity linked to Exam ID: {$exam->id}");
        }
    }
}
