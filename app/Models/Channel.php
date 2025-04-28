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
        "team_id",
        "category_id",
        "name",
        "slug",
        "description",
        "type",
        "is_private",
        "is_dm", // Added is_dm
        "position",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "is_private" => "boolean",
        "is_dm" => "boolean", // Added is_dm cast
        "position" => "integer",
        "deleted_at" => "datetime",
    ];

    /**
     * Channel types available in the system.
     */
    public const TYPES = [
        "text" => "Text",
        "announcement" => "Announcement",
        "voice" => "Voice",
        "media" => "Media",
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Channel $channel): void {
            // Handle slug generation differently for DMs vs regular channels
            if ($channel->is_dm) {
                // Slug for DMs will be set explicitly in findOrCreateDirectMessage.
                // If somehow created without a slug, generate a fallback unique one.
                if (empty($channel->slug)) {
                    // This fallback should ideally not be hit if findOrCreateDirectMessage is used.
                    $channel->slug = "dm-" . uniqid();
                }
            } elseif (empty($channel->slug) && !empty($channel->name)) {
                // Generate slug from name for regular channels only if name exists
                $channel->slug = Str::slug($channel->name);
            }

            // Default position logic - only apply to non-DMs
            if (!$channel->is_dm && is_null($channel->position)) {
                $lastPosition = self::where("team_id", $channel->team_id)
                    ->where("category_id", $channel->category_id)
                    ->where("is_dm", false) // Exclude DMs from position calculation
                    ->max("position");
                $channel->position = $lastPosition ? $lastPosition + 1 : 0;
            }
        });

        static::deleting(function (Channel $channel): void {
            // When deleting a channel, also detach all members
            $channel->members()->detach();

            // If force deleting, consider deleting related messages
            if (
                method_exists($channel, "isForceDeleting") &&
                $channel->isForceDeleting()
            ) {
                $channel->messages()->forceDelete(); // Example: force delete messages
            }
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
        // Category is nullable for DMs
        return $this->belongsTo(ChannelCategory::class, "category_id");
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
        return $this->belongsToMany(User::class, "channel_members")
            ->withPivot("permissions") // Permissions might not be relevant for DMs
            ->withTimestamps();
    }

    /**
     * Check if a user is a member of the channel.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where("user_id", $user->id)->exists();
    }

    /**
     * Check if a user can access the channel.
     */
    /**
     * Check if a user can access the channel.
     */
    public function canAccess(User $user): bool
    {
        // Get the user's role in the channel's team context
        $userRole = $user->teamRole($this->team); // Assumes User->teamRole(Team $team) method exists

        // --- Parent Role Restrictions ---
        if ($userRole === "parent") {
            // Parents can ONLY access DMs they are part of.
            if ($this->is_dm) {
                // Check if the parent is one of the two members of this DM
                return $this->members()->where("user_id", $user->id)->exists();
            } else {
                // Parents cannot access any non-DM channels (public or private)
                return false;
            }
        }

        // --- Other Roles (Teachers, Students, Admins etc.) ---

        // If it's a DM channel (and user is not a parent), check membership
        if ($this->is_dm) {
            return $this->hasMember($user); // Use existing hasMember check
        }

        // If the channel is public (and not a DM), any team member (non-parent handled above) can access
        if (!$this->is_private) {
            // Ensure the user actually belongs to the team
            // The teamRole check implicitly does this, but belt-and-suspenders is okay.
            return $user->belongsToTeam($this->team);
        }

        // If the channel is private (and not a DM), only explicit members can access
        return $this->hasMember($user);
    }
    /**
     * Check if a user can manage (edit/delete) the channel.
     * NOTE: DMs should generally not be manageable in the same way as channels.
     */
    public function canManage(User $user): bool
    {
        // Disallow managing DMs through this standard check
        if ($this->is_dm) {
            return false;
        }

        // Team owners can manage any (non-DM) channel
        if ($this->team->user_id === $user->id) {
            return true;
        }

        // Channel admin permissions check (for non-DM channels)
        $member = $this->members()->where("user_id", $user->id)->first();
        if ($member && isset($member->pivot->permissions)) {
            return str_contains($member->pivot->permissions, "manage");
        }

        return false;
    }

    /**
     * Validate channel name uniqueness within a team/category.
     * NOTE: This is not relevant for DMs as they don't have user-facing names.
     */
    public static function validateUniqueName(
        string $name,
        string $teamId,
        ?string $categoryId, // Category ID can be null
        ?string $excludeChannelId = null
    ): bool {
        $query = self::where("team_id", $teamId)
            ->where("name", $name)
            ->where("is_dm", false); // Only check against non-DM channels

        // Only scope by category if one is provided
        if ($categoryId) {
            $query->where("category_id", $categoryId);
        } else {
            // Handle channels without a category if that's possible in your logic
            // $query->whereNull('category_id');
        }

        if ($excludeChannelId) {
            $query->where("id", "!=", $excludeChannelId);
        }

        return !$query->exists();
    }

    /**
     * Find or create a direct message channel between two users.
     */
    public static function findOrCreateDirectMessage(
        User $user1,
        User $user2
    ): Channel {
        // Ensure consistent ordering of user IDs to avoid duplicate DM channels
        $userIds = collect([$user1->id, $user2->id])
            ->sort()
            ->values();
        $uniqueDmSlug = "dm-" . $userIds[0] . "-" . $userIds[1]; // Create predictable slug

        // Look for an existing DM channel using the unique slug within the team
        $channel = Channel::where("team_id", $user1->currentTeam->id)
            ->where("slug", $uniqueDmSlug)
            ->where("is_dm", true)
            ->first();

        // Alternatively, check based on members if slug might not exist yet (e.g., legacy data)
        if (!$channel) {
            $channel = Channel::where("team_id", $user1->currentTeam->id)
                ->where("is_dm", true)
                ->whereHas(
                    "members",
                    function ($query) use ($userIds) {
                        $query->whereIn("user_id", $userIds);
                    },
                    "=",
                    2
                )
                ->whereHas("members", function ($query) use ($userIds) {
                    $query->where("user_id", $userIds[0]);
                })
                ->whereHas("members", function ($query) use ($userIds) {
                    $query->where("user_id", $userIds[1]);
                })
                ->first();
        }

        if ($channel) {
            // Ensure the slug is set correctly if found via member check
            if ($channel->slug !== $uniqueDmSlug) {
                $channel->slug = $uniqueDmSlug;
                $channel->saveQuietly(); // Save without triggering events
            }
            return $channel;
        }

        // If not found, create a new DM channel
        $newChannel = Channel::create([
            "team_id" => $user1->currentTeam->id,
            "is_dm" => true,
            "is_private" => true, // DMs are inherently private
            "slug" => $uniqueDmSlug, // Set the unique DM slug explicitly
            // name, description, category_id, position are nullable or not relevant
        ]);

        // Attach both users as members
        // Note: Permissions might not be needed/relevant for DMs
        $newChannel->members()->attach([$user1->id, $user2->id]);

        return $newChannel;
    }
}
