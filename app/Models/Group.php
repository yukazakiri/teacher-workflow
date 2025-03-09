<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Group extends Model
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
     * Get the activity that this group belongs to.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the members (students) of this group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withTimestamps();
    }

    /**
     * Get the role assignments for this group.
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(GroupRoleAssignment::class);
    }

    /**
     * Get the submissions for this group.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }
}
