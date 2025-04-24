<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ClassResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class DirectResourceUploader extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static string $view = 'filament.pages.direct-resource-uploader';

    protected static ?string $navigationGroup = 'Class Resources';

    protected static ?int $navigationSort = 21; // After the quick uploader

    protected static ?string $title = 'Direct Upload';

    protected ?string $heading = 'Direct File Upload';

    protected ?string $subheading = 'Alternative upload method if the regular uploader is not working.';

    // Form properties
    public $resourceTitle = '';
    public $file = null;
    public $access_level = 'all';

    // Validation rules
    protected function rules()
    {
        return [
            'resourceTitle' => 'required|string|max:255',
            'file' => 'required|file|max:51200', // 50MB
            'access_level' => 'required|in:all,teacher,owner',
        ];
    }

    // Real-time validation
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    // Handle file selection to auto-populate title from filename
    public function updatedFile()
    {
        try {
            if ($this->file && empty($this->resourceTitle)) {
                $filename = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
                $this->resourceTitle = Str::title(str_replace(['-', '_'], ' ', $filename));
            }
        } catch (\Exception $e) {
            Log::warning('Error auto-populating title', ['error' => $e->getMessage()]);
        }
    }

    // Process the file upload
    public function upload()
    {
        try {
            $this->validate();
            
            $team = Filament::getTenant();
            $user = Auth::user();
            
            Log::debug('Direct Upload process started', [
                'file_name' => $this->file ? $this->file->getClientOriginalName() : 'No file',
                'file_size' => $this->file ? $this->file->getSize() : 'No file',
                'file_mime' => $this->file ? $this->file->getMimeType() : 'No file',
            ]);

            // 1. Create resource record
            $resource = ClassResource::create([
                'team_id' => $team->id,
                'title' => $this->resourceTitle,
                'description' => null,
                'category_id' => null,
                'access_level' => $this->access_level,
                'created_by' => $user->id,
            ]);
            
            Log::info('Resource record created (Direct Upload)', ['resource_id' => $resource->id]);

            // 2. Handle file upload - using direct method instead of disk
            if ($this->file) {
                try {
                    // Store directly using addMedia instead of addMediaFromDisk
                    $resource->addMedia($this->file->getRealPath())
                             ->usingName($this->resourceTitle)
                             ->usingFileName($this->file->getClientOriginalName())
                             ->toMediaCollection('resources');
                    
                    Log::info('Successfully added media to collection via direct method', ['resource_id' => $resource->id]);
                    
                } catch (\Exception $mediaError) {
                    Log::error('Error adding media via direct method', [
                        'resource_id' => $resource->id,
                        'error' => $mediaError->getMessage(),
                        'file_path' => $this->file->getRealPath(),
                        'trace' => Str::limit($mediaError->getTraceAsString(), 500)
                    ]);
                    
                    Notification::make()
                        ->title('Upload Failed')
                        ->body('Could not store the file: ' . $mediaError->getMessage())
                        ->danger()
                        ->send();
                        
                    return;
                }
            }

            // Success notification
            Notification::make()
                ->title('Resource uploaded successfully')
                ->success()
                ->send();

            // Reset form
            $this->reset(['resourceTitle', 'file', 'access_level']);
            $this->access_level = 'all'; // Reset to default
            
            // Dispatch event for any listeners
            $this->dispatch('resource-created');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Direct upload validation failed', ['errors' => $e->errors()]);
            // Validation errors shown automatically by Livewire
        } catch (\Exception $e) {
            Log::error('General error during direct upload', [
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 500)
            ]);
            
            Notification::make()
                ->title('An unexpected error occurred')
                ->body('Could not upload the resource. Please try again later.')
                ->danger()
                ->send();
        }
    }
} 