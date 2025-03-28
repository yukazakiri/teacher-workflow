@php
    use Illuminate\Support\Str;
@endphp

<div class="min-h-screen bg-background" x-data="{ sidebarOpen: false }">

    {{-- Right Sidebar (Hidden by default) --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-40 w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 shadow-xl flex flex-col"
        @click.away="sidebarOpen = false"
        style="display: none;" {{-- Prevents flash of content --}}
    >
        {{-- Sidebar Header --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Chat History</h2>
            <button
                @click="sidebarOpen = false"
                class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
            >
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Sidebar Content (Chat List) --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            @forelse($this->getAllChats() as $chatItem) {{-- Assuming you have a method `getAllChats()` in your component --}}
            <div
                wire:click="loadConversation({{ $chatItem['id'] }})"
                @click="sidebarOpen = false" {{-- Close sidebar when a chat is loaded --}}
                class="cursor-pointer rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all group relative"
            >
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                        {{ Str::upper(substr($chatItem['title'], 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $chatItem['title'] }}</h3>
                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span class="truncate">{{ $chatItem['model'] }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $chatItem['last_activity'] }}</span>
                        </div>
                    </div>
                </div>
                <button
                    wire:click.stop="deleteConversation({{ $chatItem['id'] }})"
                    wire:confirm="Are you sure you want to delete this chat?"
                    class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity"
                    aria-label="Delete chat"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                      <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.426C1.98 4.845 1 6.148 1 7.5v1.562c0 4.97 3.493 9.13 8.25 10.438a.75.75 0 00.3 0c4.757-1.308 8.25-5.468 8.25-10.438V7.5c0-1.352-.98-2.655-2.635-2.871-.785-.206-1.57-.35-2.365-.426V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 1.5a1.25 1.25 0 011.25 1.25v.463c-.37.044-.74.1-.11.157H8.86c-.37-.056-.74-.113-1.11-.157V2.75A1.25 1.25 0 0110 1.5zM2.5 7.5c0-.718.56-1.353 1.355-1.492.715-.188 1.44-.321 2.18-.402a.75.75 0 01.75.75c0 .414-.336.75-.75.75-.52 0-1.023.08-1.5.231V15c0 .828.672 1.5 1.5 1.5h7c.828 0 1.5-.672 1.5-1.5V7.11c-.477-.15-.98-.23-1.5-.23a.75.75 0 01-.75-.75.75.75 0 01.715-.75c.74.08 1.465.214 2.18.402C16.94 6.147 17.5 6.782 17.5 7.5v1.562c0 4.1-2.92 7.74-7.03 8.895a.75.75 0 01-.44 0C5.92 17.002 2.5 13.362 2.5 9.062V7.5z" clip-rule="evenodd" /> {{-- Using a trash icon --}}
                      <path fill-rule="evenodd" d="M10 6a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0110 6zM8.25 6.75a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5zm3.5 0a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            @empty
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No chats yet.</p>
            @endforelse
            </div>

        {{-- Sidebar Footer --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
             <button
                wire:click="newConversation"
                @click="sidebarOpen = false"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" />
                </svg>
                New Chat
            </button>
        </div>
    </div>

    {{-- Overlay for mobile sidebar --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

    {{-- Main Content Area --}}
    <div class="flex flex-col min-h-screen">

        {{-- Header --}}
        @if ($conversation)
            {{-- Conversation Header --}}
            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Back Button (New) --}}
                    <button
                        wire:click="newConversation"
                        class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mr-1"
                        title="Back to home"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-data="{ isRenaming: false, newTitle: @js($conversation->title) }">
                        {{-- Rest of the conversation header --}}
                        <template x-if="!isRenaming">
                            <div class="flex items-center gap-2 min-w-0">
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white truncate cursor-pointer hover:text-primary-600 dark:hover:text-primary-400"
                                    @click="isRenaming = true; $nextTick(() => $refs.titleInput.focus())"
                                    title="Rename conversation"
                                >
                                    {{ $conversation->title }}
                                </h2>
                                <button
                                    @click="isRenaming = true; $nextTick(() => $refs.titleInput.focus())"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0"
                                    aria-label="Rename conversation"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                        <path d="M227.31,73.37,182.63,28.68a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96a16,16,0,0,0,0-22.63Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="isRenaming">
                            <form @submit.prevent="isRenaming = false; if(newTitle.trim()) $wire.renameConversation(newTitle)" class="flex items-center gap-2 min-w-0">
                                <input
                                    x-ref="titleInput"
                                    x-model="newTitle"
                                    class="text-lg font-semibold text-gray-900 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-primary-500 dark:focus:border-primary-500 focus:ring-0 px-1 py-0 leading-tight w-full"
                                    @keydown.escape="isRenaming = false"
                                    @blur="isRenaming = false; if(newTitle.trim()) $wire.renameConversation(newTitle)"
                                />
                                <button type="submit" class="text-primary-600 dark:text-primary-400 flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                                        <path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,11.32L96,188.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
                                    </svg>
                                </button>
                            </form>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        wire:click="regenerateLastMessage"
                        wire:loading.attr="disabled"
                        wire:target="regenerateLastMessage"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50"
                        title="Regenerate last response (⌘/Ctrl+R)"
                        aria-label="Regenerate last response"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256" class="text-gray-500 dark:text-gray-400">
                            <path d="M197.66,186.34a8,8,0,0,1,0,11.32C196.58,198.73,170.82,224,128,224c-23.36,0-46.13-9.1-66.28-26.41L45.66,213.66A8,8,0,0,1,32,208V160a8,8,0,0,1,8-8H88a8,8,0,0,1,5.66,13.66L73.08,186.24C86.08,197.15,104.83,208,128,208c36.27,0,56.67-20.53,56.82-20.71A8,8,0,0,1,197.66,186.34Zm26.34-90.34H176a8,8,0,0,0-5.66,13.66l20.58,20.58C177.92,141.15,159.17,152,136,152c-36.27,0-56.67-20.53-56.82-20.71a8,8,0,0,1-11.32,11.32C68.94,143.68,94.7,168.9,136,168.9c23.36,0,46.13-9.1,66.28-26.41l16.06,16.07A8,8,0,0,0,232,152V104A8,8,0,0,0,224,96Z"></path>
                        </svg>
                        <span>Regenerate</span>
                    </button>
                    <button
                        wire:click="newConversation"
                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                    >
                        New chat
                    </button>
                </div>
            </div>
        @else
            {{-- If we're at the home/initial state, we could add a visual breadcrumb/navigation hint --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Teacher Assistant</h1>
                    <span class="text-sm px-2 py-0.5 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-full">Home</span>
                </div>

                {{-- Optionally add other controls like settings, help, etc. here --}}
                <div>
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 rounded-full"
                        title="View chat history"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Main Content --}}
        <main class="flex-1 flex flex-col mx-auto max-w-4xl w-full px-4 sm:px-6 lg:px-8 py-8">

            @if (!$conversation)
                {{-- Initial State: Welcome, Input, Quick Actions, Recent Chats Grid --}}
                <div class="flex-1 flex flex-col items-center w-full">
                    {{-- Welcome Message & Suggestions --}}
                    <div class="w-full max-w-3xl mb-8 text-center">
                         <div class="text-primary-600 dark:text-primary-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 256 256" class="mx-auto">
                                <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            Good {{ now()->format('A') === 'AM' ? 'morning' : (now()->format('H') < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}!
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">How can I help you today?</p>

                        {{-- Suggestion Buttons --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left max-w-lg mx-auto mb-8">
                             <button
                                wire:click="setPrompt('Help me create a lesson plan for a high school English class on Shakespeare.')"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
                            >
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Create a lesson plan</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">For a high school English class</div>
                            </button>

                            <button
                                wire:click="setPrompt('Generate 5 discussion questions about climate change for a middle school science class.')"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
                            >
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Generate discussion questions</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">About climate change for middle school</div>
                            </button>

                            <button
                                wire:click="setPrompt('Help me draft a parent-teacher conference email template.')"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
                            >
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Draft an email template</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">For parent-teacher conferences</div>
                            </button>

                            <button
                                wire:click="setPrompt('Suggest activities for a virtual classroom to increase student engagement.')"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
                            >
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Suggest virtual activities</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">To increase student engagement</div>
                            </button>
                        </div>
                    </div>

                    {{-- Chat Input Form (Initial State) --}}
                    <form wire:submit.prevent="sendMessage" class="w-full max-w-3xl sticky bottom-8 z-10">
                        <div class="relative rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
                            {{-- Message input area --}}
                            <div class="flex p-3 gap-3 items-start">
                            <textarea
                                wire:model.live="message"
                                    placeholder="Start typing your message here..."
                                    class="flex-1 min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                                wire:keydown.enter.prevent="sendMessage"
                                aria-label="Message input"
                                x-data="{
                                    resize() {
                                            $el.style.height = 'auto'; // Reset height
                                        $el.style.height = $el.scrollHeight + 'px';
                                    }
                                }"
                                x-init="resize()"
                                @input="resize()"
                            ></textarea>
                        <button
                            type="submit"
                                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                                    :disabled="!$wire.message.trim()"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                      <path d="M6.28 5.22a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.06l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">Send message</span>
                                </button>
                            </div>

                            {{-- Bottom toolbar --}}
                            <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 p-2">
                                <div class="flex items-center gap-2">
                                    {{-- Model selector --}}
                                    <x-filament::dropdown placement="top-start">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v2.25A2.25 2.25 0 0111.25 18.5h-6.5A2.25 2.25 0 012.5 16.25V7.75A2.25 2.25 0 014.75 5.5H7V3.75A2.25 2.25 0 019.25 1.5h6.5A2.25 2.25 0 0115.988 3.012zM13.5 6.75a.75.75 0 000-1.5H9.25a.75.75 0 00-.75.75V11h3.25a.75.75 0 00.75-.75V6.75zm-3 6.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $selectedModel }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-filament::dropdown.list>
                                            @foreach($availableModels as $model)
                                                <x-filament::dropdown.list.item wire:click="changeModel('{{ $model }}')">
                                                    {{ $model }}
                                                </x-filament::dropdown.list.item>
                                            @endforeach
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>

                                    {{-- Style selector --}}
                                     <x-filament::dropdown placement="top-start">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path d="M11.03 2.97a.75.75 0 010 1.06l-4.72 4.72a.75.75 0 101.06 1.06l4.72-4.72a.75.75 0 011.06 0l4.72 4.72a.75.75 0 101.06-1.06l-4.72-4.72a.75.75 0 010-1.06l-4.72-4.72a.75.75 0 00-1.06 0l-4.72 4.72a.75.75 0 000 1.06l4.72 4.72a.75.75 0 101.06-1.06l-4.72-4.72a.75.75 0 010-1.06l4.72-4.72zM10 10.75a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM10 15.75a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM3 10.75a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75zM3 15.75a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" /> {{-- Using a style/palette icon --}}
                                                </svg>
                                                <span>{{ $availableStyles[$selectedStyle] ?? $selectedStyle }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                        </x-slot>
                                        <x-filament::dropdown.list>
                                        @foreach($availableStyles as $styleKey => $styleName)
                                                <x-filament::dropdown.list.item wire:click="changeStyle('{{ $styleKey }}')">
                                            {{ $styleName }}
                                                </x-filament::dropdown.list.item>
                                        @endforeach
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>
                        </div>

                        <div class="flex items-center">
                                    {{-- Character count --}}
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mr-3" x-data x-text="$wire.message.length + ' chars'"></div>

                                    {{-- New Chat Button --}}
                            <button
                                type="button"
                                wire:click="newConversation"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                            >
                                New chat
                            </button>
                                    {{-- Keyboard shortcut hint --}}
                                    <div class="hidden sm:flex items-center text-xs text-gray-400 dark:text-gray-500 ml-2">
                                        <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 mr-1">⌘/Ctrl</kbd>+<kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 mx-1">N</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

                    {{-- Recent Chats Grid --}}
                    <div class="w-full max-w-5xl mt-12">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-400 dark:text-gray-500">
                              <path fill-rule="evenodd" d="M7.75 2a.75.75 0 01.75.75V7.5h4.5V2.75a.75.75 0 011.5 0V7.5h.75a3 3 0 013 3v6.75a3 3 0 01-3 3H4a3 3 0 01-3-3V10.5a3 3 0 013-3h.75V2.75A.75.75 0 017.75 2zM4.5 10.5a1.5 1.5 0 00-1.5 1.5v6.75a1.5 1.5 0 001.5 1.5h11a1.5 1.5 0 001.5-1.5V12a1.5 1.5 0 00-1.5-1.5h-11z" clip-rule="evenodd" />
                    </svg>
                            Recent Chats
                </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($recentChats as $chat)
                <div
                    wire:click="loadConversation({{ $chat['id'] }})"
                                class="cursor-pointer rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all group relative"
                >
                                <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                                        {{ Str::upper(substr($chat['title'], 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $chat['title'] }}</h3>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="truncate">{{ $chat['model'] }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ $chat['last_activity'] }}</span>
                            </div>
                        </div>
                    </div>
                                <button
                                    wire:click.stop="deleteConversation({{ $chat['id'] }})"
                                    wire:confirm="Are you sure you want to delete this chat?"
                                    class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity"
                                    aria-label="Delete chat"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                      <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.426C1.98 4.845 1 6.148 1 7.5v1.562c0 4.97 3.493 9.13 8.25 10.438a.75.75 0 00.3 0c4.757-1.308 8.25-5.468 8.25-10.438V7.5c0-1.352-.98-2.655-2.635-2.871-.785-.206-1.57-.35-2.365-.426V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 1.5a1.25 1.25 0 011.25 1.25v.463c-.37.044-.74.1-.11.157H8.86c-.37-.056-.74-.113-1.11-.157V2.75A1.25 1.25 0 0110 1.5zM2.5 7.5c0-.718.56-1.353 1.355-1.492.715-.188 1.44-.321 2.18-.402a.75.75 0 01.75.75c0 .414-.336.75-.75.75-.52 0-1.023.08-1.5.231V15c0 .828.672 1.5 1.5 1.5h7c.828 0 1.5-.672 1.5-1.5V7.11c-.477-.15-.98-.23-1.5-.23a.75.75 0 01-.75-.75.75.75 0 01.715-.75c.74.08 1.465.214 2.18.402C16.94 6.147 17.5 6.782 17.5 7.5v1.562c0 4.1-2.92 7.74-7.03 8.895a.75.75 0 01-.44 0C5.92 17.002 2.5 13.362 2.5 9.062V7.5z" clip-rule="evenodd" /> {{-- Using a trash icon --}}
                                      <path fill-rule="evenodd" d="M10 6a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0110 6zM8.25 6.75a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5zm3.5 0a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                </div>
                @empty
                            <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12">
                                <div class="text-gray-400 dark:text-gray-500 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">No recent chats found.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">Start a new conversation above!</p>
                </div>
                @endforelse
            </div>
        </div>
                </div>

            @else
                {{-- Active Conversation State: Messages + Fixed Input --}}
                <div class="flex-1 flex flex-col min-h-0 pb-32"> {{-- Added padding-bottom for fixed input --}}

                    {{-- Messages Container --}}
                    <div
                        class="flex-1 overflow-y-auto space-y-4 px-1 scroll-smooth"
                        x-ref="messagesContainer"
                        x-init="
                            $watch('$wire.conversation.messages.length', () => $nextTick(() => scrollToBottom()));
                            $nextTick(() => scrollToBottom());
                        "
                        @refreshChat.window="$nextTick(() => scrollToBottom())"
                        x-data="{
                             scrollToBottom() {
                                 // Only scroll if already near the bottom or if it's a new message stream
                                 const el = $refs.messagesContainer;
                                 const isScrolledToBottom = el.scrollHeight - el.clientHeight <= el.scrollTop + 100; // 100px threshold
                                 if (isScrolledToBottom || $wire.isStreaming) {
                                     el.scrollTop = el.scrollHeight;
                                 }
                             }
                         }"
                    >
                        @foreach($conversation->messages as $index => $message)
                        <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] group relative {{ $message->role === 'user' ? 'bg-primary-900 dark:bg-primary-500/50 text-gray-900 dark:text-white ' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600' }} rounded-xl p-3 shadow-sm">
                                <div class="text-xs {{ $message->role === 'user' ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400' }} mb-1 flex justify-between items-center">
                                    <span>{{ $message->role === 'user' ? 'You' : $conversation->model }}</span>
                                    <span class="text-xs {{ $message->role === 'user' ? 'text-primary-200' : 'text-gray-400 dark:text-gray-500' }}">{{ $message->created_at->format('g:i A') }}</span>
                                </div>
                                <div class="prose prose-sm {{ $message->role === 'user' ? 'prose-invert' : 'dark:prose-invert' }} max-w-none message-content"
                                     x-data="{ showCopied: false }"
                                     data-original-content="{!! Str::markdown($message->content) !!}" {{-- Store original for search --}}
                                >
                                        {!! Str::markdown($message->content) !!}
                                    </div>
                                {{-- Copy Button --}}
                                <div class="absolute -top-2 {{ $message->role === 'user' ? 'left-2' : 'right-2' }} opacity-0 group-hover:opacity-100 transition-opacity" x-data="{ showCopied: false }">
                                    <button
                                        @click="navigator.clipboard.writeText($el.closest('.group').querySelector('.message-content').innerText); showCopied = true; setTimeout(() => showCopied = false, 2000);"
                                        class="p-1 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-100"
                                        :class="{ 'bg-green-100 dark:bg-green-700 text-green-600 dark:text-green-300': showCopied }"
                                        aria-label="Copy message"
                                    >
                                        <x-heroicon-o-clipboard x-show="!showCopied" class="w-4 h-4" />
                                        <x-heroicon-s-check x-show="showCopied" style="display: none;" class="w-4 h-4" />
                                    </button>
                                </div>

                                {{-- Streaming Indicator --}}
                                @if($message->is_streaming ?? false) {{-- Check if property exists --}}
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="text-xs {{ $message->role === 'user' ? 'text-primary-200' : 'text-gray-500 dark:text-gray-400' }}">Generating...</div>
                                    <div class="typing-indicator">
                                        <span></span><span></span><span></span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        {{-- Loading indicator for regeneration --}}
                        <div wire:loading wire:target="regenerateLastMessage" class="flex justify-center py-4">
                            <div class="inline-flex items-center px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-md text-sm font-medium">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Regenerating response...</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Fixed Chat Input Form (Active Conversation) - Redesigned --}}
                <div class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700">
                    <form wire:submit.prevent="sendMessage" class="mx-auto max-w-4xl w-full px-4 sm:px-6 lg:px-8">
                        <div class="relative rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
                            {{-- Message input area --}}
                            <div class="flex p-3 gap-3 items-start">
                            <textarea
                                wire:model.live="message"
                                    placeholder="Send a message..."
                                    class="flex-1 min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                                wire:keydown.enter.prevent="sendMessage"
                                aria-label="Message input"
                                x-data="{
                                    resize() {
                                            $el.style.height = 'auto'; // Reset height
                                        $el.style.height = $el.scrollHeight + 'px';
                                    }
                                }"
                                x-init="resize()"
                                @input="resize()"
                            ></textarea>
                        <button
                            type="submit"
                                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                                    :disabled="!$wire.message.trim()"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                      <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 008 16.171V11.5a1 1 0 011-1h2a1 1 0 011 1v4.671a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    </svg>
                                    <span class="sr-only">Send message</span>
                                </button>
                            </div>

                            {{-- Bottom toolbar (Re-added selectors and buttons) --}}
                            <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 p-2">
                                <div class="flex items-center gap-2">
                                    {{-- Model selector --}}
                                    <x-filament::dropdown placement="top-start">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v2.25A2.25 2.25 0 0111.25 18.5h-6.5A2.25 2.25 0 012.5 16.25V7.75A2.25 2.25 0 014.75 5.5H7V3.75A2.25 2.25 0 019.25 1.5h6.5A2.25 2.25 0 0115.988 3.012zM13.5 6.75a.75.75 0 000-1.5H9.25a.75.75 0 00-.75.75V11h3.25a.75.75 0 00.75-.75V6.75zm-3 6.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $selectedModel }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                        </x-slot>
                                        <x-filament::dropdown.list>
                                            @foreach($availableModels as $model)
                                                <x-filament::dropdown.list.item wire:click="changeModel('{{ $model }}')">
                                                    {{ $model }}
                                                </x-filament::dropdown.list.item>
                                            @endforeach
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>

                                    {{-- Style selector --}}
                                     <x-filament::dropdown placement="top-start">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path d="M11.03 2.97a.75.75 0 010 1.06l-4.72 4.72a.75.75 0 101.06 1.06l4.72-4.72a.75.75 0 011.06 0l4.72 4.72a.75.75 0 101.06-1.06l-4.72-4.72a.75.75 0 010-1.06l-4.72-4.72a.75.75 0 00-1.06 0l-4.72 4.72a.75.75 0 000 1.06l4.72 4.72a.75.75 0 101.06-1.06l-4.72-4.72a.75.75 0 010-1.06l4.72-4.72zM10 10.75a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM10 15.75a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM3 10.75a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75zM3 15.75a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" /> {{-- Using a style/palette icon --}}
                                                </svg>
                                                <span>{{ $availableStyles[$selectedStyle] ?? $selectedStyle }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                                                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                        </x-slot>
                                        <x-filament::dropdown.list>
                                        @foreach($availableStyles as $styleKey => $styleName)
                                                <x-filament::dropdown.list.item wire:click="changeStyle('{{ $styleKey }}')">
                                            {{ $styleName }}
                                                </x-filament::dropdown.list.item>
                                        @endforeach
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>
                                </div>

                                <div class="flex items-center gap-2">
                                    {{-- Character count --}}
                                    <div class="text-xs text-gray-400 dark:text-gray-500" x-data x-text="$wire.message.length + ' chars'"></div>

                                    {{-- New Chat Button --}}
                            <button
                                type="button"
                                wire:click="newConversation"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                            >
                                New chat
                            </button>
                                    {{-- Keyboard shortcut hint --}}
                            <div class="hidden sm:flex items-center text-xs text-gray-400 dark:text-gray-500">
                                        <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 mr-1">⌘/Ctrl</kbd>+<kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 mx-1">N</kbd>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Enter to send</span>
                                </div>
                            </div>
                        </div>
            </form>
        </div>
        @endif
    </main>
    </div>

    {{-- Add Typing Indicator CSS --}}
    <style>
        .typing-indicator { display: flex; align-items: center; }
        .typing-indicator span {
            height: 6px; width: 6px; margin: 0 1px; background-color: currentColor; display: block; border-radius: 50%; opacity: 0.4;
            animation: typing 1s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.1s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.2s; }
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
            40% { transform: scale(1.0); opacity: 1; }
        }
        /* Ensure prose styles don't add excessive margins */
        .prose :first-child { margin-top: 0; }
        .prose :last-child { margin-bottom: 0; }
    </style>
</div>
