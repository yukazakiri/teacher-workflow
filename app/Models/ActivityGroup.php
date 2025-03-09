<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityGroup extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'activity_id',
        'name',
        'description',
    ];

    /**
     * Get the activity that owns the group.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the student assignments for the group.
     */
    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentGroupAssignment::class);
    }

    /**
     * Get the submissions for the group.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    /**
     * Get the students assigned to this group.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'student_group_assignments', 'activity_group_id', 'student_id')
            ->withPivot('group_role_id')
            ->withTimestamps();
    }
}
