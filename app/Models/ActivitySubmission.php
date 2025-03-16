<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySubmission extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'activity_id',
        'student_id',
        'group_id',
        'content',
        'form_responses',
        'attachments',
        'status',
        'score',
        'final_grade',
        'feedback',
        'submitted_at',
        'submitted_by_teacher',
        'graded_by',
        'graded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'form_responses' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'float',
        'final_grade' => 'float',
        'submitted_by_teacher' => 'boolean',
    ];

    /**
     * Get the activity that owns the submission.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the student that owns the submission.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the group that owns the submission.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the teacher who graded the submission.
     */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Determine if the submission is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Determine if the submission is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Determine if the submission is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Determine if the submission is completed (graded).
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Determine if the submission is late.
     */
    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /**
     * Determine if the submission has been graded.
     */
    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    /**
     * Determine if the submission was made by a teacher on behalf of a student.
     */
    public function isSubmittedByTeacher(): bool
    {
        return $this->submitted_by_teacher === true;
    }
}
