<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'title',
        'model',
        'style',
        'context',
        'last_activity_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that the conversation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the most recent messages for the conversation.
     */
    public function recentMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
            ->orderBy('created_at', 'desc')
            ->limit(50);
    }

    /**
     * Get a truncated title for display.
     */
    public function getTruncatedTitleAttribute(): string
    {
        return Str::limit($this->title, 30);
    }

    /**
     * Update the last activity timestamp.
     */
    public function updateLastActivity(): bool
    {
        $this->last_activity_at = now();
        return $this->save();
    }
}
