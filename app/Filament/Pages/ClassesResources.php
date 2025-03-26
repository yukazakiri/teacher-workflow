<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use App\Models\ResourceCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use App\Filament\Resources\ClassResource;
use Filament\Support\Facades\FilamentIcon;
use App\Models\ClassResource as ModelsClassResource;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Gate;

class ClassesResources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.pages.class-resources';

    protected static ?string $navigationGroup = 'Class Resources';

    protected static ?int $navigationSort = 19;

    protected ?string $heading = 'Teaching Resources Hub';

    protected ?string $subheading = 'Organize, discover, and share your teaching materials in one central location.';
    
    public ?array $data = [];
    
    // Filter state
    public ?string $selectedType = null;
    public ?string $searchQuery = '';
    public ?string $selectedAccessLevel = null;
    
    public function mount(): void
    {
        // Initialize filter state
        $this->selectedType = request()->query('type', null);
        $this->selectedAccessLevel = request()->query('access', null);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('manage_categories')
                ->label('Manage Categories')
                ->url(route('filament.app.resources.resource-categories.index', ['tenant' => Filament::getTenant()]))
                ->icon('heroicon-o-tag')
                ->color('secondary')
                ->visible(fn() => Gate::allows('manageCategories', ModelsClassResource::class)),
                
            \Filament\Actions\Action::make('add_resource')
                ->label('Add New Resource')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn() => Gate::allows('create', ModelsClassResource::class))
                ->modalHeading('Upload New Resource')
                ->modalDescription('Upload a new resource to share with your team. The title and description will be automatically generated from the file metadata.')
                ->modalSubmitActionLabel('Upload Resource')
                ->form([
                    Section::make()
                        ->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('Document File')
                                ->disk('public')
                                ->directory('class-resources/' . auth()->user()->currentTeam->id)
                                ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'])
                                ->helperText('Upload PDF, Word, Excel, PowerPoint, or image files')
                                ->required()
                                ->maxSize(10240)
                                ->preserveFilenames(),
                                
                            Select::make('category_id')
                                ->label('Category')
                                ->options(function() {
                                    return ResourceCategory::where('team_id', auth()->user()->currentTeam->id)
                                        ->pluck('name', 'id');
                                })
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                    Forms\Components\Textarea::make('description'),
                                    Forms\Components\Select::make('type')
                                        ->options([
                                            'teaching' => 'Teaching Materials',
                                            'student' => 'Student Resources',
                                            'admin' => 'Administrative Documents',
                                        ])
                                        ->default('teaching')
                                        ->required(),
                                ])
                                ->searchable()
                                ->preload(),
                                
                            Select::make('access_level')
                                ->label('Access Level')
                                ->options([
                                    'all' => 'All Team Members',
                                    'teacher' => 'Teachers Only',
                                    'owner' => 'Team Owner Only',
                                ])
                                ->default('all')
                                ->required()
                                ->helperText('Who can access this resource'),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    $team = Filament::getTenant();
                    $user = Auth::user();
                    
                    // Get the file path
                    $filePath = $data['file'];
                    
                    // Get file information for title generation
                    $fileName = pathinfo($filePath, PATHINFO_BASENAME);
                    $fileNameWithoutExtension = pathinfo($filePath, PATHINFO_FILENAME);
                    $tempTitle = str_replace(['-', '_'], ' ', $fileNameWithoutExtension);
                    $tempTitle = ucwords($tempTitle);
                    
                    // Create the resource
                    $resource = new ModelsClassResource();
                    $resource->team_id = $team->id;
                    $resource->created_by = $user->id;
                    $resource->category_id = $data['category_id'] ?? null;
                    $resource->access_level = $data['access_level'];
                    $resource->title = $tempTitle;
                    $resource->description = 'Processing...';
                    $resource->file = $filePath; // Store the file path
                    $resource->save();
                    
                    // Add the media file
                    $storage_path = storage_path('app/public/' . $filePath);
                    
                    // Debug information
                    Log::info('File upload information:', [
                        'filePath' => $filePath,
                        'storage_path' => $storage_path,
                        'exists' => file_exists($storage_path),
                        'public_url' => asset('storage/' . $filePath)
                    ]);
                    
                    if (file_exists($storage_path)) {
                        try {
                            $media = $resource->addMedia($storage_path)
                                ->usingName($fileNameWithoutExtension)
                                ->withCustomProperties(['original_filename' => $fileName])
                                ->toMediaCollection('resources', 'public');
                                
                            // Log media path for debugging
                            Log::info('Media added:', [
                                'id' => $media->id,
                                'url' => $media->getUrl(),
                                'path' => $media->getPath(),
                                'filename' => $media->file_name,
                                'collection' => $media->collection_name,
                                'disk' => $media->disk,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Error adding media:', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        Log::error('File not found:', ['path' => $storage_path]);
                    }
                    
                    // Notification
                    Notification::make()
                        ->title('Resource created successfully')
                        ->success()
                        ->send();
                        
                    // Refresh the resources list
                    $this->dispatch('resource-created');
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Teaching Resources';
    }

    public function getTitle(): string
    {
        return 'Teaching Resources';
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    #[On('resource-created')]
    public function refreshResources(): void
    {
        // This method will be called when the resource-created event is dispatched
        // The blade view will re-render with fresh data
    }
    
    #[On('toggle-pinned')]
    public function togglePinned(string $resourceId): void
    {
        $resource = ModelsClassResource::findOrFail($resourceId);
        
        // Check authorization
        if (!Gate::allows('update', $resource)) {
            Notification::make()
                ->title('Permission denied')
                ->danger()
                ->send();
            return;
        }
        
        $resource->is_pinned = !$resource->is_pinned;
        $resource->save();
        
        $status = $resource->is_pinned ? 'pinned' : 'unpinned';
        
        Notification::make()
            ->title("Resource {$status} successfully")
            ->success()
            ->send();
    }
    
    #[On('filter-changed')]
    public function applyFilter(string $type = null, string $access = null): void
    {
        $this->selectedType = $type;
        $this->selectedAccessLevel = $access;
    }
    
    #[On('search')]
    public function search(string $query): void
    {
        $this->searchQuery = $query;
    }
    
    public function getViewData(): array
    {
        $team = Filament::getTenant();
        $user = Auth::user();
        
        // Define resource types
        $resourceTypes = [
            'teaching' => [
                'title' => 'Teaching Materials',
                'icon' => 'heroicon-o-academic-cap',
                'color' => 'blue',
                'description' => 'Lesson plans, worksheets, and other materials for teaching and classroom instruction.',
                'examples' => 'Lesson plans, Worksheets, Presentations, Rubrics, Study guides',
            ],
            'student' => [
                'title' => 'Student Resources',
                'icon' => 'heroicon-o-user-group',
                'color' => 'green',
                'description' => 'Handouts, guides, and resources designed for student use and learning.',
                'examples' => 'Handouts, Reading materials, Practice exercises, Reference guides, Project templates',
            ],
            'admin' => [
                'title' => 'Administrative Documents',
                'icon' => 'heroicon-o-document-text',
                'color' => 'purple',
                'description' => 'Forms, policies, and administrative documents for school management.',
                'examples' => 'Forms, Policies, Schedules, Reports, Templates',
            ],
        ];
        
        // Build the base queries with proper permissions
        $resourceQuery = ModelsClassResource::query()
            ->where('team_id', $team->id);
        
        // Apply permission filters based on authenticated user
        if ($team->userIsOwner($user)) {
            // Team owners can see everything
        } elseif ($user->hasTeamRole($team, 'teacher')) {
            // Teachers can see their own resources, all public resources, and teacher-level resources
            $resourceQuery->where(function ($query) use ($user) {
                $query->where('access_level', 'all')
                    ->orWhere('access_level', 'teacher')
                    ->orWhere(function ($query) use ($user) {
                        $query->where('created_by', $user->id);
                    });
            });
        } else {
            // Students/others can only see public resources
            $resourceQuery->where('access_level', 'all');
        }
        
        // Apply search filter
        if (!empty($this->searchQuery)) {
            $searchQuery = $this->searchQuery;
            $resourceQuery->where(function ($query) use ($searchQuery) {
                $query->where('title', 'like', "%{$searchQuery}%")
                    ->orWhere('description', 'like', "%{$searchQuery}%");
            });
        }
        
        // Apply type filter through categories
        if (!empty($this->selectedType)) {
            $categoryIds = ResourceCategory::where('team_id', $team->id)
                ->where('type', $this->selectedType)
                ->pluck('id');
                
            $resourceQuery->whereIn('category_id', $categoryIds);
        }
        
        // Apply access level filter
        if (!empty($this->selectedAccessLevel)) {
            $resourceQuery->where('access_level', $this->selectedAccessLevel);
        }
        
        // Get categories with permission-filtered resources
        $categories = ResourceCategory::query()
            ->where('team_id', $team->id)
            ->orderBy('sort_order')
            ->with(['resources' => function ($query) use ($user, $team, $resourceQuery) {
                // Clone the main resource query conditions for proper permission filtering
                $baseQuery = clone $resourceQuery;
                $query->whereIn('id', $baseQuery->pluck('id'))
                    ->orderBy('created_at', 'desc')
                    ->with(['media', 'creator', 'team']);
            }])
            ->get();
            
        // Get uncategorized resources with permissions
        $uncategorizedQuery = clone $resourceQuery;
        $uncategorizedResources = $uncategorizedQuery
            ->whereNull('category_id')
            ->orderBy('created_at', 'desc')
            ->with(['media', 'category', 'creator', 'team'])
            ->get();
            
        // Get recent resources with permissions
        $recentQuery = clone $resourceQuery;
        $recentResources = $recentQuery
            ->orderBy('created_at', 'desc')
            ->with(['media', 'category', 'creator', 'team'])
            ->limit(10)
            ->get();
            
        // Get pinned/favorite resources
        $pinnedQuery = clone $resourceQuery;
        $pinnedResources = $pinnedQuery
            ->where('is_pinned', true)
            ->orderBy('created_at', 'desc')
            ->with(['media', 'category', 'creator', 'team'])
            ->get();
            
        // Group categories by type
        $categoriesByType = [];
        foreach ($categories as $category) {
            $type = $category->type ?? 'teaching';
            if (!isset($categoriesByType[$type])) {
                $categoriesByType[$type] = collect();
            }
            $categoriesByType[$type]->push($category);
        }
        
        // Get resources by category (already filtered by permissions)
        $resourcesByCategory = [];
        foreach ($categories as $category) {
            if ($category->resources->count() > 0) {
                $resourcesByCategory[$category->id] = $category->resources;
            }
        }
        
        // Calculate stats
        $resourceStats = [
            'total' => $resourceQuery->count(),
            'teaching' => ResourceCategory::where('team_id', $team->id)
                ->where('type', 'teaching')
                ->withCount(['resources' => function($query) use ($resourceQuery) {
                    $query->whereIn('id', $resourceQuery->pluck('id'));
                }])
                ->get()->sum('resources_count'),
            'student' => ResourceCategory::where('team_id', $team->id)
                ->where('type', 'student')
                ->withCount(['resources' => function($query) use ($resourceQuery) {
                    $query->whereIn('id', $resourceQuery->pluck('id'));
                }])
                ->get()->sum('resources_count'),
            'admin' => ResourceCategory::where('team_id', $team->id)
                ->where('type', 'admin')
                ->withCount(['resources' => function($query) use ($resourceQuery) {
                    $query->whereIn('id', $resourceQuery->pluck('id'));
                }])
                ->get()->sum('resources_count'),
            'uncategorized' => $uncategorizedResources->count(),
        ];
            
        return [
            'categories' => $categoriesByType,
            'resourcesByCategory' => $resourcesByCategory,
            'uncategorizedResources' => $uncategorizedResources,
            'recentResources' => $recentResources,
            'pinnedResources' => $pinnedResources,
            'resourceTypes' => $resourceTypes,
            'resourceStats' => $resourceStats,
            'selectedType' => $this->selectedType,
            'selectedAccessLevel' => $this->selectedAccessLevel,
            'searchQuery' => $this->searchQuery,
            'canManageCategories' => Gate::allows('manageCategories', ModelsClassResource::class),
            'canCreateResources' => Gate::allows('create', ModelsClassResource::class),
            'user' => $user,
            'team' => $team,
            'resourceViewUrl' => function($resourceId) {
                return ClassResource::getUrl('view', ['record' => $resourceId]);
            },
        ];
    }
}