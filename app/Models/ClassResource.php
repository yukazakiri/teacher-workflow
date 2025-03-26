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

        // Handle media registration
        static::created(function ($resource) {
            // Process uploaded files after resource creation
            $resource->registerMediaCollections();
        });

        // Extract metadata when media is added
        static::created(function ($resource) {
            $resource->getMedia('resources')->each(function ($media) {
                // Process file metadata
                $fileName = $media->file_name;
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                
                // Generate title from filename
                $title = str_replace(['-', '_'], ' ', $fileNameWithoutExtension);
                $title = ucwords($title);
                
                // Get the resource
                $resource = $media->model;
                
                // Set title if not already set
                if (empty($resource->title)) {
                    $resource->title = $title;
                }
                
                // For PDF files, try to extract more metadata
                $description = "Document: $title";
                
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
                        $description = "Document: $title";
                    }
                }
                
                // Set description if not already set
                if (empty($resource->description)) {
                    $resource->description = $description;
                }
                
                // Save the resource
                $resource->save();
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