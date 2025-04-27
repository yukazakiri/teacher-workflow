<?php

namespace App\Livewire;

use App\Models\ClassResource;
use App\Models\ResourceCategory;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClassResourcesBrowser extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $selectedCategory = null;

    public ?string $viewMode = 'all'; // 'all', 'teacher', 'student'

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => ''],
        'viewMode' => ['except' => 'all'],
    ];

    public function mount()
    {
        // Initialize component
        $this->viewMode = 'all';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory()
    {
        $this->resetPage();
    }

    public function updatedViewMode()
    {
        $this->resetPage();
        $this->selectedCategory = null;
    }

    public function getResourcesProperty()
    {
        $team = Filament::getTenant();
        $user = Auth::user();

        $query = ClassResource::query()
            ->where('team_id', $team->id)
            ->with(['category', 'creator', 'media']);

        // Filter by view mode (teacher materials vs student resources)
        if ($this->viewMode !== 'all') {
            $categoryType = $this->viewMode === 'teacher' ? 'teacher_material' : 'student_resource';
            $query->whereHas('category', function ($q) use ($categoryType): void {
                $q->where('type', $categoryType);
            });
        }

        // Filter by category if selected
        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        // Filter by search term
        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Filter by access level
        $query->where(function ($q) use ($user, $team): void {
            // All resources accessible to everyone
            $q->where('access_level', 'all');

            // Add teacher-accessible resources if user is a teacher
            if ($team->hasUserWithRole($user, 'teacher')) {
                $q->orWhere('access_level', 'teacher');
            }

            // Add owner-accessible resources if user is the owner
            if ($team->user_id === $user->id) {
                $q->orWhere('access_level', 'owner');
            }
        });

        return $query->latest()->paginate(12);
    }

    public function getCategoriesProperty()
    {
        $team = Filament::getTenant();
        $user = Auth::user();

        $query = ResourceCategory::where('team_id', $team->id);

        // Filter categories based on view mode
        if ($this->viewMode !== 'all') {
            $categoryType = $this->viewMode === 'teacher' ? 'teacher_material' : 'student_resource';
            $query->where('type', $categoryType);
        }

        // For non-teachers, only show student resource categories
        if (! $team->hasUserWithRole($user, 'teacher') && $team->user_id !== $user->id) {
            $query->where('type', 'student_resource');
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTeacherCategoriesCountProperty()
    {
        $team = Filament::getTenant();

        return ResourceCategory::where('team_id', $team->id)
            ->where('type', 'teacher_material')
            ->count();
    }

    public function getStudentCategoriesCountProperty()
    {
        $team = Filament::getTenant();

        return ResourceCategory::where('team_id', $team->id)
            ->where('type', 'student_resource')
            ->count();
    }

    public function downloadResource(ClassResource $resource)
    {
        // Check if user can access this resource
        if (! $resource->canBeAccessedBy(Auth::user())) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to access this resource.',
            ]);

            return;
        }

        // Get the first media item
        $media = $resource->getFirstMedia('files');

        if ($media) {
            return redirect()->to($media->getTemporaryUrl(now()->addMinutes(5)));
        }

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'No file found for this resource.',
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = Filament::getTenant();
        $isTeacher = $team->hasUserWithRole($user, 'teacher') || $team->user_id === $user->id;

        return view('livewire.class-resources-browser', [
            'resources' => $this->resources,
            'categories' => $this->categories,
            'isTeacher' => $isTeacher,
            'teacherCategoriesCount' => $this->getTeacherCategoriesCountProperty(),
            'studentCategoriesCount' => $this->getStudentCategoriesCountProperty(),
        ]);
    }
}
