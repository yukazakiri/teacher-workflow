@props(['resource', 'isPinnedSection' => false])

@php
    use Illuminate\Support\Str;
    $media = $resource->getFirstMedia('resources');
    $fileType = $media ? $media->mime_type : '';
    $fileSize = $media ? $media->human_readable_size : '';

    // Determine Icon and Color based on file type
    if (str_contains($fileType, 'pdf')) { $icon = 'heroicon-o-document-text'; $iconColor = 'text-red-500'; $bgColor = 'bg-red-100 dark:bg-red-900/30'; }
    elseif (str_contains($fileType, 'image')) { $icon = 'heroicon-o-photo'; $iconColor = 'text-indigo-500'; $bgColor = 'bg-indigo-100 dark:bg-indigo-900/30'; }
    elseif (str_contains($fileType, 'word')) { $icon = 'heroicon-o-document-text'; $iconColor = 'text-blue-500'; $bgColor = 'bg-blue-100 dark:bg-blue-900/30'; }
    elseif (str_contains($fileType, 'excel')) { $icon = 'heroicon-o-table-cells'; $iconColor = 'text-green-500'; $bgColor = 'bg-green-100 dark:bg-green-900/30'; }
    elseif (str_contains($fileType, 'powerpoint')) { $icon = 'heroicon-o-presentation-chart-bar'; $iconColor = 'text-orange-500'; $bgColor = 'bg-orange-100 dark:bg-orange-900/30'; }
    else { $icon = 'heroicon-o-document'; $iconColor = 'text-gray-500'; $bgColor = 'bg-gray-100 dark:bg-gray-700'; }

    $isOwner = $isTeamOwner ?? false; // Ensure variable is available
    $user = auth()->user();

@endphp

<li class="relative px-4 py-5 sm:px-6 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition duration-150">
    <div class="flex items-start space-x-4">
        <!-- Icon / Thumbnail -->
        <div class="flex-shrink-0 pt-1">
            <a href="{{ $resourceViewUrl($resource->id) }}" class="block {{ $bgColor }} rounded-lg p-3">
                <x-dynamic-component :component="$icon" class="h-6 w-6 {{ $iconColor }}" />
            </a>
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
                {{-- Title and Link --}}
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    <a href="{{ $resourceViewUrl($resource->id) }}" class="hover:underline focus:outline-none">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        {{ $resource->title }}
                    </a>
                </h3>
                {{-- Access Level & Archived Badge --}}
                <div class="flex items-center space-x-2 flex-shrink-0 ml-4">
                    @if(isset($resource->is_archived) && $resource->is_archived)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            Archived
                        </span>
                    @endif
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                        @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                        @else bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 @endif">
                        {{ match($resource->access_level) { 'all' => 'All Members', 'teacher' => 'Teachers Only', 'owner' => 'Owner Only'} }}
                    </span>
                </div>
            </div>

            {{-- Meta Information --}}
            <div class="mt-1 flex flex-wrap items-center space-x-3 text-xs text-gray-500 dark:text-gray-400">
                @if($resource->category)
                    <span><x-heroicon-m-tag class="h-3.5 w-3.5 inline -mt-0.5 mr-0.5" /> {{ $resource->category->name }}</span>
                    <span>&middot;</span>
                @endif
                @if($media)
                     <span><x-heroicon-m-document-text class="h-3.5 w-3.5 inline -mt-0.5 mr-0.5" /> {{ Str::upper($media->extension) }} {{ $fileSize ? '(' . $fileSize . ')' : '' }}</span>
                     <span>&middot;</span>
                @endif
                 @if($resource->creator)
                    <span><x-heroicon-m-user class="h-3.5 w-3.5 inline -mt-0.5 mr-0.5" /> Added by {{ Str::limit($resource->creator->name, 15) }}</span>
                     <span>&middot;</span>
                @endif
                <span><x-heroicon-m-clock class="h-3.5 w-3.5 inline -mt-0.5 mr-0.5" /> {{ $resource->updated_at->diffForHumans() }}</span>
            </div>

            {{-- Description (Optional: could be truncated) --}}
            @if($resource->description)
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                    {{ $resource->description }}
                </p>
            @endif
        </div>
        
         <!-- Actions Column (Positioned absolutely on hover, or always visible) -->
        <div class="absolute top-4 right-4 sm:relative sm:top-auto sm:right-auto sm:ml-6 sm:mt-1 flex-shrink-0 z-10">
            <div class="flex items-center space-x-1">
                 <!-- Pin button -->
                <button wire:click="togglePinned('{{ $resource->id }}')" type="button" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 {{ $resource->is_pinned ? 'text-amber-500' : 'text-gray-400 hover:text-amber-500 dark:text-gray-500 dark:hover:text-amber-400' }}">
                    <span class="sr-only">{{ $resource->is_pinned ? 'Unpin' : 'Pin' }}</span>
                    @if($resource->is_pinned)
                        <x-heroicon-s-star class="h-5 w-5" />
                    @else
                        <x-heroicon-o-star class="h-5 w-5" />
                    @endif
                </button>
                <!-- Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="p-1.5 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-gray-700">
                        <span class="sr-only">More options</span>
                        <x-heroicon-m-ellipsis-vertical class="h-5 w-5" />
                    </button>
                    <div x-show="open" @click.away="open = false" 
                            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" 
                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" 
                            class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-700 focus:outline-none z-50">
                        <div class="py-1" role="none">
                            <a href="{{ $resourceViewUrl($resource->id) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                <x-heroicon-m-eye class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" /> View Details
                            </a>
                            @if($media)
                            <a href="{{ $media->getUrl() }}" target="_blank" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                <x-heroicon-m-arrow-down-tray class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" /> Download
                            </a>
                            @endif
                        </div>
                        @if($isOwner || $resource->created_by === $user->id)
                            <div class="py-1" role="none">
                                <a href="{{ $resourceEditUrl($resource->id) }}" class="group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    <x-heroicon-m-pencil class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" /> Edit
                                </a>
                                @if(isset($resource->is_archived) && $resource->is_archived)
                                    <button wire:click="restoreResource('{{ $resource->id }}')" type="button" class="w-full text-left group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                        <x-heroicon-m-arrow-uturn-left class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" /> Restore
                                    </button>
                                @else
                                    <button wire:click="archiveResource('{{ $resource->id }}')" type="button" class="w-full text-left group flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                        <x-heroicon-m-archive-box class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" /> Archive
                                    </button>
                                @endif
                            </div>
                            <div class="py-1" role="none">
                                <button wire:click="deleteResource('{{ $resource->id }}')" wire:confirm="Are you sure you want to delete this resource? This action cannot be undone." type="button" class="w-full text-left group flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" role="menuitem">
                                    <x-heroicon-m-trash class="mr-3 h-5 w-5 text-red-500 group-hover:text-red-600 dark:text-red-400 dark:group-hover:text-red-300" /> Delete
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</li> 