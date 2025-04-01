<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

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
        'file',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['category'];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Handle media processing after resource creation
        static::created(function ($resource) {
            if (!empty($resource->file) && file_exists($resource->file)) {
                try {
                    $fileName = pathinfo($resource->file, PATHINFO_BASENAME);
                    $fileNameWithoutExtension = pathinfo($resource->file, PATHINFO_FILENAME);
                    
                    $media = $resource->addMedia($resource->file)
                        ->usingName($fileNameWithoutExtension)
                        ->withCustomProperties([
                            'original_filename' => $fileName,
                            'team_id' => $resource->team_id
                        ])
                        ->toMediaCollection('resources');

                    // Ensure the media is using the public disk
                    if ($media->disk !== 'public') {
                        $media->disk = 'public';
                        $media->save();
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to add media in boot: ' . $e->getMessage());
                }
            }
        });

        // Extract metadata when media is added
        static::updated(function ($resource) {
            // Process metadata from media files
            $resource->getMedia('resources')->each(function ($media) use ($resource) {
                // Process file metadata if description is empty or processing
                if (empty($resource->description) || $resource->description === 'Processing...') {
                    // Extract metadata from file
                $fileName = $media->file_name;
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                
                    // Generate title from filename if not set
                    if (empty($resource->title)) {
                $title = str_replace(['-', '_'], ' ', $fileNameWithoutExtension);
                        $resource->title = ucwords($title);
                    }
                    
                    // Default description
                    $description = "Document: " . $resource->title;
                
                // For PDF files, try to extract more metadata
                if (strtolower($fileExtension) === 'pdf') {
                    try {
                        // Get the file path
                        $filePath = $media->getPath();
                        
                        // Parse PDF
                        $parser = new Parser();
                        $pdf = $parser->parseFile($filePath);
                        
                        // Extract details
                        $details = $pdf->getDetails();
                        
                        // Build description from available metadata
                        $descriptionParts = [];
                        
                        if (isset($details['Title']) && !empty($details['Title'])) {
                            $resource->title = $details['Title'];
                        }
                        
                        if (isset($details['Subject']) && !empty($details['Subject'])) {
                            $descriptionParts[] = $details['Subject'];
                        }
                        
                        if (isset($details['Keywords']) && !empty($details['Keywords'])) {
                            $descriptionParts[] = "Keywords: " . $details['Keywords'];
                        }
                        
                        if (isset($details['Author']) && !empty($details['Author'])) {
                            $descriptionParts[] = "Author: " . $details['Author'];
                        }
                        
                        if (isset($details['Creator']) && !empty($details['Creator'])) {
                            $descriptionParts[] = "Created with: " . $details['Creator'];
                        }
                        
                        if (isset($details['CreationDate']) && !empty($details['CreationDate'])) {
                            $descriptionParts[] = "Created on: " . $details['CreationDate'];
                        }
                        
                        // Set the description
                        if (!empty($descriptionParts)) {
                            $description = implode("\n", $descriptionParts);
                        }
                    } catch (\Exception $e) {
                        // If PDF parsing fails, just use the filename
                            \Illuminate\Support\Facades\Log::error('PDF parsing error: ' . $e->getMessage());
                            $description = "Document: " . $resource->title;
                        }
                }
                
                    // Set description
                    $resource->description = $description;
                    
                    // Save without triggering events to avoid recursion
                    $resource->saveQuietly();
                }
            });
        });
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
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('resources')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain'
            ]);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->height(200)
            ->fit('crop', 200, 200)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(400)
            ->height(400)
            ->fit('contain', 400, 400)
            ->nonQueued();
    }

    /**
     * Get the path generator class.
     */
    public function getPathGenerator(): string
    {
        return \App\Support\MediaLibrary\CustomPathGenerator::class;
    }

    /**
     * Get the URL of the media file.
     */
    public function getMediaUrl(): ?string
    {
        $media = $this->getFirstMedia('resources');
        if (!$media) {
            return null;
        }

        // Ensure we're using the correct disk
        if ($media->disk !== 'public') {
            $media->disk = 'public';
            $media->save();
        }

        return $media->getUrl();
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