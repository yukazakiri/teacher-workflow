<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'position',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category): void {
            // Default position to the end if not specified
            if (is_null($category->position)) {
                $lastPosition = self::where('team_id', $category->team_id)
                    ->max('position');
                    
                $category->position = $lastPosition ? $lastPosition + 1 : 0;
            }
        });
        
        static::deleting(function ($category): void {
            // When soft deleting a category, also soft delete its channels
            if (method_exists($category, 'isForceDeleting') && !$category->isForceDeleting()) {
                $category->channels->each(function ($channel): void {
                    $channel->delete();
                });
            }
        });
    }

    /**
     * Get the team that owns the category.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the channels for the category.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class, 'category_id');
    }
    
    /**
     * Check if a user can manage (edit/delete) the category.
     */
    public function canManage(User $user): bool
    {
        // Only team owners can manage categories
        return $this->team->user_id === $user->id;
    }
    
    /**
     * Validate category name uniqueness within a team.
     */
    public static function validateUniqueName(string $name, string $teamId, ?string $excludeCategoryId = null): bool
    {
        $query = self::where('team_id', $teamId)
            ->where('name', $name);
            
        if ($excludeCategoryId) {
            $query->where('id', '!=', $excludeCategoryId);
        }
        
        return !$query->exists();
    }
}
