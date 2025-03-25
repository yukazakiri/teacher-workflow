<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Resource Types -->
        @foreach($resourceTypes as $typeKey => $type)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-{{ $type['color'] }}-100 dark:bg-{{ $type['color'] }}-900">
                                <x-dynamic-component :component="$type['icon']" class="w-6 h-6 text-{{ $type['color'] }}-600 dark:text-{{ $type['color'] }}-400" />
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $type['title'] }}</h3>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $type['description'] }}</p>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Examples:</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach(explode(', ', $type['examples']) as $example)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $type['color'] }}-100 text-{{ $type['color'] }}-800 dark:bg-{{ $type['color'] }}-900 dark:text-{{ $type['color'] }}-300">
                                    {{ $example }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Recent Resources Section -->
    @if($recentResources->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recently Added Resources</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($recentResources as $resource)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden flex flex-col h-full">
                        <div class="p-4 flex-grow">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $resource->title }}</h3>
                                
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                    @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                    @endif">
                                    @if($resource->access_level === 'all') All Team Members
                                    @elseif($resource->access_level === 'teacher') Teachers Only
                                    @else Owner Only
                                    @endif
                                </span>
                            </div>
                            
                            @if($resource->category)
                                <div class="flex items-center mb-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <x-dynamic-component :component="$resource->category->icon ?? 'heroicon-o-tag'" class="inline-block w-4 h-4 mr-1" />
                                        {{ $resource->category->name }}
                                    </span>
                                </div>
                            @endif
                            
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $resource->description }}</p>
                            
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                @foreach($resource->getMedia('resources') as $media)
                                    @php
                                        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
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
                                    <a href="{{ $media->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $bgColor }}">
                                        <div class="w-12 h-12 flex items-center justify-center mb-2">
                                            @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                                <img src="{{ $media->getUrl('thumbnail') }}" alt="{{ $media->file_name }}" class="max-w-full max-h-full rounded object-cover">
                                            @else
                                                <x-dynamic-component :component="$icon" class="w-8 h-8 {{ $iconColor }}" />
                                            @endif
                                        </div>
                                        <span class="text-xs text-center truncate w-full {{ $iconColor }}">{{ Str::limit($media->file_name, 15) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 mt-auto border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $resource->created_at->diffForHumans() }}
                            </span>
                            
                            <div class="flex space-x-2">
                                <a href="{{ route('filament.resources.class-resources.edit', $resource) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </a>
                                <a href="{{ route('filament.resources.class-resources.view', $resource) }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Resources by Category -->
    @foreach($resourceTypes as $typeKey => $typeInfo)
        @if(isset($categories[$typeKey]) && $categories[$typeKey]->count() > 0)
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ $typeInfo['title'] }}</h2>
                
                <div class="space-y-6">
                    @foreach($categories[$typeKey] as $category)
                        @if(isset($resourcesByCategory[$category->id]) && $resourcesByCategory[$category->id]->count() > 0)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
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
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                        @foreach($resourcesByCategory[$category->id] as $resource)
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 bg-white dark:bg-gray-800 shadow-sm">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $resource->title }}</h4>
                                                    
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                        @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                        @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                        @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                                        @endif">
                                                        @if($resource->access_level === 'all') All
                                                        @elseif($resource->access_level === 'teacher') Teachers
                                                        @else Owner
                                                        @endif
                                                    </span>
                                                </div>
                                                
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $resource->description }}</p>
                                                
                                                <div class="grid grid-cols-2 gap-2 mb-2">
                                                    @foreach($resource->getMedia('resources') as $media)
                                                        @php
                                                            $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
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
                                                        <a href="{{ $media->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $bgColor }}">
                                                            <div class="w-8 h-8 flex items-center justify-center mb-1">
                                                                @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                                                    <img src="{{ $media->getUrl('thumbnail') }}" alt="{{ $media->file_name }}" class="max-w-full max-h-full rounded object-cover">
                                                                @else
                                                                    <x-dynamic-component :component="$icon" class="w-6 h-6 {{ $iconColor }}" />
                                                                @endif
                                                            </div>
                                                            <span class="text-xs text-center truncate w-full {{ $iconColor }}">{{ Str::limit($media->file_name, 10) }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                                
                                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    <div class="flex space-x-2">
                                                        <a href="{{ route('filament.resources.class-resources.edit', $resource) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400">
                                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                                        </a>
                                                        <a href="{{ route('filament.resources.class-resources.view', $resource) }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                                            <x-heroicon-o-eye class="w-4 h-4" />
                                                        </a>
                                                    </div>
                                                    <span>{{ $resource->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
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
    
    <!-- Uncategorized Resources -->
    @if(isset($uncategorizedResources) && $uncategorizedResources->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Uncategorized Resources</h2>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-4 py-3">
                    <div class="flex items-center">
                        <x-heroicon-o-question-mark-circle class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Uncategorized</h3>
                    </div>
                </div>
                
                <div class="p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">These resources haven't been assigned to a category yet. Edit each resource to assign it to a category.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($uncategorizedResources as $resource)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 bg-white dark:bg-gray-800 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $resource->title }}</h4>
                                    
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                        @if($resource->access_level === 'all') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @elseif($resource->access_level === 'teacher') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                        @endif">
                                        @if($resource->access_level === 'all') All
                                        @elseif($resource->access_level === 'teacher') Teachers
                                        @else Owner
                                        @endif
                                    </span>
                                </div>
                                
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $resource->description }}</p>
                                
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    @foreach($resource->getMedia('resources') as $media)
                                        @php
                                            $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
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
                                        <a href="{{ $media->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $bgColor }}">
                                            <div class="w-8 h-8 flex items-center justify-center mb-1">
                                                @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                                    <img src="{{ $media->getUrl('thumbnail') }}" alt="{{ $media->file_name }}" class="max-w-full max-h-full rounded object-cover">
                                                @else
                                                    <x-dynamic-component :component="$icon" class="w-6 h-6 {{ $iconColor }}" />
                                                @endif
                                            </div>
                                            <span class="text-xs text-center truncate w-full {{ $iconColor }}">{{ Str::limit($media->file_name, 10) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('filament.resources.class-resources.edit', $resource) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('filament.resources.class-resources.view', $resource) }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                        </a>
                                    </div>
                                    <span>{{ $resource->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <div class="p-4 border border-blue-200 rounded-lg bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1 md:flex md:justify-between">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <strong>Tip:</strong> When uploading PDF files, the title and description will be automatically generated from the file metadata. You can always edit these details later.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>