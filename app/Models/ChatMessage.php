<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "conversation_id",
        "user_id",
        "role",
        "content",
        "metadata",
        "is_streaming",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "metadata" => "array",
        "is_streaming" => "boolean",
        "created_at" => "datetime", // Ensure Carbon instance
        "updated_at" => "datetime", //
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include user messages.
     */
    public function scopeUser($query)
    {
        return $query->where("role", "user");
    }

    /**
     * Scope a query to only include assistant messages.
     */
    public function scopeAssistant($query)
    {
        return $query->where("role", "assistant");
    }

    /**
     * Scope a query to only include system messages.
     */
    public function scopeSystem($query)
    {
        return $query->where("role", "system");
    }
}
