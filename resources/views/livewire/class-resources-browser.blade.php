<div>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Class Resources</h2>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input 
                            wire:model.live.debounce.300ms="search" 
                            type="search" 
                            class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                            placeholder="Search resources..."
                        >
                    </div>
                    
                    <select 
                        wire:model.live="selectedCategory" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Category Pills -->
            @if($categories->count() > 0)
                <div class="flex flex-wrap gap-2 mb-6">
                    <button 
                        wire:click="$set('selectedCategory', null)" 
                        class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ is_null($selectedCategory) ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}"
                    >
                        All
                    </button>
                    
                    @foreach($categories as $category)
                        <button 
                            wire:click="$set('selectedCategory', '{{ $category->id }}')" 
                            class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $selectedCategory === $category->id ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}"
                        >
                            <span class="w-2 h-2 mr-1 rounded-full" style="background-color: {{ $category->color }}"></span>
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            @endif
            
            <!-- Resources Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($resources as $resource)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        @php
                                            $iconClass = 'heroicon-o-document';
                                            $media = $resource->getFirstMedia('files');
                                            
                                            if ($media) {
                                                $mimeType = $media->mime_type;
                                                
                                                if (str_contains($mimeType, 'image')) {
                                                    $iconClass = 'heroicon-o-photo';
                                                } elseif (str_contains($mimeType, 'pdf')) {
                                                    $iconClass = 'heroicon-o-document-text';
                                                } elseif (str_contains($mimeType, 'word')) {
                                                    $iconClass = 'heroicon-o-document';
                                                } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
                                                    $iconClass = 'heroicon-o-table-cells';
                                                } elseif (str_contains($mimeType, 'powerpoint') || str_contains($mimeType, 'presentation')) {
                                                    $iconClass = 'heroicon-o-presentation-chart-bar';
                                                } elseif (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar')) {
                                                    $iconClass = 'heroicon-o-archive-box';
                                                }
                                            }
                                            
                                            $bgColor = $resource->category?->color ?? '#4f46e5';
                                        @endphp
                                        
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $bgColor }}20;">
                                            <x-dynamic-component :component="$iconClass" class="w-6 h-6" style="color: {{ $bgColor }}"/>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ $resource->title }}
                                        </h3>
                                        
                                        @if($resource->category)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $resource->category->color }}20; color: {{ $resource->category->color }}">
                                                {{ $resource->category->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div>
                                    @php
                                        $badgeColor = match($resource->access_level) {
                                            'all' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'teacher' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                                            'owner' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                        };
                                        
                                        $badgeText = match($resource->access_level) {
                                            'all' => 'Everyone',
                                            'teacher' => 'Teachers',
                                            'owner' => 'Owner',
                                            default => 'Unknown'
                                        };
                                    @endphp
                                    
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeColor }}">
                                        {{ $badgeText }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($resource->description)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ $resource->description }}
                                </p>
                            @endif
                            
                            <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                <div>
                                    Added by {{ $resource->creator->name }}
                                </div>
                                
                                <div>
                                    {{ $resource->created_at->diffForHumans() }}
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button 
                                    wire:click="downloadResource({{ $resource->id }})"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-xl font-medium">No resources found</p>
                        <p class="mt-1">Try adjusting your search or filter to find what you're looking for.</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Pagination -->
            <div class="mt-6">
                {{ $resources->links() }}
            </div>
        </div>
    </div>
    
    <!-- Notification -->
    <div
        x-data="{ 
            notifications: [], 
            add(notification) {
                this.notifications.push({
                    id: Date.now(),
                    type: notification.type,
                    message: notification.message,
                });
                
                setTimeout(() => {
                    this.notifications.shift();
                }, 3000);
            }
        }"
        @notify.window="add($event.detail)"
        class="fixed bottom-0 right-0 p-4 z-50 space-y-3"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div 
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                :class="{
                    'bg-green-500': notification.type === 'success',
                    'bg-red-500': notification.type === 'error',
                    'bg-blue-500': notification.type === 'info',
                    'bg-yellow-500': notification.type === 'warning'
                }"
                class="rounded-lg p-4 text-white shadow-lg flex items-center space-x-3"
            >
                <div class="flex-shrink-0">
                    <template x-if="notification.type === 'success'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </template>
                    <template x-if="notification.type === 'error'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </template>
                    <template x-if="notification.type === 'info'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </template>
                    <template x-if="notification.type === 'warning'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </template>
                </div>
                <div x-text="notification.message"></div>
            </div>
        </template>
    </div>
</div> 