<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use App\Models\ResourceCategory;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use App\Filament\Resources\ClassResource;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Facades\FilamentIcon;
use App\Models\ClassResource as ModelsClassResource;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ClassesResources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.class-resources';

    protected static ?string $navigationGroup = 'Class Resources';

    protected static ?int $navigationSort = 19;

    protected ?string $heading = 'Class Resources Hub';

    protected ?string $subheading = 'Access and manage all your classroom resources, organized by type and category.';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        // $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('manage_categories')
                ->label('Manage Categories')
                ->url(route('filament.app.resources.resource-categories.index', ['tenant' => auth()->user()->currentTeam]))
                ->icon('heroicon-o-tag')
                ->color('secondary'),
            \Filament\Actions\Action::make('add_resource')
                ->label('Add New Resource')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Upload New Resource')
                ->modalDescription('Upload a new resource to share with your team. The title and description will be automatically generated from the file metadata.')
                ->modalSubmitActionLabel('Upload Resource')
                ->form([
                    Section::make()
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('files')
                                ->label('Document File')
                                ->collection('resources') // Specify the media collection name
                                ->model(ModelsClassResource::class) // Specify the model class
                                ->directory('class-resources/' . Filament::getTenant()->id)
                                ->disk('public')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'])
                                ->helperText('Upload PDF, Word, Excel, PowerPoint, or image files')
                                ->required()
                                ->maxSize(10240), // 10MB
                                
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
                    
                    // Get the file name for generating a temporary title
                    $tempTitle = 'Untitled Resource';
                    if (!empty($data['files'])) {
                        // If using SpatieMediaLibraryFileUpload, the structure is different
                        if (isset($data['files']['name'])) {
                            $fileName = $data['files']['name'];
                            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                            $tempTitle = str_replace(['-', '_'], ' ', $fileNameWithoutExtension);
                            $tempTitle = ucwords($tempTitle);
                        }
                    }
                    
                    // Create the resource with a temporary title
                    $resource = new ModelsClassResource();
                    $resource->team_id = $team->id;
                    $resource->created_by = $user->id;
                    $resource->access_level = $data['access_level'];
                    $resource->title = $tempTitle; // Set a temporary title to satisfy the not-null constraint
                    $resource->description = 'Processing...'; // Set a temporary description
                    $resource->save();
                    
                    // The SpatieMediaLibraryFileUpload component will automatically
                    // attach the uploaded file to the newly created resource
                    
                    // Show notification
                    Notification::make()
                        ->title('Resource created successfully')
                        ->success()
                        ->send();
                        
                    // Emit event to refresh the resources list
                    $this->dispatch('resource-created');
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Classes Resources';
    }

    public function getTitle(): string
    {
        return 'Classes Resources';
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
        
        // Get categories with resources
        $categories = ResourceCategory::query()
            ->where('team_id', $team->id)
            ->orderBy('sort_order')
            ->with(['resources' => function ($query) use ($user, $team) {
                $query->where(function ($query) use ($user, $team) {
                    // Access level filtering
                    $query->where('access_level', 'all')
                        // ->orWhere(function ($query) use ($user, $team) {
                        //     $query->where('access_level', 'teacher')
                        //         ->whereIn('created_by', $team->teachers->pluck('id'));
                        // })
                        ->orWhere(function ($query) use ($user, $team) {
                            $query->where('access_level', 'owner')
                                ->where('created_by', $team->owner_id);
                        });
                })
                ->orderBy('created_at', 'desc')
                ->with('media');
            }])
            ->get();
            
        // Get uncategorized resources
        $uncategorizedResources = ModelsClassResource::query()
            ->where('team_id', $team->id)
            ->whereNull('category_id')
            ->where(function ($query) use ($user, $team) {
                // Access level filtering
                $query->where('access_level', 'all')
                    ->orWhere(function ($query) use ($user, $team) {
                        $query->where('access_level', 'owner')
                            ->where('created_by', $team->owner_id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->with('media')
            ->get();
            
        // Get recent resources (last 10)
        $recentResources = ModelsClassResource::query()
            ->where('team_id', $team->id)
            ->where(function ($query) use ($user, $team) {
                // Access level filtering
                $query->where('access_level', 'all')
                    ->orWhere(function ($query) use ($user, $team) {
                        $query->where('access_level', 'owner')
                            ->where('created_by', $team->owner_id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->with(['media', 'category'])
            ->limit(10)
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
        
        // Get resources by category
        $resourcesByCategory = [];
        foreach ($categories as $category) {
            if ($category->resources->count() > 0) {
                $resourcesByCategory[$category->id] = $category->resources;
            }
        }
            
        return [
            'categories' => $categoriesByType,
            'resourcesByCategory' => $resourcesByCategory,
            'uncategorizedResources' => $uncategorizedResources,
            'recentResources' => $recentResources,
            'resourceTypes' => $resourceTypes,
            'resourceViewUrl' => function($resourceId) {
                return ClassResource::getUrl('view', ['record' => $resourceId]);
            },
        ];
    }
}