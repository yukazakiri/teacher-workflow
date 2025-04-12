<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];
    
    /**
     * Common emojis that can be used for reactions.
     * 
     * @var array<string>
     */
    public const COMMON_EMOJIS = [
        '👍', '👎', '😄', '🎉', '😕', '❤️', '🚀', '👀'
    ];

    /**
     * Get the message that owns the reaction.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user that owns the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope a query to get reactions with counts.
     */
    public function scopeWithCounts($query, string $messageId)
    {
        return $query->where('message_id', $messageId)
            ->select('emoji')
            ->selectRaw('count(*) as count')
            ->groupBy('emoji')
            ->orderByDesc('count');
    }
    
    /**
     * Check if a user can toggle this reaction.
     */
    public function canToggle(User $user): bool
    {
        // Users can toggle their own reactions
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // Team owners and channel admins can remove any reaction
        return $this->message->channel->canManage($user);
    }
    
    /**
     * Toggle a reaction for a user on a message.
     */
    public static function toggle(string $messageId, string $userId, string $emoji): bool
    {
        $reaction = self::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();
            
        if ($reaction) {
            $reaction->delete();
            return false; // Reaction removed
        } else {
            self::create([
                'message_id' => $messageId,
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
            return true; // Reaction added
        }
    }
}
