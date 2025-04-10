{{-- Server Header/Name - Align with Filament panel header --}}
<div class="p-3 border-b border-gray-200 dark:border-gray-700 shadow-sm fi-topbar sticky top-0 z-10 bg-inherit flex items-center justify-between">
    <h2 class="font-semibold text-lg text-gray-950 dark:text-white truncate">Server Name</h2>
    {{-- Mobile Close Button --}}
    <button 
        @click="$dispatch('close-panel')" 
        class="lg:hidden fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500"
        title="Close Channel List"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
</div>

{{-- Scrollable Channel List --}}
<div class="flex-1 overflow-y-auto p-3 space-y-4 scrollable">
    {{-- Channel Category --}}
    <div>
        {{-- Use Filament's subtle text color for category headers --}}
        {{-- Add wire:click.prevent="toggleCategory('text')" later --}}
        <button type="button" class="flex items-center justify-between w-full text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white mb-1 rounded p-1 focus:outline-none focus:ring-1 focus:ring-primary-500">
            <span>Text Channels</span>
            {{-- Add chevron for open/close state later --}}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /> 
            </svg>
        </button>
        <div class="space-y-1">
            {{-- Active Channel - Use primary color accent and hover state --}}
            {{-- Add wire:click.prevent="selectChannel(channelId)" later --}}
            <a href="#" class="flex items-center space-x-2 px-2 py-1 rounded bg-primary-500/10 dark:bg-primary-500/20 text-primary-700 dark:text-primary-400 font-medium group relative">
                 <span class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500 rounded-r-full"></span>
                <span class="text-gray-500 dark:text-gray-400 ml-2"> {{-- Add margin to account for active bar --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-1.5-15l-1.5 15m-10.5-15l-1.5 15M7.5 3l-1.5 15M16.5 3l-1.5 15" /></svg>
                </span>
                <span>general</span>
                {{-- Maybe add notification count/mention badge here --}}
                 {{-- <span class="ml-auto text-xs font-normal bg-danger-500 text-white rounded-full px-1.5 py-0.5">3</span> --}}
            </a>
            {{-- Inactive Channel - More subtle hover --}}
             {{-- Add wire:click.prevent="selectChannel(channelId)" later --}}
            <a href="#" class="flex items-center space-x-2 px-2 py-1 rounded hover:bg-gray-500/10 dark:hover:bg-gray-500/10 text-gray-500 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white group">
                 <span class="text-gray-500 dark:text-gray-400 ml-3"> {{-- Add margin to align with active channel icon --}}
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-1.5-15l-1.5 15m-10.5-15l-1.5 15M7.5 3l-1.5 15M16.5 3l-1.5 15" /></svg>
                 </span>
                <span>random</span>
            </a>
        </div>
    </div>

    {{-- Another Channel Category --}}
    <div>
         {{-- Add wire:click.prevent="toggleCategory('voice')" later --}}
        <button type="button" class="flex items-center justify-between w-full text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white mb-1 rounded p-1 focus:outline-none focus:ring-1 focus:ring-primary-500">
            <span>Voice Channels</span>
             {{-- Add chevron for open/close state later --}}
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>
        <div class="space-y-1">
             {{-- Add wire:click.prevent="joinVoiceChannel(channelId)" later --}}
            <a href="#" class="flex items-center space-x-2 px-2 py-1 rounded hover:bg-gray-500/10 dark:hover:bg-gray-500/10 text-gray-500 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white group">
                <span class="text-gray-500 dark:text-gray-400 ml-3"> {{-- Add margin to align with active channel icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l9.75 7.5M6.75 15.75l9.75-7.5" /></svg>
                </span>
                <span>General Voice</span>
                 {{-- User avatars in voice --}}
                 <div class="flex -space-x-1 overflow-hidden ml-auto">
                    {{-- Use UI Avatars --}}
                    @php 
                        $user1Name = 'User1';
                        $user2Name = 'User2'; 
                        $bgColor = 'eebebe'; // Your primary color hex without #
                        $textColor = 'ffffff'; // White text for contrast
                    @endphp
                    <img class="inline-block h-4 w-4 rounded-full ring-1 ring-white dark:ring-gray-800/75" src="https://ui-avatars.com/api/?name={{ urlencode($user1Name) }}&color={{ $textColor }}&background={{ $bgColor }}&size=20" alt="{{ $user1Name }}">
                    <img class="inline-block h-4 w-4 rounded-full ring-1 ring-white dark:ring-gray-800/75" src="https://ui-avatars.com/api/?name={{ urlencode($user2Name) }}&color={{ $textColor }}&background={{ $bgColor }}&size=20" alt="{{ $user2Name }}">
                </div>
            </a>
        </div>
    </div>
</div>

{{-- User Area (Bottom) - Align with Filament sidebar bottom --}}
<div class="p-2 border-t border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 flex items-center justify-between sticky bottom-0">
    <div class="flex items-center space-x-2">
        <div class="relative">
            {{-- Use UI Avatar for the logged-in user --}}
             @php 
                $loggedInUserName = auth()->user()?->name ?? 'User';
                $bgColor = 'eebebe'; // Your primary color hex without #
                $textColor = 'ffffff'; // White text for contrast
             @endphp
            <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($loggedInUserName) }}&color={{ $textColor }}&background={{ $bgColor }}&size=32" alt="{{ $loggedInUserName }}">
            <span class="absolute -bottom-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-success-500 ring-2 ring-white dark:ring-gray-800/50"></span> {{-- Status indicator --}}
        </div>
        <div>
            <div class="text-sm font-medium text-gray-950 dark:text-white truncate">{{ $loggedInUserName }}</div>
            {{-- Optional: User status/tag --}}
            {{-- <div class="text-xs text-gray-500 dark:text-gray-400">Online</div> --}}
        </div>
    </div>
    <div class="flex space-x-1">
         {{-- Use Filament icon button styling --}}
          {{-- Add wire:click for mic toggle later --}}
         <button type="button" title="Mute/Unmute Mic" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6 6 6 0 0 0-6-6 6 6 0 0 0-6 6 6 6 0 0 0 6 6ZM12 18.75a9.75 9.75 0 1 1 0-19.5 9.75 9.75 0 0 1 0 19.5Z" /></svg> {{-- Mic Icon --}}
        </button>
         {{-- Add wire:click for deafen toggle later --}}
         <button type="button" title="Deafen/Undeafen" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg> {{-- Headphones Icon --}}
        </button>
         {{-- Add wire:click for user settings modal later --}}
         <button type="button" title="User Settings" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.646.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.004.827c-.292.24-.437.613-.43.992a6.759 6.759 0 0 1 0 1.555c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.333.184-.582.496-.646.87l-.213 1.28c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.646-.87-.074-.04-.147-.083-.22-.127-.324-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.759 6.759 0 0 1 0-1.555c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.333-.184.582-.496.646-.87l.213-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> {{-- Settings Icon --}}
        </button>
    </div>
</div> 