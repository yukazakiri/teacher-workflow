<?php

namespace App\Observers;

use App\Models\Exam;
use App\Services\ExamActivityHelper;
use Illuminate\Support\Facades\Log;

class ExamObserver
{
    protected ExamActivityHelper $examActivityHelper;

    public function __construct(ExamActivityHelper $examActivityHelper)
    {
        $this->examActivityHelper = $examActivityHelper;
    }

    /**
     * Handle the Exam "created" event.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    public function created(Exam $exam)
    {
        Log::info("Exam created event for Exam ID: {$exam->id}. Triggering activity sync.");
        $this->examActivityHelper->syncActivityForExam($exam);
    }

    /**
     * Handle the Exam "updated" event.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    public function updated(Exam $exam)
    {
         // Check if relevant fields that affect the Activity have changed
         if ($exam->isDirty(['title', 'description', 'total_points', 'status', 'team_id', 'teacher_id'])) {
             Log::info("Exam updated event for Exam ID: {$exam->id}. Triggering activity sync.");
             $this->examActivityHelper->syncActivityForExam($exam);
         }
    }

    /**
     * Handle the Exam "deleted" event.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    public function deleted(Exam $exam)
    {
         Log::info("Exam deleted event for Exam ID: {$exam->id}. Triggering activity deletion.");
         $this->examActivityHelper->deleteActivityForExam($exam);
    }

    /**
     * Handle the Exam "restored" event.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    public function restored(Exam $exam)
    {
        // Optional: Re-sync activity if exam is restored (if using soft deletes)
        Log::info("Exam restored event for Exam ID: {$exam->id}. Triggering activity sync.");
        $this->examActivityHelper->syncActivityForExam($exam);
    }

    /**
     * Handle the Exam "force deleted" event.
     *
     * @param  \App\Models\Exam  $exam
     * @return void
     */
    public function forceDeleted(Exam $exam)
    {
         // Optional: Ensure activity is deleted if exam is force deleted
         Log::info("Exam force deleted event for Exam ID: {$exam->id}. Triggering activity deletion.");
         $this->examActivityHelper->deleteActivityForExam($exam);
    }
}
