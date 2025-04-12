<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Channel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'category_id',
        'name',
        'slug',
        'description',
        'type',
        'is_private',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_private' => 'boolean',
        'position' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Channel types available in the system.
     */
    public const TYPES = [
        'text' => 'Text',
        'announcement' => 'Announcement',
        'voice' => 'Voice',
        'media' => 'Media',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($channel) {
            if (empty($channel->slug)) {
                $channel->slug = Str::slug($channel->name);
            }
            
            // Default position to the end if not specified
            if (is_null($channel->position)) {
                $lastPosition = self::where('team_id', $channel->team_id)
                    ->where('category_id', $channel->category_id)
                    ->max('position');
                    
                $channel->position = $lastPosition ? $lastPosition + 1 : 0;
            }
        });
        
        static::deleting(function ($channel) {
            // When deleting a channel, also detach all members
            $channel->members()->detach();
        });
    }

    /**
     * Get the team that owns the channel.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the category that owns the channel.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ChannelCategory::class, 'category_id');
    }

    /**
     * Get the messages for the channel.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the members of the channel.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
            ->withPivot('permissions')
            ->withTimestamps();
    }

    /**
     * Check if a user is a member of the channel.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can access the channel.
     */
    public function canAccess(User $user): bool
    {
        // If the channel is not private, any team member can access it
        if (!$this->is_private) {
            return $user->belongsToTeam($this->team);
        }

        // If the channel is private, only channel members can access it
        return $this->hasMember($user);
    }
    
    /**
     * Check if a user can manage (edit/delete) the channel.
     */
    public function canManage(User $user): bool
    {
        // Team owners can manage any channel
        if ($this->team->user_id === $user->id) {
            return true;
        }
        
        // Channel admin permissions check
        $member = $this->members()->where('user_id', $user->id)->first();
        
        if ($member && isset($member->pivot->permissions)) {
            return str_contains($member->pivot->permissions, 'manage');
        }
        
        return false;
    }
    
    /**
     * Validate channel name uniqueness within a team/category.
     */
    public static function validateUniqueName(string $name, string $teamId, string $categoryId, ?string $excludeChannelId = null): bool
    {
        $query = self::where('team_id', $teamId)
            ->where('category_id', $categoryId)
            ->where('name', $name);
            
        if ($excludeChannelId) {
            $query->where('id', '!=', $excludeChannelId);
        }
        
        return !$query->exists();
    }
}
