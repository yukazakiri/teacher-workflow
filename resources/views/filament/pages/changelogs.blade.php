@php
    use Illuminate\Support\Str;
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Version</h2>
                <span class="px-3 py-1 text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-800/20 dark:text-primary-400 rounded-full">
                    {{ str()->startsWith($currentVersion, 'v') ? $currentVersion : 'v' . $currentVersion }}
                </span>
            </div>
            <p class="text-gray-600 dark:text-gray-300">
                You are currently using version {{ str()->startsWith($currentVersion, 'v') ? $currentVersion : 'v' . $currentVersion }} of the application.
                Check the timeline below for details about this and previous releases.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Release Timeline</h2>
            
            @if(count($releases) > 0)
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-5 top-0 h-full w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                    
                    <div class="space-y-8">
                        @foreach($releases as $release)
                            <div class="relative pl-12">
                                <!-- Timeline dot -->
                                <div class="absolute left-0 top-1.5 h-10 w-10 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-800/20 border-4 border-white dark:border-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $release['name'] ?? $release['tag_name'] }}
                                        </h3>
                                        <div class="flex items-center mt-2 sm:mt-0">
                                            <span class="px-2.5 py-0.5 text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800/20 dark:text-primary-400 rounded-full">
                                                {{ $release['tag_name'] }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($release['published_at'])->format('M d, Y') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if(!empty($release['body']))
                                        <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-300 mt-2">
                                            {!! Str::markdown($release['body']) !!}
                                        </div>
                                    @else
                                        <p class="text-gray-500 dark:text-gray-400 italic">No release notes available.</p>
                                    @endif
                                    
                                    @if(!empty($release['html_url']))
                                        <div class="mt-3">
                                            <a href="{{ $release['html_url'] }}" target="_blank" class="inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                                                View on GitHub
                                                <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No releases found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No release information is available at this time.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
