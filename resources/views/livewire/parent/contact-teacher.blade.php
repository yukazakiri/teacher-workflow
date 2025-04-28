<div class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg">
    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
        <span class="text-sm font-medium">
            {{ strtoupper(substr($teacher->name ?? 'T', 0, 2)) }}
        </span>
    </div>
    <div>
        <p class="font-medium text-sm">{{ $teacher->name ?? __('Teacher') }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->email ?? __('No email available') }}</p>
    </div>
    <div class="ml-auto flex gap-2 flex-shrink-0">
        {{-- Add wire:click to the message button --}}
        <button
            wire:click="startDirectMessage"
            wire:loading.attr="disabled"
            wire:target="startDirectMessage"
            type="button"
            class="text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-1.5 rounded-md transition duration-150 ease-in-out"
            x-tooltip="'Send Direct Message'"
        >
             {{-- Show spinner when loading --}}
             <span wire:loading wire:target="startDirectMessage">
                 <svg class="animate-spin h-4 w-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                     <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                     <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                 </svg>
             </span>
             {{-- Show icon when not loading --}}
             <span wire:loading.remove wire:target="startDirectMessage">
                 <x-heroicon-m-chat-bubble-left-right class="h-4 w-4" />
             </span>
        </button>
         {{-- Keep the email button if needed, or remove it --}}
         {{-- <button class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-1.5 rounded-md">
             <x-heroicon-m-envelope class="h-4 w-4" />
         </button> --}}
    </div>
</div>
