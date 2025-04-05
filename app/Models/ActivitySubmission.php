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
        'form_data' => 'array',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
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

    /**
     * Get formatted form data with labels for display.
     * Assumes form_structure is stored on the related Activity.
     */
    public function getFormattedFormData(): array
    {
        if (
            empty($this->form_data) ||
            ! $this->activity ||
            empty($this->activity->form_structure)
        ) {
            return [];
        }

        $formatted = [];
        $structure = $this->activity->form_structure; // The Builder structure array

        foreach ($structure as $block) {
            $fieldName = $block['data']['name'] ?? null;
            $fieldLabel =
                $block['data']['label'] ??
                Str::title(str_replace('_', ' ', $fieldName)); // Fallback label

            if ($fieldName && isset($this->form_data[$fieldName])) {
                $value = $this->form_data[$fieldName];

                // Handle potential array values from checkboxes/multi-select
                if (is_array($value)) {
                    // If options were KeyValue, map values back to labels if possible
                    $options = $block['data']['options'] ?? [];
                    if (! empty($options)) {
                        $labels = [];
                        $optionMap = array_column($options, 'label', 'value'); // Map value => label
                        foreach ($value as $singleValue) {
                            $labels[] =
                                $optionMap[$singleValue] ?? $singleValue; // Use label if found, else value
                        }
                        $value = $labels; // Replace value array with label array
                    }
                } elseif (
                    ($block['type'] === 'select' ||
                        $block['type'] === 'radio') &&
                    ! ($block['data']['multiple'] ?? false)
                ) {
                    // Handle single select/radio - map value to label
                    $options = $block['data']['options'] ?? [];
                    if (! empty($options)) {
                        $optionMap = array_column($options, 'label', 'value');
                        $value = $optionMap[$value] ?? $value; // Use label if found
                    }
                }

                $formatted[] = [
                    'name' => $fieldName,
                    'label' => $fieldLabel,
                    'value' => $value,
                ];
            }
        }

        return $formatted;
    }
}
