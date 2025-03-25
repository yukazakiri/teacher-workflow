<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'email',
        'student_id',
        'gender',
        'birth_date',
        'notes',
        'status',
        'phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the team that the student belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user associated with the student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity submissions for the student.
     */
    public function activitySubmissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    /**
     * Get the exam submissions for the student.
     */
    public function examSubmissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    /**
     * Get the group assignments for the student.
     */
    public function groupAssignments(): HasMany
    {
        return $this->hasMany(StudentGroupAssignment::class);
    }

    /**
     * Get the activity progress records for the student.
     */
    public function activityProgress(): HasMany
    {
        return $this->hasMany(ActivityProgress::class);
    }

    /**
     * Determine if the student is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if the student is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Determine if the student has graduated.
     */
    public function hasGraduated(): bool
    {
        return $this->status === 'graduated';
    }
}
