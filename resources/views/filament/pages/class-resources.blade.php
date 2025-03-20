<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Quick Upload Card -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-primary-100 dark:bg-primary-900">
                            <x-heroicon-o-arrow-up-tray class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Upload</h3>
                </div>
                
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Upload files directly to your class resources. The title will be automatically generated from the filename.
                </p>
                
                <div class="mt-4">
                    <a href="{{ route('filament.app.resources.class-resources.create', ['tenant' => auth()->user()->currentTeam]) }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload Files
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Resource Types -->
        @if(isset($resourceTypes))
            @foreach($resourceTypes as $type)
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
        @endif
    </div>
    
    <div class="p-4 border border-blue-200 rounded-lg bg-blue-50 dark:bg-gray-800 dark:border-blue-800 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1 md:flex md:justify-between">
                <p class="text-sm text-blue-700 dark:text-blue-400">
                    <strong>Tip:</strong> We've created default categories for teacher materials and student resources. Teacher materials are automatically restricted to teachers only.
                </p>
            </div>
        </div>
    </div>
    
    <livewire:class-resources-browser />
</x-filament-panels::page> 