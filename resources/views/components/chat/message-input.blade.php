<div class="flex items-center bg-white dark:bg-gray-800 px-4 py-2 border-t border-gray-200 dark:border-gray-700 relative">
    {{-- File Upload Button --}}
    <button class="mr-2 fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800/50 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>

    {{-- Message Input - Use Filament Textarea/TextInput styling --}}
    <div class="flex-1 fi-input-wrapper flex rounded-lg shadow-sm ring-1 transition duration-75 focus-within:ring-2 bg-white dark:bg-gray-700 ring-gray-950/10 dark:ring-white/20 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
        {{-- Consider using a textarea for multi-line messages --}}
        <input type="text" placeholder="Message #general" class="fi-input block w-full border-none bg-transparent py-1.5 px-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:text-gray-400 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:text-gray-500 sm:text-sm sm:leading-6">
        {{-- Ideally bind this with wire:model --}}
    </div>

    {{-- Action Buttons --}}
    <div class="flex space-x-1 ml-2">
        <button title="GIF" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800/50 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.375a6 6 0 0 0 6-6 6 6 0 0 0-6-6 6 6 0 0 0-6 6 6 6 0 0 0 6 6ZM12 18.375v2.625M12 5.625v-2.625M5.625 12h-2.625m18 0h-2.625M18.375 18.375l1.875 1.875m-15-15 1.875 1.875m15 0-1.875 1.875m-15 15 1.875-1.875" />
            </svg>
        </button>
        <button title="Emoji" class="fi-icon-btn inline-flex items-center justify-center rounded-full ring-1 disabled:pointer-events-none disabled:opacity-70 ring-gray-950/10 dark:ring-white/20 w-8 h-8 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800/50 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" />
            </svg>
        </button>
         {{-- Maybe add a Send button? --}}
    </div>
</div> 