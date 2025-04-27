{{-- Modal: Delete Channel Confirmation --}}
<div 
    x-show="showDeleteConfirm" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div 
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="showDeleteConfirm = false"
    >
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Delete Channel</h3>
        <p class="text-gray-700 dark:text-gray-300 mb-3">Are you sure you want to delete this channel? This action cannot be undone.</p>
        <p class="text-danger-600 font-medium mb-6">All messages in this channel will be permanently deleted.</p>
        
        <div class="flex justify-end space-x-3">
            <button 
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-200 rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500"
                @click="showDeleteConfirm = false"
            >
                Cancel
            </button>
            <button 
                class="px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-md transition-colors duration-150 flex items-center focus:outline-none focus:ring-2 focus:ring-danger-500"
                wire:click="deleteChannel(selectedChannel)" {{-- Make sure selectedChannel ID is passed if using this --}}
                wire:loading.attr="disabled"
                wire:target="deleteChannel"
            >
                <span wire:loading.remove wire:target="deleteChannel">Delete Channel</span>
                <span wire:loading wire:target="deleteChannel" class="flex items-center">
                    <x-heroicon-o-arrow-path class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" />
                    Deleting...
                </span>
            </button>
        </div>
    </div>
</div>
            
{{-- Modal: Create Channel Form --}}
<div 
    x-show="showCreateChannelForm" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <form wire:submit.prevent="createChannel" 
        class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="showCreateChannelForm = false"
    >
        <h3 class="text-lg font-bold text-white mb-4">Create New Channel</h3>
        
        <div class="space-y-4">
            <div>
                <label for="channelName" class="block text-sm font-medium text-gray-300 mb-1">Channel Name</label>
                <input 
                   wire:model="channelName" 
                    type="text"
                    id="channelName"
                   placeholder="Enter channel name"
                    class="block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm py-2 px-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent sm:text-sm"
                    x-ref="channelNameInput"
                >
                @error('channelName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label for="channelDescription" class="block text-sm font-medium text-gray-300 mb-1">Description (Optional)</label>
                <input 
                   wire:model="channelDescription" 
                    type="text"
                    id="channelDescription"
                   placeholder="Enter channel description"
                    class="block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm py-2 px-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent sm:text-sm"
                >
                @error('channelDescription') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label for="selectedCategoryIdModal" class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                <select 
                    wire:model="selectedCategoryId"
                    id="selectedCategoryIdModal" {{-- Different ID from any other potential select --}}
                    class="block w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent sm:text-sm"
                    @if($categories->isEmpty()) disabled @endif
                >
                    <option value="">Select a category</option>
                    @forelse($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @empty
                         <option value="" disabled>No categories available. Create one first.</option>
                    @endforelse
                </select>
                @error('selectedCategoryId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="channelTypeModal" class="block text-sm font-medium text-gray-300 mb-1">Channel Type</label>
                <select 
                    wire:model="channelType"
                    id="channelTypeModal" {{-- Different ID --}}
                    class="block w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent sm:text-sm"
                >
                    @foreach($channelTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        
            <div>
               <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" wire:model="isPrivateChannel" class="rounded border-gray-600 text-indigo-600 focus:ring-indigo-500 h-4 w-4 bg-gray-700 focus:ring-offset-gray-800">
                   <span class="text-sm text-gray-300">Make this channel private</span>
               </label>
            </div>
       </div>
        
        <div class="flex justify-end space-x-3 mt-6">
            <button type="button" 
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-500"
                wire:click="cancelCreateChannel"
            >
                Cancel
            </button>
            <button type="submit" 
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition-colors duration-150 flex items-center focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                wire:loading.attr="disabled"
                wire:target="createChannel"
            >
                <span wire:loading.remove wire:target="createChannel">Create Channel</span>
                <span wire:loading wire:target="createChannel" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                </span>
            </button>
        </div>
    </form>
</div>

{{-- Modal: Create Category Form --}}
<div 
    x-show="showCreateCategoryForm" 
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60"
    x-transition:enter="transition ease-out duration-200" ...
>
    <form wire:submit.prevent="createCategory" 
        class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6"
        x-transition:enter="transition ease-out duration-200" ...
        @click.away="showCreateCategoryForm = false"
    >
        <h3 class="text-lg font-bold text-white mb-4">Create New Category</h3>
        
        <div>
            <label for="categoryNameModal" class="block text-sm font-medium text-gray-300 mb-1">Category Name</label>
            <input 
                wire:model="categoryName" 
                type="text"
                id="categoryNameModal" {{-- Different ID --}}
                placeholder="Enter category name"
                class="block w-full bg-gray-700 border-gray-600 rounded-md shadow-sm py-2 px-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent sm:text-sm"
                x-ref="categoryNameInput"
            >
            @error('categoryName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
       </div>
    
        <div class="flex justify-end space-x-3 mt-6">
            <button type="button" 
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-500"
                wire:click="cancelCreateCategory"
            >
               Cancel
            </button>
            <button type="submit" 
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition-colors duration-150 flex items-center focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                wire:loading.attr="disabled"
                wire:target="createCategory"
            >
                <span wire:loading.remove wire:target="createCategory">Create Category</span>
                <span wire:loading wire:target="createCategory" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" ...>
                        ...
                    </svg>
                    Creating...
                </span>
            </button>
        </div>
    </form>
</div>
