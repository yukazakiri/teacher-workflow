<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
    ];
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($attachment): void {
            // Delete the physical file when the attachment record is deleted
            if ($attachment->file_path && Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
        });
    }

    /**
     * Get the message that owns the attachment.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
    
    /**
     * Get the URL for the attached file.
     */
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }
    
    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
        ]);
    }
    
    /**
     * Check if the attachment is a video.
     */
    public function isVideo(): bool
    {
        return in_array($this->file_type, [
            'video/mp4', 'video/webm', 'video/ogg'
        ]);
    }
    
    /**
     * Check if the attachment is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->file_type, [
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain'
        ]);
    }
}
