<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'reply_to_id',
        'is_pinned',
        'edited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($message): void {
            // When deleting a message, detach mentions
            $message->mentions()->detach();
            
            // When force deleting, also delete attachments and reactions
            if (method_exists($message, 'isForceDeleting') && $message->isForceDeleting()) {
                $message->attachments->each(function ($attachment): void {
                    $attachment->delete();
                });
                
                $message->reactions->each(function ($reaction): void {
                    $reaction->delete();
                });
            }
        });
    }

    /**
     * Get the channel that owns the message.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the message this message is replying to.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Get the replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Get the reactions for the message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get the users mentioned in the message.
     */
    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_mentions')
            ->withTimestamps();
    }

    /**
     * Check if a user can manage (edit/delete) the message.
     */
    public function canManage(User $user): bool
    {
        // Message owners can manage their own messages
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // Team owners and channel admins can manage any message in channels they manage
        return $this->channel->canManage($user);
    }
}
