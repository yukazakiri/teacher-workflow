@php
use Illuminate\Support\Str; 
use Filament\Facades\Filament;
    $primaryMedia = $resource->getFirstMedia('resources');
    $extension = $primaryMedia ? pathinfo($primaryMedia->file_name, PATHINFO_EXTENSION) : '';
    $bgColor = match(strtolower($extension)) {
        'pdf' => 'bg-red-50 dark:bg-red-900/20',
        'doc', 'docx' => 'bg-blue-50 dark:bg-blue-900/20',
        'xls', 'xlsx' => 'bg-green-50 dark:bg-green-900/20',
        'ppt', 'pptx' => 'bg-orange-50 dark:bg-orange-900/20',
        'jpg', 'jpeg', 'png', 'gif' => 'bg-purple-50 dark:bg-purple-900/20',
        default => 'bg-gray-50 dark:bg-gray-800',
    };
    $icon = match(strtolower($extension)) {
        'pdf' => 'heroicon-o-document-text',
        'doc', 'docx' => 'heroicon-o-document',
        'xls', 'xlsx' => 'heroicon-o-table-cells',
        'ppt', 'pptx' => 'heroicon-o-presentation-chart-bar',
        'jpg', 'jpeg', 'png', 'gif' => 'heroicon-o-photo',
        default => 'heroicon-o-paper-clip',
    };
    $iconColor = match(strtolower($extension)) {
        'pdf' => 'text-red-600 dark:text-red-400',
        'doc', 'docx' => 'text-blue-600 dark:text-blue-400',
        'xls', 'xlsx' => 'text-green-600 dark:text-green-400',
        'ppt', 'pptx' => 'text-orange-600 dark:text-orange-400',
        'jpg', 'jpeg', 'png', 'gif' => 'text-purple-600 dark:text-purple-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    
    // Check if any relationship exists before accessing it
    $hasCreator = $resource->relationLoaded('creator') && $resource->creator;
    $hasCategory = $resource->relationLoaded('category') && $resource->category;
@endphp

<div class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition hover:shadow-md flex flex-col h-full">
    <div class="absolute top-2 right-2 z-10">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
            @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
            @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
            @else bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300
            @endif">
            @if($resource->access_level === 'all') All
            @elseif($resource->access_level === 'teacher') Teachers
            @else Owner
            @endif
        </span>
    </div>
    
    @if($resource->is_pinned)
        <div class="absolute top-2 left-2 z-10">
            <span class="inline-flex items-center text-amber-600">
                <x-heroicon-s-star class="h-5 w-5" />
            </span>
        </div>
    @endif
    
    <div class="{{ $bgColor }} p-3 flex justify-center items-center h-24">
        @if($primaryMedia && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
            <img src="{{ $primaryMedia->getUrl('thumbnail') }}" alt="{{ $resource->title }}" class="max-w-full max-h-full rounded object-cover">
        @else
            <x-dynamic-component :component="$icon" class="w-12 h-12 {{ $iconColor }}" />
        @endif
    </div>
    
    <div class="p-3 flex-grow">
        @if($hasCategory)
            <div class="flex items-center mb-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    <x-dynamic-component :component="$resource->category->icon ?? 'heroicon-o-tag'" class="inline-block w-3 h-3 mr-1" />
                    {{ $resource->category->name }}
                </span>
            </div>
        @endif
        
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $resource->title }}</h4>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $resource->description }}</p>
    </div>
    
    <div class="border-t border-gray-200 dark:border-gray-700 p-3 flex items-center justify-between mt-auto">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            @if($hasCreator)
                <span class="inline-flex items-center">
                    <x-heroicon-o-user class="w-3 h-3 mr-1" />
                    {{ Str::limit($resource->creator->name, 15) }}
                </span>
            @else
                {{ $resource->created_at->diffForHumans() }}
            @endif
        </span>
        
        <div class="flex space-x-2">
            @can('update', $resource)
                <button 
                    wire:click="$dispatch('toggle-pinned', { resourceId: '{{ $resource->id }}' })"
                    class="{{ $resource->is_pinned ? 'text-amber-600 hover:text-amber-800 dark:text-amber-500 dark:hover:text-amber-400' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    title="{{ $resource->is_pinned ? 'Unpin resource' : 'Pin resource' }}"
                >
                    <x-heroicon-{{ $resource->is_pinned ? 's' : 'o' }}-star class="w-4 h-4" />
                </button>
                
                <a href="{{ route('filament.app.resources.classes.edit', ['record' => $resource->id, 'tenant' => Filament::getTenant()] )}}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400">
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                </a>
            @endcan
            
            @if($primaryMedia)
                <a href="{{ $primaryMedia->getUrl() }}" 
                   target="_blank" 
                   class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                   title="Download {{ $primaryMedia->file_name }}">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                </a>
            @else
                <a href="{{ route('filament.app.resources.classes.view', ['record' => $resource->id, 'tenant' => Filament::getTenant()]) }}" 
                   class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    <x-heroicon-o-eye class="w-4 h-4" />
                </a>
            @endif
        </div>
    </div>
    
    <!-- Hover overlay -->
    <a href="{{ route('filament.app.resources.classes.view', ['record' => $resource->id, 'tenant' => Filament::getTenant()] )}}" 
       class="absolute inset-0 bg-black/5 dark:bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity"></a>
</div> 