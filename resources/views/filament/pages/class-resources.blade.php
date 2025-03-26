<?php 

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

?>

<x-filament-panels::page>
    <!-- Filter & Search Section -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Find Resources</h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="col-span-1 md:col-span-2">
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchQuery" 
                            placeholder="Search resources by title or description"
                            class="block w-full pl-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"
                        >
                    </div>
                </div>
                
                <!-- Resource Type Filter -->
                <div class="col-span-1">
                    <select 
                        wire:model.live="selectedType"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"
                    >
                        <option value="">All Types</option>
                        @foreach($resourceTypes as $typeKey => $type)
                            <option value="{{ $typeKey }}">{{ $type['title'] }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Access Level Filter -->
                <div class="col-span-1">
                    <select 
                        wire:model.live="selectedAccessLevel"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"
                    >
                        <option value="">All Access Levels</option>
                        <option value="all">All Team Members</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="owner">Owner Only</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Resources -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Resources</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resourceStats['total'] }}</p>
            </div>
            <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-2.5">
                <x-heroicon-o-document-text class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
        </div>
        
        <!-- Teaching Materials -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Teaching</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resourceStats['teaching'] }}</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-2.5">
                <x-heroicon-o-academic-cap class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
        </div>
        
        <!-- Student Resources -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Student</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resourceStats['student'] }}</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-2.5">
                <x-heroicon-o-user-group class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
        </div>
        
        <!-- Administrative -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Administrative</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resourceStats['admin'] }}</p>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-2.5">
                <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
        </div>
        
        <!-- Uncategorized -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Uncategorized</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resourceStats['uncategorized'] }}</p>
            </div>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-2.5">
                <x-heroicon-o-question-mark-circle class="w-6 h-6 text-gray-600 dark:text-gray-400" />
            </div>
        </div>
    </div>
    
    <!-- Pinned Resources -->
    @if($pinnedResources->count() > 0)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <x-heroicon-s-star class="w-5 h-5 mr-2 text-amber-500" />
                    Pinned Resources
                </h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($pinnedResources as $resource)
                    @include('filament.pages.partials.resource-card', ['resource' => $resource])
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Recent Resources -->
    @if($recentResources->count() > 0)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recently Added</h2>
                <a href="#all-resources" class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">
                    View all resources
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($recentResources as $resource)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition hover:shadow-md flex flex-col h-full">
                        <div class="absolute top-3 right-3 z-10">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
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
                        
                        @php
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
                        @endphp
                        
                        <div class="flex-shrink-0 {{ $bgColor }} px-6 pt-6 pb-4">
                            <div class="w-full h-32 flex items-center justify-center">
                                @if($primaryMedia && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                    <img src="{{ $primaryMedia->getUrl('thumbnail') }}" alt="{{ $resource->title }}" class="max-w-full max-h-full rounded object-cover">
                                @else
                                    <x-dynamic-component :component="$icon" class="w-16 h-16 {{ $iconColor }}" />
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex-grow p-4">
                            @if($resource->category)
                                <div class="flex items-center mb-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <x-dynamic-component :component="$resource->category->icon ?? 'heroicon-o-tag'" class="inline-block w-4 h-4 mr-1" />
                                        {{ $resource->category->name }}
                                    </span>
                                </div>
                            @endif
                            
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2 line-clamp-1">{{ $resource->title }}</h3>
                            
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $resource->description }}</p>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between mt-auto">
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                @if($resource->creator)
                                    <span class="inline-flex items-center">
                                        <x-heroicon-o-user class="w-4 h-4 mr-1.5" />
                                        {{ Str::limit($resource->creator->name, 15) }}
                                    </span>
                                @endif
                            </div>
                            
                            <div class="flex space-x-2">
                                @if(Gate::allows('update', $resource))
                                    <a href="{{ route('filament.app.resources.classes.edit', ['tenant' => auth()->user()->currentTeam->id, 'record' => $resource]) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400">
                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                    </a>
                                @endif
                                
                                @if($primaryMedia)
                                    <a href="{{ $primaryMedia->getUrl() }}" 
                                       target="_blank" 
                                       class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Hover overlay with quick actions -->
                        <a href="{{ route('filament.app.resources.classes.view', ['tenant' => auth()->user()->currentTeam->id, 'record' => $resource]) }}" 
                           class="absolute inset-0 bg-black/5 dark:bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="px-3 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-900 dark:text-white">
                                View Details
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Resource Categories -->
    <div id="all-resources">
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">All Resources</h2>
            </div>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2">
                        <button wire:click="$dispatch('filter-changed', { type: null })" 
                                class="inline-flex items-center p-4 border-b-2 {{ $selectedType === null ? 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                            <x-heroicon-o-squares-2x2 class="w-4 h-4 mr-2" />
                            All Types
                        </button>
                    </li>
                    @foreach($resourceTypes as $typeKey => $type)
                        <li class="mr-2">
                            <button wire:click="$dispatch('filter-changed', { type: '{{ $typeKey }}' })" 
                                    class="inline-flex items-center p-4 border-b-2 {{ $selectedType === $typeKey ? 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                                <x-dynamic-component :component="$type['icon']" class="w-4 h-4 mr-2" />
                                {{ $type['title'] }}
                            </button>
                        </li>
                    @endforeach
                    
                    @if($uncategorizedResources->count() > 0)
                        <li class="mr-2">
                            <button wire:click="$dispatch('filter-changed', { type: 'uncategorized' })" 
                                    class="inline-flex items-center p-4 border-b-2 {{ $selectedType === 'uncategorized' ? 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                                <x-heroicon-o-question-mark-circle class="w-4 h-4 mr-2" />
                                Uncategorized
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
            
            <!-- Categories and resources -->
            <div>
                @if($selectedType === 'uncategorized' && $uncategorizedResources->count() > 0)
                    <!-- Uncategorized Resources Section -->
                    <div class="mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-3">
                                <div class="flex items-center">
                                    <x-heroicon-o-question-mark-circle class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Uncategorized Resources</h3>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @foreach($uncategorizedResources as $resource)
                                        @include('filament.pages.partials.resource-card', ['resource' => $resource])
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($selectedType === null)
                    <!-- All types -->
                    @foreach($resourceTypes as $typeKey => $typeInfo)
                        @if(isset($categories[$typeKey]) && $categories[$typeKey]->count() > 0)
                            <!-- Type Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                    <x-dynamic-component :component="$typeInfo['icon']" class="w-5 h-5 mr-2 text-{{ $typeInfo['color'] }}-600 dark:text-{{ $typeInfo['color'] }}-400" />
                                    {{ $typeInfo['title'] }}
                                </h3>
                                
                                <div class="space-y-6">
                                    @foreach($categories[$typeKey] as $category)
                                        @if(isset($resourcesByCategory[$category->id]) && $resourcesByCategory[$category->id]->count() > 0)
                                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <x-dynamic-component :component="$category->icon ?? 'heroicon-o-tag'" class="w-5 h-5 mr-2 text-{{ $category->color ?? 'gray' }}-600 dark:text-{{ $category->color ?? 'gray' }}-400" />
                                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                                    </div>
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $resourcesByCategory[$category->id]->count() }} resources</span>
                                                </div>
                                                
                                                <div class="p-4">
                                                    @if($category->description)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $category->description }}</p>
                                                    @endif
                                                    
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                                        @foreach($resourcesByCategory[$category->id] as $resource)
                                                            @include('filament.pages.partials.resource-card', ['resource' => $resource])
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Uncategorized section -->
                    @if($uncategorizedResources->count() > 0)
                        <div class="mb-8">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-3">
                                    <div class="flex items-center">
                                        <x-heroicon-o-question-mark-circle class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Uncategorized Resources</h3>
                                    </div>
                                </div>
                                
                                <div class="p-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        @foreach($uncategorizedResources as $resource)
                                            @include('filament.pages.partials.resource-card', ['resource' => $resource])
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Specific type -->
                    @if(isset($categories[$selectedType]) && $categories[$selectedType]->count() > 0)
                        <div class="space-y-6">
                            @foreach($categories[$selectedType] as $category)
                                @if(isset($resourcesByCategory[$category->id]) && $resourcesByCategory[$category->id]->count() > 0)
                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between">
                                            <div class="flex items-center">
                                                <x-dynamic-component :component="$category->icon ?? 'heroicon-o-tag'" class="w-5 h-5 mr-2 text-{{ $category->color ?? 'gray' }}-600 dark:text-{{ $category->color ?? 'gray' }}-400" />
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                            </div>
                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $resourcesByCategory[$category->id]->count() }} resources</span>
                                        </div>
                                        
                                        <div class="p-4">
                                            @if($category->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $category->description }}</p>
                                            @endif
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                                @foreach($resourcesByCategory[$category->id] as $resource)
                                                    @include('filament.pages.partials.resource-card', ['resource' => $resource])
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-white">No resources found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try changing your search criteria or add new resources.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    
    <!-- Tips and Guidance -->
    <div class="mt-8 p-4 border border-blue-200 rounded-xl bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
        <div class="flex">
            <div class="flex-shrink-0">
                <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Resource Management Tips</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p>• When uploading PDF files, the title and description will be automatically generated from the document metadata.</p>
                    <p>• Organize your resources by assigning them to categories for easier access.</p>
                    <p>• Set appropriate access levels to control who can view each resource.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>