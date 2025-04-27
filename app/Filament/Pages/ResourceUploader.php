<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ClassResource as ModelsClassResource;
// use App\Models\ResourceCategory; // No longer needed here
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
// use Filament\Forms\Components\RichEditor; // No longer needed here
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ResourceUploader extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.pages.resource-uploader';

    protected static ?string $navigationGroup = 'Class Resources';

    protected static ?int $navigationSort = 20; // Place it after the main resources page

    protected static ?string $title = 'Quick Upload Resource'; // Renamed for clarity

    protected ?string $heading = 'Quickly Upload a Resource';

    protected ?string $subheading = 'Just add a title, file, and access level.';

    public ?array $data = []; // Holds form data

    public function mount(): void
    {
        $this->form->fill([
            'access_level' => 'all', // Default access level
        ]);
    }

    public function form(Form $form): Form
    {
        $team = Filament::getTenant();

        return $form
            ->schema([
                 // Combine File Upload and essential details into one section
                 Section::make('Upload Resource')
                    ->schema([
                        FileUpload::make('file')
                            ->label('File')
                            ->disk('public')
                            ->directory('class-resources/' . $team?->id)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'text/plain', 'text/csv', 'text/markdown',
                                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                                'application/msword', // .doc
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                                'application/vnd.ms-powerpoint', // .ppt
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
                                'application/vnd.ms-excel', // .xls
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            ])
                            ->maxSize(51200) // 50MB limit
                            ->helperText('Max file size: 50MB. Accepts common documents, text, and images.')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set, ?string $state, TemporaryUploadedFile $file): void {
                                if ($file && empty($get('title'))) {
                                     $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                     $title = Str::title(str_replace(['-', '_'], ' ', $filename));
                                     $set('title', $title);
                                }
                            })
                            ->columnSpanFull(), // Make file upload span full width initially

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpan(2), // Span 2 columns

                        Select::make('access_level')
                            ->label('Access Level')
                            ->options([
                                'all' => 'All Team Members',
                                'teacher' => 'Teachers Only',
                                'owner' => 'Team Owner Only',
                            ])
                            ->default('all')
                            ->required()
                            ->helperText('Who can access this resource')
                            ->columnSpan(1), // Span 1 column

                    ])->columns(3), // Use 3 columns for Title and Access Level

            ])
            ->statePath('data'); // Ensure data binding
    }

    public function create(): void
    {
        try {
            $this->validate(); // Validate the form data
            $data = $this->form->getState();
            $team = Filament::getTenant();
            $user = Auth::user();

            Log::debug('Resource Quick Uploader Create Triggered', ['data_keys' => array_keys($data)]);

            // 1. Create the resource record (without description/category)
            $resource = ModelsClassResource::create([
                'team_id' => $team->id,
                'title' => $data['title'],
                'description' => null, // Set description to null initially
                'category_id' => null, // Set category to null initially
                'access_level' => $data['access_level'],
                'created_by' => $user->id,
            ]);

            Log::info('Resource record created (Quick Upload)', ['resource_id' => $resource->id]);

            // 2. Handle the file upload using Spatie Media Library
            if (!empty($data['file'])) {
                try {
                    Log::debug('Attempting to add media from file upload field (Quick Upload)', ['resource_id' => $resource->id, 'file_info' => $data['file']]);

                    $resource->addMediaFromDisk($data['file'], 'public')
                             ->toMediaCollection('resources');

                    Log::info('Successfully added media to collection for resource (Quick Upload)', ['resource_id' => $resource->id]);

                    // PDF extraction logic is now in the Model's updated event, will trigger after this.

                } catch (\Exception $mediaError) {
                    Log::error('Error adding media to collection during quick upload', [
                        'resource_id' => $resource->id,
                        'error' => $mediaError->getMessage(),
                        'trace' => Str::limit($mediaError->getTraceAsString(), 1000)
                    ]);
                    // Keep the resource record even if media fails, user can delete.
                    Notification::make()
                        ->title('Upload Failed')
                        ->body('Could not store the uploaded file: ' . $mediaError->getMessage())
                        ->danger()
                        ->send();
                    return; // Stop if media fails
                }
            }

            Notification::make()
                ->title('Resource uploaded successfully')
                ->success()
                ->send();

            $this->form->fill(); // Reset the form
            $this->dispatch('resource-created'); // Dispatch event

        } catch (\Illuminate\Validation\ValidationException $e) {
             Log::warning('Resource quick upload validation failed.', ['errors' => $e->errors()]);
             Notification::make()
                ->title('Validation Error')
                ->body('Please check the form for errors.')
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Log::error('General error during resource quick upload', [
                 'error' => $e->getMessage(),
                 'trace' => Str::limit($e->getTraceAsString(), 1000)
            ]);
            Notification::make()
                ->title('An unexpected error occurred')
                ->body('Could not upload the resource. Please try again later.')
                ->danger()
                ->send();
        }
    }

} 