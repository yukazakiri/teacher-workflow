@props(['resource', 'viewUrl', 'editUrl', 'isOwner'])

{{-- This is the compact card, similar to the previous grid item --}}
<div wire:key="card-{{ $resource->id }}" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full group transition-all duration-200 hover:shadow-lg relative">
    @php
    $media = $resource->getFirstMedia('resources');
    $fileType = $media ? $media->mime_type : '';
    $fileName = $media ? $media->file_name : '';
    $extension = $media ? pathinfo($fileName, PATHINFO_EXTENSION) : '';
    
    // Define colors and icons based on file type
    if (str_contains($fileType, 'pdf')) { $bgColor = 'bg-red-50 dark:bg-red-900/20'; $icon = 'heroicon-o-document'; $iconColor = 'text-red-500 dark:text-red-400'; }
    elseif (str_contains($fileType, 'image')) { $bgColor = 'bg-indigo-50 dark:bg-indigo-900/20'; $icon = 'heroicon-o-photo'; $iconColor = 'text-indigo-500 dark:text-indigo-400'; }
    elseif (str_contains($fileType, 'word')) { $bgColor = 'bg-blue-50 dark:bg-blue-900/20'; $icon = 'heroicon-o-document-text'; $iconColor = 'text-blue-500 dark:text-blue-400'; }
    elseif (str_contains($fileType, 'excel')) { $bgColor = 'bg-green-50 dark:bg-green-900/20'; $icon = 'heroicon-o-table-cells'; $iconColor = 'text-green-500 dark:text-green-400'; }
    elseif (str_contains($fileType, 'powerpoint')) { $bgColor = 'bg-orange-50 dark:bg-orange-900/20'; $icon = 'heroicon-o-presentation-chart-bar'; $iconColor = 'text-orange-500 dark:text-orange-400'; }
    else { $bgColor = 'bg-gray-50 dark:bg-gray-900/30'; $icon = 'heroicon-o-document-text'; $iconColor = 'text-gray-500 dark:text-gray-400'; }
    @endphp
    
    <!-- Card header with icon/preview -->
    <div class="{{ $bgColor }} p-4 flex items-center justify-center h-32 flex-shrink-0">
        @if($media && str_contains($fileType, 'image'))
            <img src="{{ $media->getUrl('preview') }}" alt="{{ $resource->title }}" class="max-w-full max-h-full object-contain rounded">
        @else
            <x-dynamic-component :component="$icon" class="h-12 w-12 {{ $iconColor }}" />
        @endif
    </div>
    
    <!-- Card content -->
    <div class="p-4 flex-grow flex flex-col">
         <!-- Access level badge -->
        <div class="mb-2 flex justify-end">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                @else bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 @endif">
                {{ match($resource->access_level) { 'all' => 'All', 'teacher' => 'Teachers', 'owner' => 'Owner'} }}
            </span>
        </div>
        <!-- Title -->
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1 line-clamp-2 flex-grow">
             <a href="{{ $viewUrl }}" class="focus:outline-none">
                 <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $resource->title }}
            </a>
        </h3>
        <!-- Category -->
        @if($resource->category)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-2">{{ $resource->category->name }}</p>
        @endif
        <!-- Date -->
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-auto pt-2">{{ $resource->updated_at->format('M d, Y') }}</p>
    </div>
    
    <!-- Quick Actions (Appear on Hover) -->
    <div class="absolute bottom-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
         <!-- Pin button -->
        <button wire:click.stop="togglePinned('{{ $resource->id }}')" type="button" 
                class="p-1.5 rounded-md bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 {{ $resource->is_pinned ? 'text-amber-500' : '' }}">
            <span class="sr-only">{{ $resource->is_pinned ? 'Unpin' : 'Pin' }}</span>
            @if($resource->is_pinned) <x-heroicon-s-star class="h-4 w-4" /> @else <x-heroicon-o-star class="h-4 w-4" /> @endif
        </button>
        <!-- Download button -->
        @if($media)
        <a href="{{ $media->getUrl() }}" target="_blank" 
           class="p-1.5 rounded-md bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400">
            <span class="sr-only">Download</span>
            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
        </a>
        @endif
         <!-- View Button -->
        <a href="{{ $viewUrl }}" 
           class="p-1.5 rounded-md bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400">
             <span class="sr-only">View Details</span>
            <x-heroicon-o-eye class="h-4 w-4" />
        </a>
    </div>
    
     <!-- Archived Overlay -->
    @if(isset($resource->is_archived) && $resource->is_archived)
        <div class="absolute inset-0 bg-gray-400/50 dark:bg-gray-900/60 flex items-center justify-center rounded-xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 shadow">
                <x-heroicon-m-archive-box class="h-4 w-4 mr-1.5" />
                Archived
            </span>
        </div>
    @endif
</div> 