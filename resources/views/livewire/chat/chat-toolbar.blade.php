<div class="flex items-center gap-2">
    <!-- Model selector -->
    <div class="relative" x-data="{ open: false }">
        <button 
            type="button" 
            class="flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
            @click="open = !open"
        >
            <span>{{ $selectedModel }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
            </svg>
        </button>
        <div 
            x-show="open" 
            @click.away="open = false"
            class="absolute left-0 mt-1 w-48 rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-10"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
        >
            <div class="py-1">
                @foreach($availableModels as $model)
                <button 
                    type="button"
                    wire:click="changeModel('{{ $model }}')"
                    class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 {{ $selectedModel === $model ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                    @click="open = false"
                >
                    {{ $model }}
                </button>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Style selector -->
    <div class="relative" x-data="{ open: false }">
        <button 
            type="button" 
            class="flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
            @click="open = !open"
        >
            <span>{{ $selectedStyle }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
            </svg>
        </button>
        <div 
            x-show="open" 
            @click.away="open = false"
            class="absolute left-0 mt-1 w-48 rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-10"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
        >
            <div class="py-1">
                @foreach($availableStyles as $styleKey => $styleName)
                <button 
                    type="button"
                    wire:click="changeStyle('{{ $styleKey }}')"
                    class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 {{ $selectedStyle === $styleKey ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                    @click="open = false"
                >
                    {{ $styleName }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- New chat button -->
    <button 
        type="button" 
        wire:click="newConversation"
        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
    >
        New chat
    </button>
</div>
