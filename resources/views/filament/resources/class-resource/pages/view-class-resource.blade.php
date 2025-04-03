<x-filament-panels::page>
    @php
        $record = $this->record;
        $mediaDetails = $this->getMediaDetails();
        $accessBadge = $this->getAccessLevelBadge();
        $descriptionHtml = $this->getFormattedDescription();
    @endphp

    <div class="space-y-6">
        {{-- Resource Metadata Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Resource Details
            </x-slot>

            <div class="grid grid-cols-1 gap-y-4 md:grid-cols-2 md:gap-x-6">
                {{-- Title --}}
                <div class="col-span-1 md:col-span-2">
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $record->title }}
                    </h2>
                </div>

                {{-- Category --}}
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->category->name ?? 'Uncategorized' }}
                    </p>
                </div>

                {{-- Access Level --}}
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Access Level</p>
                    <div class="mt-1">
                        <x-filament::badge :color="$accessBadge['color']">
                            {{ $accessBadge['label'] }}
                        </x-filament::badge>
                    </div>
                </div>

                {{-- Created By --}}
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->creator->name ?? 'N/A' }}
                    </p>
                </div>

                {{-- Created At --}}
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->created_at?->format('M d, Y H:i A') ?? 'N/A' }}
                    </p>
                </div>

                {{-- Last Updated --}}
                <div class="col-span-1 md:col-span-2">
                     <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</p>
                     <p class="mt-1 text-sm text-gray-900 dark:text-white">
                         {{ $record->updated_at?->diffForHumans() ?? 'N/A' }}
                         ({{ $record->updated_at?->format('M d, Y H:i A') }})
                     </p>
                 </div>

                {{-- Description --}}
                @if($record->description)
                    <div class="col-span-1 md:col-span-2">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</p>
                        <div class="prose prose-sm dark:prose-invert mt-1 max-w-none">
                            {!! $descriptionHtml !!}
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- File Preview Section --}}
        <x-filament::section>
            <x-slot name="heading">
                File Preview & Download
            </x-slot>

            @if ($mediaDetails)
                <div class="space-y-4">
                    {{-- Preview Area --}}
                    <div class="mb-4">
                        @if ($mediaDetails->isPdf)
                            <iframe src="{{ $mediaDetails->url }}" class="w-full h-[60vh] border border-gray-200 dark:border-gray-700 rounded-lg bg-white"></iframe>
                        @elseif ($mediaDetails->isImage)
                             <div class="flex justify-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                @if ($mediaDetails->hasThumb)
                                    <img src="{{ $mediaDetails->thumbUrl }}" class="max-w-full max-h-[200px] border border-gray-200 dark:border-gray-700 rounded-lg mb-2" alt="{{ $mediaDetails->name }} (thumbnail)">
                                    <br>
                                @endif
                                <img src="{{ $mediaDetails->url }}" class="max-w-full max-h-[60vh] border border-gray-200 dark:border-gray-700 rounded-lg" alt="{{ $mediaDetails->name }}">
                            </div>
                        @else
                            <div class="p-4 bg-gray-50 border-l-4 border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-600">
                                <div class="flex items-center">
                                     <x-heroicon-o-document-text class="h-6 w-6 text-gray-500 dark:text-gray-400 mr-3" />
                                     <p class="text-gray-700 dark:text-gray-300">Preview not available for this file type.</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- File Info and Download --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="mb-2 sm:mb-0">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $mediaDetails->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ strtoupper(pathinfo($mediaDetails->name, PATHINFO_EXTENSION)) }} File • {{ $mediaDetails->size }}
                            </p>
                        </div>
                        <x-filament::button
                            tag="a"
                            :href="$mediaDetails->url"
                            target="_blank"
                            icon="heroicon-m-arrow-down-tray"
                            color="primary"
                        >
                            Download
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="text-gray-500 dark:text-gray-400 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                    No file attached to this resource.
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>