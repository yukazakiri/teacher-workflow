<div 
    class="h-full flex flex-col bg-gray-900 text-gray-300 shadow-lg"
    x-data="{ 
        showDeleteConfirm: false,
        editingChannelId: null,
        editingChannelName: '',
        selectedChannel: null,
        selectedCategoryId: null,
        channelContextMenu: { show: false, x: 0, y: 0, id: null },
        showCreateChannelForm: false,
        showCreateCategoryForm: false,
        showMembers: @js($showMembers), // Sync Alpine state with Livewire
        unreadChannels: new Set() // Track unread channels
    }"
    @click.away="channelContextMenu.show = false"
    @channel-delete-initiated.window="showDeleteConfirm = true; selectedChannel = $event.detail"
    @channel-deletion-complete.window="showDeleteConfirm = false"
    @channel-rename-initiated.window="editingChannelId = $event.detail; $nextTick(() => $refs['channel-name-'+editingChannelId]?.focus())"
    @channel-rename-complete.window="editingChannelId = null"
    @channel-rename-cancelled.window="editingChannelId = null"
    @channel-create-initiated.window="showCreateChannelForm = true; selectedCategoryId = $event.detail; $nextTick(() => $refs.channelNameInput?.focus())"
    @channel-creation-complete.window="showCreateChannelForm = false"
    @channel-creation-cancelled.window="showCreateChannelForm = false"
    @category-create-initiated.window="showCreateCategoryForm = true; $nextTick(() => $refs.categoryNameInput?.focus())"
    @category-creation-complete.window="showCreateCategoryForm = false"
    @category-creation-cancelled.window="showCreateCategoryForm = false"
    @keydown.escape.window="channelContextMenu.show = false; showDeleteConfirm = false; editingChannelId = null; showCreateChannelForm = false; showCreateCategoryForm = false"
    @new-message.window="if($event.detail.channelId && $event.detail.channelId !== '{{ $activeChannelId }}') { unreadChannels.add($event.detail.channelId); }"
    @channel-read.window="unreadChannels.delete($event.detail.channelId);"
>
    {{-- Team/Server Header --}}
    <div class="flex-shrink-0 h-12 flex items-center justify-between px-4 shadow-md bg-discord-dark border-b border-discord-light-gray z-10">
        <span class="text-white font-medium text-base truncate">{{ $team->name ?? 'Team' }}</span>
        <div class="flex items-center space-x-1 md:space-x-2">
            <button 
                @click="showMembers = !showMembers"
                wire:click="toggleMembersList"
                class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-discord-hover rounded-md focus:outline-none transition-colors duration-150"
                :class="{ 'bg-discord-hover text-gray-200': showMembers }"
                x-tooltip="Members"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
            <button 
                wire:click="startCreateChannel()" 
                class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-discord-hover rounded-md focus:outline-none transition-colors duration-150"
                x-tooltip="New Channel"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
            </button>
            <button 
                wire:click="startCreateCategory" 
                class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-discord-hover rounded-md focus:outline-none transition-colors duration-150"
                x-tooltip="New Category"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Main Content (Channel List or Members List) --}}
    <div class="flex-1 overflow-y-auto p-2 scrollbar-thin scrollbar-thumb-discord-scrollbar scrollbar-track-discord-dark">
        
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
                class="bg-discord-dark rounded-lg shadow-xl w-full max-w-md p-6"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="showDeleteConfirm = false"
            >
                <h3 class="text-lg font-bold text-white mb-4">Delete Channel</h3>
                <p class="text-gray-300 mb-3">Are you sure you want to delete this channel? This action cannot be undone.</p>
                <p class="text-red-400 font-medium mb-6">All messages in this channel will be permanently deleted.</p>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        class="px-4 py-2 bg-discord-light-gray hover:bg-discord-hover text-gray-200 rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-discord-blurple"
                        @click="showDeleteConfirm = false"
                    >
                        Cancel
                    </button>
                    <button 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors duration-150 flex items-center focus:outline-none focus:ring-2 focus:ring-red-500"
                        wire:click="deleteChannel(selectedChannel)"
                        wire:loading.attr="disabled"
                        wire:target="deleteChannel"
                    >
                        <span wire:loading.remove wire:target="deleteChannel">Delete Channel</span>
                        <span wire:loading wire:target="deleteChannel" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
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
                        <label for="channelDescription" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
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
                <label for="selectedCategoryId" class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                <select 
                    wire:model="selectedCategoryId"
                    id="selectedCategoryId"
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
                        <label for="channelType" class="block text-sm font-medium text-gray-300 mb-1">Channel Type</label>
                <select 
                    wire:model="channelType"
                    id="channelType"
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
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <form wire:submit.prevent="createCategory" 
                class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="showCreateCategoryForm = false"
            >
                <h3 class="text-lg font-bold text-white mb-4">Create New Category</h3>
                
                <div>
                    <label for="categoryName" class="block text-sm font-medium text-gray-300 mb-1">Category Name</label>
                    <input 
                        wire:model="categoryName" 
                        type="text"
                        id="categoryName"
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
        
        {{-- Context Menu --}}
        <div 
            x-show="channelContextMenu.show" 
            x-cloak
            :style="`position: fixed; left: ${channelContextMenu.x}px; top: ${channelContextMenu.y}px; z-index: 50;`"
            class="bg-gray-800 border border-gray-700 rounded-md shadow-lg overflow-hidden w-40"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="py-1">
                <button 
                    @click="editingChannelId = channelContextMenu.id; channelContextMenu.show = false; $nextTick(() => $refs['channel-name-'+editingChannelId]?.focus())"
                    wire:click="startRenameChannel(channelContextMenu.id)"
                    class="w-full text-left px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-700 hover:text-white flex items-center transition-colors duration-150"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Rename
                </button>
                <button 
                    @click="channelContextMenu.show = false; showDeleteConfirm = true; selectedChannel = channelContextMenu.id"
                    wire:click="startDeleteChannel(channelContextMenu.id)"
                    class="w-full text-left px-3 py-1.5 text-sm text-red-400 hover:bg-red-600 hover:text-white flex items-center transition-colors duration-150"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>
        
        {{-- Members List (Conditionally Shown) --}}
        <div x-show="showMembers" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <h3 class="font-semibold text-gray-400 text-xs uppercase tracking-wide mb-2 px-1">Members ({{ count($teamMembers) }})</h3>
            <ul class="space-y-0.5">
                @foreach($teamMembers as $member)
                    <li class="flex items-center space-x-2.5 p-1.5 rounded-md hover:bg-discord-hover transition-colors duration-150 cursor-default">
                        <div class="relative flex-shrink-0">
                            <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}" class="h-8 w-8 rounded-full object-cover">
                            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 border-2 border-discord-dark"></span>
                        </div>
                        <span class="text-sm font-medium {{ $team->user_id === $member->id ? 'text-yellow-300' : 'text-gray-300' }} truncate">
                            {{ $member->name }}
                            @if($team->user_id === $member->id)
                                <span class="text-xs text-yellow-500 ml-1">(owner)</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
        
        {{-- Channel List (Conditionally Shown) --}}
        <div x-show="!showMembers" x-cloak>
            @if ($categories->isEmpty())
                <div class="text-center text-gray-500 py-10">
                    <p class="mb-4">No channels found.</p>
                    <button 
                        wire:click="startCreateCategory" 
                        class="mt-4 px-4 py-2 bg-discord-blurple hover:bg-discord-blurple-dark text-white rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-discord-blurple"
                    >
                        Create your first category
                    </button>
                </div>
            @else
                <nav class="space-y-2">
                    @foreach ($categories as $category)
                        <div x-data="{ open: true }">
                            {{-- Category Header with Add Channel Button --}}
                            <div class="flex items-center justify-between group mb-0.5">
                                <button 
                                    @click="open = !open" 
                                    class="flex items-center px-1 py-1 text-xs font-semibold text-gray-400 uppercase hover:text-gray-200 focus:outline-none transition-colors duration-150 rounded w-full"
                                >
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="h-3 w-3 mr-1 transition-transform duration-150"
                                        :class="{'transform rotate-90': open, 'transform rotate-0': !open}"
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke="currentColor"
                                        >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span class="truncate max-w-[150px] sm:max-w-full">{{ $category->name }}</span>
                                </button>
                                
                                <button 
                                    wire:click="startCreateChannel('{{ $category->id }}')"
                                    class="p-1 text-gray-500 hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none rounded-full hover:bg-discord-hover"
                                    x-tooltip="New Channel in {{ $category->name }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Channels --}}
                            <div 
                                x-show="open" 
                                x-collapse
                                class="mt-0.5 space-y-0 pl-1"
                            >
                                <ul class="space-y-0">
                                    @forelse ($category->channels as $channel)
                                        <li 
                                            class="relative group rounded flex items-center"
                                            :class="{
                                                'bg-[#36393f]': editingChannelId === '{{ $channel->id }}',
                                            }"
                                            @contextmenu.prevent="channelContextMenu.show = true; channelContextMenu.x = $event.clientX; channelContextMenu.y = $event.clientY; channelContextMenu.id = '{{ $channel->id }}'"
                                        >
                                            {{-- Inline Editing --}}
                                            <div x-show="editingChannelId === '{{ $channel->id }}'" class="flex items-center py-1 px-1.5 w-full">
                                                <input 
                                                    type="text" 
                                                    wire:model.defer="channelName" 
                                                    x-ref="channel-name-{{ $channel->id }}"
                                                    class="flex-grow bg-[#202225] border-none rounded py-1 px-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#5865f2]"
                                                    @keydown.enter="$wire.renameChannel('{{ $channel->id }}')"
                                                    @keydown.escape="editingChannelId = null; $wire.cancelRename()"
                                                    @click.outside="if(editingChannelId === '{{ $channel->id }}') { editingChannelId = null; $wire.cancelRename() }"
                                                />
                                                <div class="flex ml-1 space-x-0.5 flex-shrink-0">
                                                    <button 
                                                        @click="$wire.renameChannel('{{ $channel->id }}')"
                                                        class="text-green-400 hover:text-green-300 p-1 rounded hover:bg-[#36393f] transition-colors duration-150"
                                                        x-tooltip="Save"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                    <button 
                                                        @click="editingChannelId = null; $wire.cancelRename()"
                                                        class="text-red-400 hover:text-red-300 p-1 rounded hover:bg-[#36393f] transition-colors duration-150"
                                                        x-tooltip="Cancel"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            {{-- Normal Channel Display --}}
                                            <div x-show="editingChannelId !== '{{ $channel->id }}'" class="flex items-center w-full">
                                                <button 
                                                    wire:click="selectChannel('{{ $channel->id }}')" 
                                                    @click="unreadChannels.delete('{{ $channel->id }}'); $wire.dispatch('markChannelAsRead', { channelId: '{{ $channel->id }}' });"
                                                    class="flex items-center w-full text-left py-1 px-2 rounded text-sm font-medium transition-colors duration-150 justify-between relative"
                                                    :class="{
                                                        // Discord left border for active/unread
                                                        'border-l-4 border-[#5865f2] bg-[#393c43] text-white font-bold': '{{ $activeChannelId }}' === '{{ $channel->id }}',
                                                        'border-l-4 border-[#43b581] bg-[#393c43] text-white font-bold': unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}',
                                                        'text-gray-400 hover:text-white hover:bg-[#36393f]': !unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}',
                                                    }"
                                                >
                                                    <div class="flex items-center flex-grow min-w-0">
                                                        {{-- Channel icon based on type --}}
                                                        <span class="mr-1.5 flex-shrink-0"
                                                              :class="{
                                                                'text-white': '{{ $activeChannelId }}' === '{{ $channel->id }}', 
                                                                'text-[#43b581]': unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}',
                                                                'text-gray-500': !unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}'
                                                               }"
                                                        >
                                                            @if($channel->type === 'text')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                                                </svg>
                                                            @elseif($channel->type === 'announcement')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                                                </svg>
                                                            @elseif($channel->type === 'voice')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                                                </svg>
                                                            @elseif($channel->type === 'media')
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                            @endif
                                                        </span>
                                                        <span class="truncate flex-grow" :class="{ 'font-bold text-white': unreadChannels.has('{{ $channel->id }}') }">{{ $channel->name }}</span>
                                                    </div>
                                                    {{-- Unread indicator (Discord style) --}}
                                                    <span 
                                                        x-show="unreadChannels.has('{{ $channel->id }}')"
                                                        class="ml-2 h-2.5 w-2.5 bg-[#43b581] rounded-full animate-pulse"
                                                    ></span>
                                                    {{-- Channel Actions Button (dots menu) - shown on hover only --}}
                                                    <button 
                                                        class="ml-1 flex-shrink-0 opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-full text-gray-500 hover:text-gray-300 hover:bg-[#36393f] transition-all duration-150"
                                                        @click.stop="channelContextMenu.show = true; channelContextMenu.x = $event.clientX; channelContextMenu.y = $event.clientY; channelContextMenu.id = '{{ $channel->id }}'"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                        </svg>
                                                    </button>
                                                </button>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="py-1.5 px-2 text-sm text-gray-500 italic">No channels in this category</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar styles */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #4a5568 #2d3748; /* thumb track */
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #2d3748; /* Use a slightly lighter shade than the main background */
            border-radius: 3px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #4a5568; /* gray-700 */
            border-radius: 3px;
            border: 1px solid #2d3748;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background-color: #718096; /* gray-600 */
        }
    </style>
    <script>
        // Add Alpine Collapse plugin if not already included globally
        document.addEventListener('alpine:initializing', () => {
            if (!Alpine.plugins.collapse) {
                Alpine.plugin(Alpine.plugins.collapse)
            }
        })
    </script>
</div>
