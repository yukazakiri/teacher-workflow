<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
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
        'is_pinned',
        'is_archived',
        'file', // Stores the path to the file in storage
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['category'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Try to extract PDF content after media has been added
        static::updated(function ($resource) {
            if (empty($resource->description)) {
                // Check if resource has PDF media
                $media = $resource->getMedia('resources')->first();
                if ($media && $media->mime_type === 'application/pdf') {
                    try {
                        $filePath = $media->getPath();
                        if (file_exists($filePath)) {
                            $parser = new Parser();
                            $pdf = $parser->parseFile($filePath);
                            
                            // Try to get content
                            $text = $pdf->getText();
                            if (!empty($text)) {
                                $resource->description = Str::limit($text, 1000);
                                $resource->saveQuietly(); // Save without triggering events
                                return;
                            }
                            
                            // If no content, try metadata
                            $details = $pdf->getDetails();
                            $descriptionParts = [];
                            if (isset($details['Subject']) && !empty($details['Subject'])) {
                                $descriptionParts[] = $details['Subject'];
                            }
                            if (isset($details['Keywords']) && !empty($details['Keywords'])) {
                                $descriptionParts[] = "Keywords: " . $details['Keywords'];
                            }
                            if (isset($details['Author']) && !empty($details['Author'])) {
                                $descriptionParts[] = "Author: " . $details['Author'];
                            }
                            
                            if (!empty($descriptionParts)) {
                                $resource->description = implode("\n", $descriptionParts);
                                $resource->saveQuietly(); // Save without triggering events
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('PDF parsing error on create: ' . $e->getMessage());
                    }
                }
            }
        });

        // Media cleanup is automatically handled by Spatie Media Library
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('resources')
            ->singleFile() // Only one file per resource
            ->acceptsMimeTypes([
                'application/pdf', 
                'text/plain',
                'text/csv',
                'text/markdown',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ]);
    }
    
    /**
     * Register any media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Generate thumbnails for images
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->performOnCollections('resources')
            ->nonQueued();
    }

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
     * Get the URL to access the stored file.
     */
    public function getFileUrlAttribute(): ?string
    {
        $media = $this->getMedia('resources')->first();
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get file metadata like size and type.
     */
    public function getFileMetadataAttribute(): ?object
    {
        $media = $this->getMedia('resources')->first();
        
        if (!$media) {
            return null;
        }
        
        try {
            return (object) [
                'size' => $media->size,
                'human_readable_size' => \Illuminate\Support\Number::fileSize($media->size, precision: 2),
                'mime_type' => $media->mime_type,
                'last_modified' => $media->updated_at,
                'file_name' => $media->file_name,
                'extension' => $media->extension,
                'has_thumb' => $media->hasGeneratedConversion('thumb'),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
            ];
        } catch (\Exception $e) {
             \Illuminate\Support\Facades\Log::error('Error getting media metadata: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the user can access this resource.
     */
    public function canBeAccessedBy(User $user): bool
    {
        // Get the team
        $team = $this->team;
        
        // Check if user is a member of the team
        if (!$team || !$user || !$team->hasUser($user)) {
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