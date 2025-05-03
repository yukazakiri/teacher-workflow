<div
    class="h-full flex flex-col bg-gray-800 text-gray-300"
    x-data="{
        editingChannelId: null,
        editingChannelName: '',
        channelContextMenu: { show: false, x: 0, y: 0, id: null },
        unreadChannels: new Set()
    }"
    @click.away="channelContextMenu.show = false"
    @channel-rename-initiated.window="editingChannelId = $event.detail; $nextTick(() => $refs['channel-name-'+editingChannelId]?.focus())"
    @channel-rename-complete.window="editingChannelId = null"
    @channel-rename-cancelled.window="editingChannelId = null"
    @keydown.escape.window="channelContextMenu.show = false; editingChannelId = null;"
    @new-message.window="if($event.detail.channelId && $event.detail.channelId !== '{{ $activeChannelId }}') { unreadChannels.add($event.detail.channelId); }"
    @channel-read.window="unreadChannels.delete($event.detail.channelId);"
>
    <div
        class="flex-shrink-0 h-12 flex items-center justify-between px-3 bg-gray-850 border-b border-black/20 z-10 shadow-sm"
        x-data="{ dropdownOpen: false }"
        @click.away="dropdownOpen = false"
    >
        <span class="text-white font-semibold text-sm truncate mr-2">{{ $team->name ?? 'Team' }}</span>

        <div class="relative">
            <button
                @click="dropdownOpen = !dropdownOpen"
                class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded-md focus:outline-none transition-colors duration-150"
                aria-label="Team Actions"
                aria-haspopup="true"
                :aria-expanded="dropdownOpen.toString()"
            >
                <x-heroicon-s-ellipsis-vertical class="h-5 w-5" />
            </button>

            <div
                x-show="dropdownOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-gray-900 border border-black/30 rounded-md shadow-lg py-1 z-20"
            >
                <button
                    type="button"
                    wire:click="mountAction('createChannelAction')"
                    @click="dropdownOpen = false"
                    class="w-full text-left px-3 py-1.5 text-sm text-gray-300 hover:bg-primary-600 hover:text-white flex items-center transition-colors duration-150"
                >
                    <x-heroicon-o-plus-circle class="h-4 w-4 mr-2.5" />
                    Create Channel
                </button>
                <button
                    type="button"
                    wire:click="mountAction('createCategoryAction')"
                    @click="dropdownOpen = false"
                    class="w-full text-left px-3 py-1.5 text-sm text-gray-300 hover:bg-primary-600 hover:text-white flex items-center transition-colors duration-150"
                >
                    <x-heroicon-o-folder-plus class="h-4 w-4 mr-2.5" />
                    Create Category
                </button>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">

        <div class="flex-shrink-0 bg-gray-800 p-2 border-b border-black/20 shadow-sm space-y-3">
             <div class="relative px-1">
                 <input
                     wire:model.live.debounce.300ms="searchTerm"
                     type="text"
                     placeholder="Search DMs..."
                     class="w-full bg-gray-700 border border-gray-600 rounded-md py-1.5 px-3 text-sm text-gray-200 placeholder-gray-400 focus:ring-primary-500 focus:border-primary-500 focus:outline-none"
                 >
                 <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                     <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400"/>
                 </div>
             </div>

            <div class="flex space-x-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800 max-w-full px-1">
                 @if($teamMembers->isEmpty())
                     <p class="text-xs text-gray-500 px-1 italic">No other members found.</p>
                 @else
                     <div class="flex space-x-2 flex-nowrap">
                         @foreach($teamMembers as $member)
                             @if($member && $member->id !== Auth::id())
                             <button
                                wire:click="startDirectMessage('{{ $member->id }}')"
                                wire:key="dm-user-{{ $member->id }}"
                                class="relative flex-shrink-0 focus:outline-none rounded-full transition-transform duration-150 transform hover:scale-105 group"
                                :class="{ 'ring-2 ring-offset-2 ring-offset-gray-800 ring-primary-500': '{{ $activeDmOtherUserId ?? null }}' === '{{ $member->id }}' }"
                                 x-tooltip.bottom="'{{ $member->name }}'"
                             >
                                 <div class="w-9 h-9 rounded-full overflow-hidden shadow-md border border-gray-700">
                                     <img
                                        src="{{ $member->profile_photo_url }}"
                                        alt="{{ $member->name }}"
                                        class="h-full w-full object-cover"
                                        onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%239ca3af%22><path d=%22M7.5 6.5C7.5 8.981 9.519 11 12 11s4.5-2.019 4.5-4.5S14.481 2 12 2 7.5 4.019 7.5 6.5zM20 21h1v-1c0-3.859-3.141-7-7-7h-4c-3.86 0-7 3.141-7 7v1h17z%22/></svg>'"
                                     >
                                 </div>
                                 <span class="absolute bottom-[-1px] right-[-1px] block h-3 w-3 rounded-full bg-green-500 border-2 border-gray-800 ring-1 ring-gray-800"></span>
                              </button>
                             @endif
                         @endforeach
                     </div>
                 @endif
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-1.5 pt-2 pb-4 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">
             @if ($categories->isEmpty())
                 <div class="text-center text-gray-500 py-10 px-2">
                     <p class="mb-4 text-sm">No channels found.</p>
                         @if(Auth::user()?->id === $team?->user_id)
                             <button wire:click="mountAction('createCategoryAction')" class="text-sm text-primary-400 hover:text-primary-300">Create a Category</button>
                             or
                             <button wire:click="mountAction('createChannelAction')" class="text-sm text-primary-400 hover:text-primary-300">Create a Channel</button>
                     @endif
                 </div>
             @else
                 <nav class="space-y-3">
                     @foreach ($categories as $category)
                         @if($category)
                         <div x-data="{ open: true }" wire:key="category-{{ $category->id }}">
                             <div class="flex items-center justify-between group mb-1 px-1">
                                 <button @click="open = !open" class="flex items-center py-1 text-xs font-bold text-gray-400 uppercase hover:text-gray-200 focus:outline-none transition-colors duration-150 rounded w-full">
                                     <x-heroicon-s-chevron-right class="h-3 w-3 mr-0.5 transition-transform duration-150 shrink-0 text-gray-500 group-hover:text-gray-400"  />
                                     <span class="truncate">{{ $category->name }}</span>
                                 </button>
                                 @if(Auth::user()?->id === $team?->user_id)
                                 <button
                                     wire:click="mountAction('createChannelAction', { categoryId: '{{ $category->id }}' })"
                                     class="p-0.5 text-gray-400 hover:text-white opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none rounded hover:bg-gray-700 shrink-0"
                                     x-tooltip="'New Channel in {{ $category->name }}'"
                                 >
                                     <x-heroicon-o-plus class="h-3.5 w-3.5" />
                                 </button>
                                 @endif
                             </div>

                             <div x-show="open" x-collapse class="mt-0.5 space-y-0.5 pl-2">
                                 <ul class="space-y-0.5">
                                     @forelse ($category->channels as $channel)
                                         @if($channel && !$channel->is_dm)
                                         <li
                                             wire:key="channel-{{ $channel->id }}"
                                             class="relative group rounded flex items-center"
                                             :class="{ 'bg-gray-700': editingChannelId === '{{ $channel->id }}' }"
                                             @contextmenu.prevent="channelContextMenu = { show: true, x: $event.clientX, y: $event.clientY, id: '{{ $channel->id }}' }"
                                         >
                                             <div x-show="editingChannelId === '{{ $channel->id }}'" class="flex items-center py-0.5 px-1.5 w-full" x-trap.inert.noscroll="editingChannelId === '{{ $channel->id }}'">
                                                 <input
                                                     type="text"
                                                     wire:model.live="channelName"
                                                     x-ref="'channel-name-{{ $channel->id }}'"
                                                     class="flex-grow bg-gray-600 border-none rounded py-1 px-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                     @keydown.enter.prevent="$wire.renameChannel('{{ $channel->id }}')"
                                                     @keydown.escape.prevent="$wire.cancelRename()"
                                                     @click.outside="if(editingChannelId === '{{ $channel->id }}') { $wire.cancelRename() }"
                                                 />
                                                 <div class="flex ml-1 space-x-0.5 flex-shrink-0">
                                                     <button @click="$wire.renameChannel('{{ $channel->id }}')" class="text-green-400 hover:text-green-300 p-0.5 rounded hover:bg-gray-700" x-tooltip="'Save'">
                                                         <x-heroicon-o-check class="h-3.5 w-3.5" />
                                                     </button>
                                                     <button @click="$wire.cancelRename()" class="text-red-400 hover:text-red-300 p-0.5 rounded hover:bg-gray-700" x-tooltip="'Cancel'">
                                                         <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                                                     </button>
                                                 </div>
                                             </div>

                                             <div x-show="editingChannelId !== '{{ $channel->id }}'" class="flex items-center w-full group">
                                                 <div class="absolute left-[-6px] top-1/2 transform -translate-y-1/2 h-1.5 w-1 bg-white rounded-r-full transition-opacity duration-150"
                                                      :class="{ 'opacity-100': unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}', 'opacity-0': !unreadChannels.has('{{ $channel->id }}') || '{{ $activeChannelId }}' === '{{ $channel->id }}' }">
                                                 </div>

                                                 <a
                                                     href="#"
                                                     wire:click.prevent="selectChannel('{{ $channel->id }}')"
                                                     @click="unreadChannels.delete('{{ $channel->id }}'); $wire.dispatch('markChannelAsRead', { channelId: '{{ $channel->id }}' });"
                                                     class="flex items-center w-full text-left py-1 px-1.5 rounded text-sm transition-colors duration-150 justify-between relative group/channel"
                                                     :class="{
                                                         'bg-gray-700 text-white font-medium': '{{ $activeChannelId }}' === '{{ $channel->id }}',
                                                         'text-gray-400 hover:text-gray-100 hover:bg-gray-750': '{{ $activeChannelId }}' !== '{{ $channel->id }}',
                                                         'text-white font-medium': unreadChannels.has('{{ $channel->id }}') && '{{ $activeChannelId }}' !== '{{ $channel->id }}'
                                                     }"
                                                     aria-current="{{ $activeChannelId === $channel->id ? 'page' : 'false' }}"
                                                 >
                                                     <div class="flex items-center flex-grow min-w-0">
                                                         <span class="mr-1.5 flex-shrink-0 text-gray-500 group-hover/channel:text-gray-400"
                                                               :class="{ 'text-gray-200': '{{ $activeChannelId }}' === '{{ $channel->id }}' || unreadChannels.has('{{ $channel->id }}') }">
                                                             @if($channel->type === 'text') <span class="font-mono text-base leading-none">#</span>
                                                             @elseif($channel->type === 'announcement') <x-heroicon-o-megaphone class="h-4 w-4 inline-block"/>
                                                             @elseif($channel->type === 'voice') <x-heroicon-o-speaker-wave class="h-4 w-4 inline-block"/>
                                                             @else <span class="font-mono text-base leading-none">#</span>
                                                             @endif
                                                         </span>
                                                         <span class="truncate flex-grow" :class="{'text-white': '{{ $activeChannelId }}' === '{{ $channel->id }}'}">{{ $channel->name }}</span>
                                                     </div>

                                                    <button
                                                        class="ml-1 flex-shrink-0 opacity-0 group-hover/channel:opacity-100 focus:opacity-100 p-0.5 rounded text-gray-400 hover:text-white hover:bg-gray-600 transition-all duration-150"
                                                        @click.stop.prevent="channelContextMenu = { show: true, x: $event.clientX, y: $event.clientY, id: '{{ $channel->id }}' }"
                                                        x-tooltip="'Options'"
                                                        aria-label="Channel Options for {{ $channel->name }}"
                                                    >
                                                        <x-heroicon-s-cog-6-tooth class="h-3.5 w-3.5" />
                                                    </button>
                                                 </a>
                                             </div>
                                         </li>
                                         @endif
                                     @empty
                                         <li class="py-1 px-1.5 text-xs text-gray-500 italic">No channels yet</li>
                                     @endforelse
                                 </ul>
                             </div>
                         </div>
                         @endif
                     @endforeach
                 </nav>
             @endif
        </div>

        <div
            x-show="channelContextMenu.show"
            x-cloak
            :style="`position: fixed; left: ${channelContextMenu.x}px; top: ${channelContextMenu.y}px; z-index: 50;`"
            class="bg-gray-900 border border-black/30 rounded-md shadow-lg overflow-hidden w-48 text-sm"
            x-transition
            @click.away="channelContextMenu.show = false"
        >
            <div class="py-1">
                 <button
                    @click="editingChannelId = channelContextMenu.id; channelContextMenu.show = false; $nextTick(() => $refs['channel-name-'+editingChannelId]?.focus())"
                    class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-primary-600 hover:text-white flex items-center transition-colors duration-150"
                >
                    <x-heroicon-o-pencil class="h-4 w-4 mr-2.5" />
                    Edit Channel
                </button>
                 <div class="my-1 border-t border-black/20"></div>
                <button
                    type="button"
                    wire:click="mountAction('deleteChannelAction', { channelId: channelContextMenu.id })"
                    @click="channelContextMenu.show = false"
                    class="w-full text-left px-3 py-1.5 text-sm text-red-400 hover:bg-red-600 hover:text-white flex items-center transition-colors duration-150"
                >
                     <x-heroicon-o-trash class="h-4 w-4 mr-2.5" />
                    Delete Channel
                </button>
             </div>
         </div>
    </div>

    <x-filament-actions::modals />

    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-thin::-webkit-scrollbar { width: 8px; height: 8px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #2f3136; border-radius: 4px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background-color: #202225; border-radius: 4px; border: 2px solid #2f3136; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background-color: #1a1c1e; }
        .scrollbar-thin { scrollbar-width: thin; scrollbar-color: #202225 #2f3136; }
        .bg-gray-850 { background-color: #2f3136; }
        .bg-gray-750 { background-color: #36393f; }
    </style>
</div>
