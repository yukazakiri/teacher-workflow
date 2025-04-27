<div
    class="h-full flex flex-col bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-200 shadow-lg"
    x-data="{
        showDeleteConfirm: false,
        editingChannelId: null,
        editingChannelName: '',
        selectedChannel: null, // Used by modals
        selectedCategoryId: null, // Used by create channel modal
        channelContextMenu: { show: false, x: 0, y: 0, id: null },
        showCreateChannelForm: false,
        showCreateCategoryForm: false,
        unreadChannels: new Set() // Keep for unread indication
    }"
    @click.away="channelContextMenu.show = false"
    @channel-delete-initiated.window="showDeleteConfirm = true; selectedChannel = $event.detail.id" {{-- Ensure ID is passed --}}
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
    <div class="flex-shrink-0 h-12 flex items-center justify-between px-4 shadow bg-white dark:bg-gray-800 border-b border-gray-300 dark:border-gray-700 z-10">
        <span class="text-gray-900 dark:text-white font-medium text-base truncate">{{ $team->name ?? 'Team' }}</span>
        <div class="flex items-center space-x-1 md:space-x-2">
            {{-- Action buttons - Simplified --}}
            <button
                wire:click="startCreateChannel()"
                class="p-1.5 text-gray-500 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900 rounded-md focus:outline-none transition-colors duration-150"
                x-tooltip="'New Channel'"
            >
                <x-heroicon-o-plus-circle class="h-5 w-5" />
            </button>
            <button
                wire:click="startCreateCategory"
                class="p-1.5 text-gray-500 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900 rounded-md focus:outline-none transition-colors duration-150"
                x-tooltip="'New Category'"
            >
                <x-heroicon-o-folder-plus class="h-5 w-5" />
            </button>
             {{-- Maybe add a general settings button later --}}
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- User Carousel for DMs using Filament UI --}}
        <div class="flex-shrink-0 fi-section bg-white dark:bg-gray-800 p-2 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex space-x-3 overflow-x-auto pb-2 fi-scrollbar scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700 max-w-full">
                 @if($teamMembers->isEmpty())
                     <p class="fi-text text-sm text-gray-500 dark:text-gray-400 px-1 italic">No other members in this team.</p>
                 @else
                    @php
                        // $activeDmOtherUserId is now passed from the component
                    @endphp
                     <div class="flex space-x-3 flex-nowrap">
                         @foreach($teamMembers as $member)
                             {{-- Ensure the member object is not null AND not the current user --}}
                             @if($member && $member->id !== Auth::id())
                             <button
                                wire:click="startDirectMessage('{{ $member->id }}')"
                                wire:key="dm-user-{{ $member->id }}"
                                class="relative flex-shrink-0 flex flex-col items-center justify-center focus:outline-none rounded-full transition-colors duration-150 group p-0.5"
                                :class="{
                                    'fi-active ring-2 ring-primary-600 dark:ring-primary-500': '{{ $activeDmOtherUserId ?? null }}' === '{{ $member->id }}',
                                    'hover:bg-gray-50 dark:hover:bg-gray-700': '{{ $activeDmOtherUserId ?? null }}' !== '{{ $member->id }}'
                                }"
                                 x-tooltip.bottom="'{{ $member->name }}'"
                             >
                                 <div class="fi-avatar w-10 h-10 rounded-full overflow-hidden shadow-sm">
                                     <img
                                        src="{{ $member->profile_photo_url }}"
                                        alt="{{ $member->name }}"
                                        class="h-full w-full object-cover border-2 border-white dark:border-gray-800"
                                        onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23cbd5e1%22><path d=%22M7.5 6.5C7.5 8.981 9.519 11 12 11s4.5-2.019 4.5-4.5S14.481 2 12 2 7.5 4.019 7.5 6.5zM20 21h1v-1c0-3.859-3.141-7-7-7h-4c-3.86 0-7 3.141-7 7v1h17z%22/></svg>'"
                                     >
                                 </div>
                                 {{-- Presence indicator (example) --}}
                                 <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-success-500 border-2 border-white dark:border-gray-800 fi-badge-success"></span>
                             </button>
                             @endif
                         @endforeach
                     </div>
                 @endif
            </div>
        </div>

        {{-- Channel List Area --}}
        <div class="flex-1 overflow-y-auto p-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700 scrollbar-track-gray-100 dark:scrollbar-track-gray-900">
             @if ($categories->isEmpty())
                 <div class="text-center text-gray-500 py-10">
                     <p class="mb-4">No channels found.</p>
                 </div>
             @else
                 <nav class="space-y-2">
                     @foreach ($categories as $category)
                         @if($category) {{-- Ensure category object exists --}}
                         <div x-data="{ open: true }" wire:key="category-{{ $category->id }}">
                             {{-- Category Header --}}
                             <div class="flex items-center justify-between group mb-0.5">
                                 <button @click="open = !open" class="flex items-center px-1 py-1 text-xs font-semibold text-gray-400 uppercase hover:text-gray-200 focus:outline-none transition-colors duration-150 rounded w-full">
                                     <svg class="h-3 w-3 mr-1 transition-transform duration-150 shrink-0" :class="{'transform rotate-90': open, 'transform rotate-0': !open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                     </svg>
                                     <span class="truncate max-w-[150px] sm:max-w-full">{{ $category->name }}</span>
                                 </button>
                                 <button
                                     wire:click="startCreateChannel('{{ $category->id }}')"
                                     class="p-1 text-gray-500 hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 shrink-0"
                                     x-tooltip="'New Channel in {{ $category->name }}'"
                                 >
                                     <x-heroicon-o-plus class="h-4 w-4" />
                                 </button>
                             </div>

                             {{-- Channels in Category --}}
                             <div x-show="open" x-collapse class="mt-0.5 space-y-0 pl-1">
                                 <ul class="space-y-0">
                                     @forelse ($category->channels as $channel)
                                         {{-- Ensure channel object exists and is not a DM --}}
                                         @if($channel && !$channel->is_dm)
                                         <li
                                             wire:key="channel-{{ $channel->id }}"
                                             class="relative group rounded flex items-center"
                                             :class="{ 'bg-gray-700 dark:bg-gray-750': editingChannelId === '{{ $channel->id }}' }"
                                             @contextmenu.prevent="channelContextMenu = { show: true, x: $event.clientX, y: $event.clientY, id: '{{ $channel->id }}' };"
                                         >
                                             {{-- Inline Editing --}}
                                             <div x-show="editingChannelId === '{{ $channel->id }}'" class="flex items-center py-1 px-1.5 w-full">
                                                 <input
                                                     type="text"
                                                     wire:model="channelName"
                                                     x-ref="channel-name-{{ $channel->id }}"
                                                     class="flex-grow bg-gray-600 dark:bg-gray-800 border-none rounded py-1 px-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                     @keydown.enter.prevent="$wire.renameChannel('{{ $channel->id }}')"
                                                     @keydown.escape.prevent="editingChannelId = null; $wire.cancelRename()"
                                                     @click.outside="if(editingChannelId === '{{ $channel->id }}') { editingChannelId = null; $wire.cancelRename() }"
                                                 />
                                                 <div class="flex ml-1 space-x-0.5 flex-shrink-0">
                                                     <button @click="$wire.renameChannel('{{ $channel->id }}')" class="text-green-400 hover:text-green-300 p-1 rounded hover:bg-gray-700 dark:hover:bg-gray-600" x-tooltip="'Save'">
                                                         <x-heroicon-o-check class="h-4 w-4" />
                                                     </button>
                                                     <button @click="editingChannelId = null; $wire.cancelRename()" class="text-red-400 hover:text-red-300 p-1 rounded hover:bg-gray-700 dark:hover:bg-gray-600" x-tooltip="'Cancel'">
                                                         <x-heroicon-o-x-mark class="h-4 w-4" />
                                                     </button>
                                                 </div>
                                             </div>

                                             {{-- Normal Channel Display --}}
                                             <div x-show="editingChannelId !== '{{ $channel->id }}'" class="flex items-center w-full">
                                                 <button
                                                     wire:click="selectChannel('{{ $channel->id }}')"
                                                     @click="unreadChannels.delete('{{ $channel->id }}'); $wire.dispatch('markChannelAsRead', { channelId: '{{ $channel->id }}' });"
                                                     class="flex items-center w-full text-left py-1 px-2 rounded text-sm font-medium transition-colors duration-150 justify-between relative group"
                                                     :class="{
                                                         'bg-primary-600/20 dark:bg-primary-500/15 text-white font-semibold': '{{ $activeChannelId }}' === '{{ $channel->id }}',
                                                         'text-gray-400 hover:text-white hover:bg-primary-500/5 dark:hover:bg-primary-500/10': '{{ $activeChannelId }}' !== '{{ $channel->id }}',
                                                         'font-semibold text-white': unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}' // Unread Bold
                                                     }"
                                                 >
                                                     <div class="flex items-center flex-grow min-w-0">
                                                         {{-- Channel icon --}}
                                                         <span class="mr-1.5 flex-shrink-0"
                                                               :class="{
                                                                    'text-white': '{{ $activeChannelId }}' === '{{ $channel->id }}' || unreadChannels.has('{{ $channel->id }}'),
                                                                    'text-gray-500': '{{ $activeChannelId }}' !== '{{ $channel->id }}' && !unreadChannels.has('{{ $channel->id }}')
                                                               }">
                                                             @if($channel->type === 'text') # @endif {{-- Simpler icons --}}
                                                             {{-- TODO: Add other icons --}}
                                                         </span>
                                                         <span class="truncate flex-grow">{{ $channel->name }}</span>
                                                     </div>
                                                     {{-- Unread indicator (white dot) --}}
                                                     <span x-show="unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}'"
                                                           class="absolute left-[-6px] top-1/2 transform -translate-y-1/2 h-2 w-2 bg-white rounded-full">
                                                     </span>
                                                     {{-- Actions Button (dots) --}}
                                                    <button
                                                        class="ml-1 flex-shrink-0 opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-full text-gray-500 hover:text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-600 transition-all duration-150"
                                                        @click.stop="channelContextMenu = { show: true, x: $event.clientX, y: $event.clientY, id: '{{ $channel->id }}' };"
                                                    >
                                                        <x-heroicon-s-ellipsis-vertical class="h-3.5 w-3.5" />
                                                    </button>
                                                 </button>
                                             </div>
                                         </li>
                                         @endif
                                     @empty
                                         <li class="py-1 px-2 text-sm text-gray-500 italic">No channels in this category</li>
                                     @endforelse
                                 </ul>
                             </div>
                         </div>
                         @endif
                     @endforeach
                 </nav>
             @endif
        </div>

        {{-- Include Modals --}}
        @include('livewire.chat.partials.sidebar-modals')

        {{-- Context Menu --}}
        <div
            x-show="channelContextMenu.show"
            x-cloak
            :style="`position: fixed; left: ${channelContextMenu.x}px; top: ${channelContextMenu.y}px; z-index: 50;`"
            class="bg-gray-800 border border-gray-700 rounded-md shadow-lg overflow-hidden w-40"
            x-transition
            @click.away="channelContextMenu.show = false"
        >
            {{-- Context menu content remains similar, targeting channels only --}}
            <div class="py-1">
                <button
                    @click="editingChannelId = channelContextMenu.id; channelContextMenu.show = false; $nextTick(() => $refs['channel-name-'+editingChannelId]?.focus())"
                    wire:click="startRenameChannel(channelContextMenu.id)"
                    class="w-full text-left px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-700 hover:text-white flex items-center transition-colors duration-150"
                >
                    <x-heroicon-o-pencil class="h-4 w-4 mr-2" />
                    Rename
                </button>
                <button
                    @click="channelContextMenu.show = false; showDeleteConfirm = true; selectedChannel = channelContextMenu.id"
                    wire:click="startDeleteChannel(channelContextMenu.id)"
                    class="w-full text-left px-3 py-1.5 text-sm text-red-400 hover:bg-red-600 hover:text-white flex items-center transition-colors duration-150"
                >
                     <x-heroicon-o-trash class="h-4 w-4 mr-2" />
                    Delete
                </button>
             </div>
         </div>
    </div> {{-- End Main Content Area --}}

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar styles (Tailwind plugin might be better) */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #9ca3af #f3f4f6; /* Adjust colors for light theme */
        }
        .dark .scrollbar-thin {
             scrollbar-color: #4b5563 #1f2937; /* Adjust colors for dark theme */
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px; /* Add height for horizontal scrollbar */
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent; /* Make track transparent */
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #9ca3af; /* gray-400 */
            border-radius: 3px;
        }
         .dark .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* gray-600 */
         }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background-color: #6b7280; /* gray-500 */
        }
        .dark .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background-color: #374151; /* gray-700 */
        }
    </style>
</div>
