<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGroupAssignment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'activity_group_id',
        'group_role_id',
    ];

    /**
     * Get the student that owns the assignment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the group that owns the assignment.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ActivityGroup::class, 'activity_group_id');
    }

    /**
     * Get the role that owns the assignment.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(GroupRole::class, 'group_role_id');
    }
}
