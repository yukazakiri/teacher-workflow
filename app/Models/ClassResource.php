<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ClassResource extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'category_id',
        'title',
        'description',
        'access_level', // 'all', 'teacher', 'owner'
        'created_by',
    ];

    /**
     * Get the team that owns the resource.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the category of the resource.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'category_id');
    }

    /**
     * Get the user who created the resource.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')
            ->useDisk('class_resources');
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(100)
            ->height(100)
            ->nonQueued()
            ->performOnCollections('files');
    }

    /**
     * Check if the user can access this resource.
     */
    public function canBeAccessedBy(User $user): bool
    {
        // Get the team
        $team = $this->team;
        
        // Check if user is a member of the team
        if (!$team->hasUser($user)) {
            return false;
        }

        // Owner can access everything
        if ($team->user_id === $user->id) {
            return true;
        }

        // Check access level
        switch ($this->access_level) {
            case 'all':
                return true;
            case 'teacher':
                return $team->hasUserWithRole($user, 'teacher') || $team->user_id === $user->id;
            case 'owner':
                return $team->user_id === $user->id;
            default:
                return false;
        }
    }
} 