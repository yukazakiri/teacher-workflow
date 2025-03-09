<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubmission extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'score',
        'feedback',
        'started_at',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the exam that owns the submission.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student that owns the submission.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the responses for the submission.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ExamQuestionResponse::class);
    }

    /**
     * Determine if the submission is not started.
     */
    public function isNotStarted(): bool
    {
        return $this->status === 'not_started';
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
     * Determine if the submission is graded.
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Calculate the score for the submission.
     */
    public function calculateScore(): int
    {
        return $this->responses->sum('score');
    }

    /**
     * Update the score for the submission.
     */
    public function updateScore(): void
    {
        $this->update(['score' => $this->calculateScore()]);
    }
}
