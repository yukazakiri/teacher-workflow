<div 
    {{ $attributes->merge(['class' => 'flex items-center justify-between p-3 shadow-sm bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 fi-topbar']) }}
>
    <div class="flex items-center space-x-2 min-w-0">
        {{-- Mobile Channel List Toggle --}}
        <button 
            @click="$dispatch('toggle-channels')" 
            class="lg:hidden fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500"
            title="Toggle Channel List"
        >
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>

        {{-- Channel Name & Icon --}}
        <span class="text-gray-500 dark:text-gray-400 flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h13.5m-13.5 7.5h13.5m-1.5-15l-1.5 15m-10.5-15l-1.5 15M7.5 3l-1.5 15M16.5 3l-1.5 15" />
            </svg>
        </span>
        <span class="font-semibold text-gray-950 dark:text-white truncate">general</span>
        <span class="text-sm text-gray-500 dark:text-gray-400 hidden md:block truncate">| General chat and discussions</span>
    </div>

    {{-- Header Icons --}}
    <div class="flex items-center space-x-1 sm:space-x-3 flex-shrink-0">
        {{-- Standard Icons (Notifications, Pins, etc.) --}}
        <button title="Notifications" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500">
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.017 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
        </button>
         <button title="Pinned Messages" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
        </button>

        {{-- User List Toggle Button --}}
        <button 
            @click="$dispatch('toggle-users')"
            :class="{ 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400': userListOpen }" 
            class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:focus:ring-primary-500"
            title="Toggle User List"
        >
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.94-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.06 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
        </button>

         {{-- Search Input - Use Filament input styling --}}
        <div class="relative hidden md:block">
             <div class="fi-input-wrapper flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="fi-input-icon h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path></svg>
                </div>
                <input type="text" placeholder="Search" class="fi-input block w-full border-none bg-transparent py-1.5 pe-3 ps-10 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:text-gray-400 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:text-gray-500 sm:text-sm sm:leading-6">
            </div>
        </div>
    </div>
</div> 