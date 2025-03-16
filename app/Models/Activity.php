<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
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
        'activity_type_id',
        'title',
        'description',
        'instructions',
        'format',
        'custom_format',
        'category',
        'mode',
        'total_points',
        'status',
        'deadline',
        'submission_type',
        'allow_file_uploads',
        'allowed_file_types',
        'max_file_size',
        'allow_teacher_submission',
        'form_structure',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'datetime',
        'allow_file_uploads' => 'boolean',
        'allowed_file_types' => 'array',
        'max_file_size' => 'integer',
        'allow_teacher_submission' => 'boolean',
        'form_structure' => 'array',
    ];

    /**
     * Get the teacher that owns the activity.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the team that owns the activity.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the activity type.
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the groups for the activity.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the roles for the activity.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ActivityRole::class);
    }

    /**
     * Get the submissions for the activity.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    /**
     * Get the progress records for the activity.
     */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(ActivityProgress::class);
    }

    /**
     * Get the resources attached to this activity.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ActivityResource::class);
    }

    /**
     * Determine if the activity is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Determine if the activity is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Determine if the activity is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Determine if the activity is a group activity.
     */
    public function isGroupActivity(): bool
    {
        return $this->mode === 'group';
    }

    /**
     * Determine if the activity is an individual activity.
     */
    public function isIndividualActivity(): bool
    {
        return $this->mode === 'individual';
    }

    /**
     * Determine if the activity is a take-home activity.
     */
    public function isTakeHomeActivity(): bool
    {
        return $this->mode === 'take_home';
    }

    /**
     * Determine if the activity is a written activity.
     */
    public function isWrittenActivity(): bool
    {
        return $this->category === 'written';
    }

    /**
     * Determine if the activity is a performance activity.
     */
    public function isPerformanceActivity(): bool
    {
        return $this->category === 'performance';
    }

    /**
     * Determine if the activity allows file uploads.
     */
    public function allowsFileUploads(): bool
    {
        return $this->allow_file_uploads;
    }

    /**
     * Determine if the activity allows teacher submissions on behalf of students.
     */
    public function allowsTeacherSubmission(): bool
    {
        return $this->allow_teacher_submission;
    }

    /**
     * Determine if the activity is a form-based activity.
     */
    public function isFormActivity(): bool
    {
        return $this->submission_type === 'form';
    }

    /**
     * Determine if the activity is a resource submission activity.
     */
    public function isResourceActivity(): bool
    {
        return $this->submission_type === 'resource';
    }

    /**
     * Determine if the activity is a manual scoring activity.
     */
    public function isManualActivity(): bool
    {
        return $this->submission_type === 'manual';
    }
}
