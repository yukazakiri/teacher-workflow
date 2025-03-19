<?php

namespace App\Livewire;

use App\Models\ClassResource;
use App\Models\ResourceCategory;
use App\Models\Team;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClassResourcesBrowser extends Component
{
    use WithPagination;
    
    public ?string $search = '';
    
    public ?string $selectedCategory = null;
    
    public function mount()
    {
        // Initialize component
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedSelectedCategory()
    {
        $this->resetPage();
    }
    
    public function getResourcesProperty()
    {
        $team = Filament::getTenant();
        $user = Auth::user();
        
        $query = ClassResource::query()
            ->where('team_id', $team->id)
            ->with(['category', 'creator', 'media']);
            
        // Filter by category if selected
        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }
        
        // Filter by search term
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }
        
        // Filter by access level
        $query->where(function ($q) use ($user, $team) {
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
        
        return ResourceCategory::where('team_id', $team->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
    
    public function downloadResource(ClassResource $resource)
    {
        // Check if user can access this resource
        if (!$resource->canBeAccessedBy(Auth::user())) {
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
        return view('livewire.class-resources-browser', [
            'resources' => $this->resources,
            'categories' => $this->categories,
        ]);
    }
} 