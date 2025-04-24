<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="upload" class="space-y-6">
            {{-- File Upload --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Upload File</h3>
                
                <div class="space-y-4">
                    {{-- File Input --}}
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File</label>
                        <div class="mt-1 flex items-center">
                            <input id="file" type="file" wire:model.live="file" 
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg
                                cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700
                                dark:border-gray-600 dark:placeholder-gray-400">
                        </div>
                        @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        @if ($file)
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)
                            </div>
                        @endif
                    </div>

                    {{-- Title --}}
                    <div>
                        <label for="resourceTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <div class="mt-1">
                            <input type="text" id="resourceTitle" wire:model.live="resourceTitle" 
                                class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full
                                sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600
                                dark:text-white">
                        </div>
                        @error('resourceTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Access Level --}}
                    <div>
                        <label for="access_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Access Level</label>
                        <select id="access_level" wire:model.live="access_level" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none
                            focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700
                            dark:border-gray-600 dark:text-white">
                            <option value="all">All Team Members</option>
                            <option value="teacher">Teachers Only</option>
                            <option value="owner">Team Owner Only</option>
                        </select>
                        @error('access_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end">
                <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md
                    shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700
                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Upload Resource
                </button>
            </div>
        </form>

        {{-- Loading indicator --}}
        <div wire:loading wire:target="file,upload" class="mt-4">
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-primary-600"></div>
            </div>
            <p class="text-center text-sm text-gray-500 mt-2">Processing upload, please wait...</p>
        </div>
    </div>
</x-filament-panels::page> 