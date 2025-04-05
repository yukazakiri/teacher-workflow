<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'team_id',
        'title',
        'description',
        'total_points',
        'status',
    ];

    /**
     * Get the teacher that owns the exam.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the team that owns the exam.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the questions for the exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the exam questions pivot records.
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    /**
     * Get the submissions for the exam.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    /**
     * Determine if the exam is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Determine if the exam is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Determine if the exam is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Calculate the total points for the exam.
     */
    public function calculateTotalPoints(): int
    {
        return $this->questions->sum('points');
    }

    /**
     * Update the total points for the exam.
     */
    public function updateTotalPoints(): void
    {
        $this->update(['total_points' => $this->calculateTotalPoints()]);
    }
}
