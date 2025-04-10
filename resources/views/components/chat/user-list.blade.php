<div class="p-3 border-b border-gray-200 dark:border-gray-700 shadow-sm fi-topbar sticky top-0 z-10 bg-gray-100 dark:bg-gray-800/75 flex items-center justify-between">
    <h2 class="font-semibold text-base text-gray-950 dark:text-white truncate">Members</h2>
    {{-- Mobile Close Button --}}
    <button 
        @click="$dispatch('close-panel')" 
        class="lg:hidden fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-200 dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500"
        title="Close User List"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
</div>

{{-- Scrollable User List --}}
<div class="flex-1 overflow-y-auto p-4 space-y-5 scrollable">

    {{-- Online Section --}}
    <div>
        <h3 class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-2.5 px-1">Online — 3</h3>
        <div class="space-y-2">
            {{-- User Entry - Add slight padding, adjust hover --}}
            <div 
                {{-- Add wire:click.prevent="openUserProfile(userId)" later --}}
                class="flex items-center space-x-3 cursor-pointer p-1.5 rounded-lg hover:bg-gray-500/10 dark:hover:bg-gray-500/10 group"
            >
                <div class="relative flex-shrink-0">
                    @php $username = 'OnlineUser1'; $bgColor = 'eebebe'; $textColor = 'ffffff'; @endphp
                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=32" alt="{{ $username }}">
                    <span class="absolute -bottom-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-success-500 ring-2 ring-white dark:ring-gray-800/75"></span> {{-- Status: Online --}}
                </div>
                <span class="font-medium text-sm text-gray-950 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 truncate">{{ $username }}</span>
            </div>
            {{-- User Entry --}}
            <div class="flex items-center space-x-3 cursor-pointer p-1.5 rounded-lg hover:bg-gray-500/10 dark:hover:bg-gray-500/10 group">
                <div class="relative flex-shrink-0">
                    @php $username = 'IdleUser'; $bgColor = 'eebebe'; $textColor = 'ffffff'; @endphp
                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=32" alt="{{ $username }}">
                    <span class="absolute -bottom-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-warning-500 ring-2 ring-white dark:ring-gray-800/75"></span> {{-- Status: Idle --}}
                </div>
                <span class="font-medium text-sm text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 truncate">{{ $username }}</span>
            </div>
            {{-- User Entry --}}
            <div class="flex items-center space-x-3 cursor-pointer p-1.5 rounded-lg hover:bg-gray-500/10 dark:hover:bg-gray-500/10 group">
                <div class="relative flex-shrink-0">
                    @php $username = 'BusyUser'; $bgColor = 'eebebe'; $textColor = 'ffffff'; @endphp
                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=32" alt="{{ $username }}">
                    <span class="absolute -bottom-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-danger-500 ring-2 ring-white dark:ring-gray-800/75"></span> {{-- Status: Busy/DND --}}
                </div>
                <span class="font-medium text-sm text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 truncate">{{ $username }}</span>
            </div>
        </div>
    </div>

    {{-- Offline Section --}}
     <div>
        <h3 class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mt-4 mb-2.5 px-1">Offline — 1</h3>
        <div class="space-y-2">
            {{-- User Entry --}}
            <div class="flex items-center space-x-3 cursor-pointer p-1.5 rounded-lg hover:bg-gray-500/10 dark:hover:bg-gray-500/10 group opacity-60">
                <div class="relative flex-shrink-0">
                    @php $username = 'OfflineUser'; $bgColor = '949cbb'; $textColor = 'ffffff'; @endphp
                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=32" alt="{{ $username }}">
                    {{-- No status dot for offline --}}
                </div>
                <span class="font-medium text-sm text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 truncate">{{ $username }}</span>
            </div>
        </div>
    </div>

</div> 