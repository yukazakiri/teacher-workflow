<?php 

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use App\Models\ClassResource;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

// Get current team and user for resource permissions
$team = Filament::getTenant();
$user = Auth::user();
$isTeamOwner = $team->userIsOwner($user);

// Check resource creation permissions
$canCreateResources = Gate::allows('create', ClassResource::class);

?>

<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Stats Section -->
        <section aria-labelledby="stats-heading">
            <h2 id="stats-heading" class="sr-only">Resource Statistics</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                <!-- Total -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $resourceStats['total'] }}</dd>
                    </div>
                    <div class="bg-primary-100 dark:bg-primary-900/30 p-3 rounded-lg">
                        <x-heroicon-o-squares-2x2 class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <!-- Teaching -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Teaching</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $resourceStats['teaching'] }}</dd>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                        <x-heroicon-o-academic-cap class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <!-- Student -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Student</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $resourceStats['student'] }}</dd>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                        <x-heroicon-o-user-group class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <!-- Admin -->
                 <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Admin</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $resourceStats['admin'] }}</dd>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg">
                        <x-heroicon-o-briefcase class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
                <!-- Pinned -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pinned</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $resourceStats['pinned'] }}</dd>
                    </div>
                    <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-lg">
                        <x-heroicon-o-star class="w-6 h-6 text-amber-500 dark:text-amber-400" />
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Active Filters -->
        @if($selectedAccessLevel || $searchQuery || $showArchived)
            <section aria-labelledby="active-filters-heading" class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 id="active-filters-heading" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Filters:</h3>
                    <button wire:click="$set('selectedAccessLevel', null); $set('searchQuery', ''); $set('showArchived', false)" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                        Clear Filters
                    </button>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($selectedAccessLevel)
                        <span class="inline-flex items-center py-1 pl-2.5 pr-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                            Access: {{ match($selectedAccessLevel) { 'all' => 'All Members', 'teacher' => 'Teachers', 'owner' => 'Owner', default => ucfirst($selectedAccessLevel) } }}
                            <button type="button" wire:click="$set('selectedAccessLevel', null)" class="ml-1 flex-shrink-0 p-0.5 text-purple-500 hover:text-purple-700 rounded-full hover:bg-purple-200 dark:hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <span class="sr-only">Remove filter</span>
                                <x-heroicon-m-x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                    @if($searchQuery)
                        <span class="inline-flex items-center py-1 pl-2.5 pr-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            Search: "{{ Str::limit($searchQuery, 20) }}"
                            <button type="button" wire:click="$set('searchQuery', '')" class="ml-1 flex-shrink-0 p-0.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <span class="sr-only">Remove filter</span>
                                <x-heroicon-m-x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                    @if($showArchived)
                        <span class="inline-flex items-center py-1 pl-2.5 pr-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            Including Archived
                            <button type="button" wire:click="$set('showArchived', false)" class="ml-1 flex-shrink-0 p-0.5 text-yellow-500 hover:text-yellow-700 rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <span class="sr-only">Remove filter</span>
                                <x-heroicon-m-x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                </div>
            </section>
        @endif

        <!-- Pinned Resources (Compact List Style) -->
        @if($pinnedResources->isNotEmpty())
            <section aria-labelledby="pinned-heading">
                <div class="flex items-center mb-3">
                    <x-heroicon-s-star class="w-5 h-5 text-amber-500 mr-2" />
                    <h2 id="pinned-heading" class="text-lg font-semibold text-gray-900 dark:text-white">Pinned Resources</h2>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pinnedResources as $resource)
                            @include('filament.pages.partials.resource-list-item', ['resource' => $resource, 'isPinnedSection' => true]) 
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <!-- Main Content Area with Tabs -->
        <section aria-labelledby="resources-heading">
            <!-- Tabs & Layout Switcher -->
            <div class="mb-6 pb-3 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                    @foreach($resourceTypes as $categoryKey => $categoryInfo)
                        <button 
                            wire:click="setViewingCategory('{{ $categoryKey }}')"
                            type="button"
                            class="group inline-flex shrink-0 items-center gap-x-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors duration-150 ease-in-out 
                                {{ $viewingCategory === $categoryKey
                                    ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-200' }}"
                            aria-current="{{ $viewingCategory === $categoryKey ? 'page' : 'false' }}"
                        >
                             @if(isset($categoryInfo['icon'])) <x-dynamic-component :component="$categoryInfo['icon']" @class(['h-5 w-5', 'text-primary-500 dark:text-primary-400' => $viewingCategory === $categoryKey, 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' => $viewingCategory !== $categoryKey]) /> @endif
                            {{ $categoryInfo['title'] }}
                        </button>
                    @endforeach
                </nav>
                
                {{-- Layout Mode Switcher --}}
                <div class="mt-3 sm:mt-0 flex items-center justify-end space-x-1 bg-gray-100 dark:bg-gray-900 p-1 rounded-lg">
                    <button wire:click="setLayoutMode('grid')" type="button" 
                            class="p-1.5 rounded-md transition-colors {{ $layoutMode === 'grid' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                        <span class="sr-only">Grid View</span>
                        <x-heroicon-m-squares-2x2 class="h-5 w-5" />
                    </button>
                     <button wire:click="setLayoutMode('card')" type="button" 
                            class="p-1.5 rounded-md transition-colors {{ $layoutMode === 'card' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                        <span class="sr-only">Card View</span>
                        <x-heroicon-m-view-columns class="h-5 w-5" />
                    </button>
                    <button wire:click="setLayoutMode('list')" type="button" 
                            class="p-1.5 rounded-md transition-colors {{ $layoutMode === 'list' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                        <span class="sr-only">List View</span>
                        <x-heroicon-m-list-bullet class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Resource Display Area -->
            <div>
                @if ($mainResources->isEmpty())
                    <div class="text-center bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-10">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <x-heroicon-o-magnifying-glass class="h-12 w-12" />
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">No Resources Found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            There are no resources matching your current filters in this category.
                            @if($searchQuery || $selectedAccessLevel || $showArchived)
                                Try adjusting your filters or clearing them.
                            @else 
                                Try selecting a different category or add new resources.
                            @endif
                        </p>
                         @if($canCreateResources)
                            <div class="mt-6">
                                {{-- Remove this line: The action is already in the header --}}
                                {{-- ($this->getHeaderActions())['add_resource'] --}} 
                            </div>
                        @endif
                    </div>
                @else
                    {{-- LIST VIEW --}}
                    @if ($layoutMode === 'list')
                         <div wire:key="layout-list" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($mainResources as $resource)
                                    @include('filament.pages.partials.resource-list-item', ['resource' => $resource, 'isPinnedSection' => false]) 
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- CARD VIEW --}}
                    @if ($layoutMode === 'card')
                        <div wire:key="layout-card" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                            @foreach($mainResources as $resource)
                                <x-resource-card :resource="$resource" :viewUrl="$resourceViewUrl($resource->id)" :editUrl="$resourceEditUrl($resource->id)" :isOwner="$isTeamOwner" />
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- GRID VIEW (Simplified Card/Grid - adjust classes as needed) --}}
                    @if ($layoutMode === 'grid')
                         <div wire:key="layout-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                             @foreach($mainResources as $resource)
                                {{-- Using the same card component but maybe with different grid classes --}}
                                <x-resource-card :resource="$resource" :viewUrl="$resourceViewUrl($resource->id)" :editUrl="$resourceEditUrl($resource->id)" :isOwner="$isTeamOwner" />
                            @endforeach
                        </div>
                    @endif

                    <!-- Pagination -->
                    @if ($mainResources->hasPages())
                        <div class="mt-8">
                            {{ $mainResources->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>

    </div>
</x-filament-panels::page>