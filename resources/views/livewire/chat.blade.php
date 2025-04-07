@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Js; // Import Js facade
@endphp

<div class="min-h-screen bg-background" x-data="{ sidebarOpen: false }" {{ isset($pollingInterval) ? "wire:poll.{$pollingInterval}='pollStreamingState'" : '' }}>
    {{-- Right Sidebar (Hidden by default) --}}
    <div
            x-show="sidebarOpen"
            x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
            x-transition:enter-start="tran`slate-x-full"
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
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Sidebar Content (Chat List) --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                {{-- Use getAllChats() directly --}}
                @forelse($this->getAllChats() as $chatItem)
                <div
                    wire:key="chat-item-{{ $chatItem['id'] }}"
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
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded"
                        aria-label="Delete chat"
                        title="Delete chat"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.426C1.98 4.845 1 6.148 1 7.5v1.562c0 4.97 3.493 9.13 8.25 10.438a.75.75 0 00.3 0c4.757-1.308 8.25-5.468 8.25-10.438V7.5c0-1.352-.98-2.655-2.635-2.871-.785-.206-1.57-.35-2.365-.426V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 1.5a1.25 1.25 0 011.25 1.25v.463c-.37.044-.74.1-.11.157H8.86c-.37-.056-.74-.113-1.11-.157V2.75A1.25 1.25 0 0110 1.5zM2.5 7.5c0-.718.56-1.353 1.355-1.492.715-.188 1.44-.321 2.18-.402a.75.75 0 01.75.75c0 .414-.336.75-.75.75-.52 0-1.023.08-1.5.231V15c0 .828.672 1.5 1.5 1.5h7c.828 0 1.5-.672 1.5-1.5V7.11c-.477-.15-.98-.23-1.5-.23a.75.75 0 01-.75-.75.75.75 0 01.715-.75c.74.08 1.465.214 2.18.402C16.94 6.147 17.5 6.782 17.5 7.5v1.562c0 4.1-2.92 7.74-7.03 8.895a.75.75 0 01-.44 0C5.92 17.002 2.5 13.362 2.5 9.062V7.5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M10 6a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0110 6zM8.25 6.75a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5zm3.5 0a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5z" clip-rule="evenodd" /></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>
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
                    {{-- Back Button --}}
                    <button
                        wire:click="newConversation"
                        class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mr-1"
                        title="Back to home"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                    </button>

                    {{-- Rename Functionality --}}
                    <div x-data="{ isRenaming: false, newTitle: @js($conversation->title ?? '') }" class="min-w-0">
                        <template x-if="!isRenaming">
                            <div class="flex items-center gap-1 min-w-0">
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white truncate cursor-pointer hover:text-primary-600 dark:hover:text-primary-400"
                                    @click="isRenaming = true; $nextTick(() => $refs.titleInput.focus())"
                                    title="Rename conversation: {{ $conversation->title }}"
                                >
                                    {{ $conversation->title }}
                                </h2>
                                <button
                                    @click="isRenaming = true; $nextTick(() => $refs.titleInput.focus())"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 p-1"
                                    aria-label="Rename conversation"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"> <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="isRenaming">
                            <form @submit.prevent="if(newTitle.trim()) { $wire.renameConversation(newTitle); isRenaming = false; } else { isRenaming = false; newTitle = $wire.conversation.title; }" @keydown.escape.prevent="isRenaming = false; newTitle = $wire.conversation.title;" class="flex items-center gap-1 min-w-0">
                                <input
                                    x-ref="titleInput"
                                    x-model="newTitle"
                                    class="text-lg font-semibold text-gray-900 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-primary-500 dark:focus:border-primary-500 focus:ring-0 px-1 py-0 leading-tight w-full min-w-0"
                                    @blur="if(newTitle.trim()) { $wire.renameConversation(newTitle); isRenaming = false; } else { isRenaming = false; newTitle = $wire.conversation.title; }"
                                />
                                <button type="submit" class="text-green-600 dark:text-green-400 flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Save">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                </button>
                                <button type="button" @click="isRenaming = false; newTitle = $wire.conversation.title;" class="text-red-600 dark:text-red-400 flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Cancel">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                </button>
                            </form>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button
                        wire:click="regenerateLastMessage"
                        wire:loading.attr="disabled"
                        wire:target="regenerateLastMessage, saveEditedMessage" {{-- Disable on both --}}
                        :disabled="$wire.isProcessing || $wire.isStreaming" {{-- Alpine disable too --}}
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Regenerate last response"
                        aria-label="Regenerate last response"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"> <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.984a.75.75 0 00-.75.75v4.566a.75.75 0 001.5 0v-2.433l.313.313a7 7 0 0011.812-3.078 1.5 1.5 0 00-2.454-1.134zM9.056 4.134a1.5 1.5 0 00-2.454 1.134 5.5 5.5 0 019.201-2.466l.312.311H13.43a.75.75 0 000 1.5h4.086a.75.75 0 00.75-.75V.066a.75.75 0 00-1.5 0v2.433l-.313-.313a7 7 0 00-11.812 3.078z" clip-rule="evenodd" /></svg>
                        <span class="hidden sm:inline">Regenerate</span>
                    </button>
                    <button
                        wire:click="newConversation"
                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                        title="Start a new chat"
                    >
                        <span class="hidden sm:inline">New chat</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 sm:hidden"> <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
        @else
            {{-- Home State Header --}}
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
        <main class="flex-1 flex flex-col mx-auto max-w-5xl w-full">

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
                    <form wire:submit.prevent="sendMessage" class="w-full max-w-5xl sticky bottom-0 z-10" wire:key="initial-input-form"
                          x-data="mentionHandler($wire)"> {{-- Add Alpine component --}}
                        <div class="relative rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
                            {{-- Mention Dropdown --}}
                            <div x-show="showSuggestions"
                                 @click.away="resetMentions()"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute bottom-full mb-2 w-full max-w-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden z-20 max-h-60 overflow-y-auto"
                                 style="display: none;">
                                <template x-if="mentionResults.length > 0">
                                    <ul>
                                        <template x-for="(result, index) in mentionResults" :key="index">
                                            <li @click="selectMention(index)"
                                                @mouseenter="highlightedIndex = index"
                                                class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                                :class="{ 'bg-gray-100 dark:bg-gray-700': highlightedIndex === index }">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="result.title"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="result.category"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <template x-if="mentionResults.length === 0 && mentionQuery.length > 0">
                                    <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No resources found matching "@<span x-text="mentionQuery"></span>"</p>
                                </template>
                            </div>

                            {{-- Message input area --}}
                            <div class="flex p-2 gap-2 items-start">
                                <textarea
                                    wire:model.live="message"
                                    x-ref="textarea" {{-- Add ref --}}
                                    @input.debounce.300ms="handleInput($event)" {{-- Debounced input --}}
                                    @keydown="handleKeyDown($event)" {{-- Keydown for navigation/selection --}}
                                    placeholder="Start typing your message here... Use @ to mention resources."
                                    class="flex-1 min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                                    {{-- x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); if (!$wire.isProcessing && !$wire.isStreaming && $wire.message.trim()) { $wire.sendMessage() } }" --}} {{-- Handled in handleKeyDown --}}
                                    aria-label="Message input"
                                    x-init="resize()"
                                    @input="resize()"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage, regenerateLastMessage, saveEditedMessage"
                                    :disabled="$wire.isProcessing || $wire.isStreaming"
                                ></textarea>
                                <button
                                    type="submit"
                                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage, regenerateLastMessage, saveEditedMessage"
                                    :disabled="!$wire.message.trim() || $wire.isProcessing || $wire.isStreaming" {{-- Alpine disable --}}
                                >
                                    {{-- Send Icon (Paper Airplane) --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 8.184l-5.357.882a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 15.016l6.895 1.724a.75.75 0 00.95-.826l1.414-4.949a.75.75 0 00-.826-.95L10 11.016l6.895-1.149a.75.75 0 00.826-.95l-1.414-4.949a.75.75 0 00-.95-.826L10 4.016 3.105 2.29z" /></svg>
                                    <span class="sr-only">Send message</span>
                                </button>
                            </div>

                            {{-- Bottom toolbar - Mobile adjustments --}}
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-t border-gray-200 dark:border-gray-700 p-2 gap-2 sm:gap-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Model selector --}}
                                    <x-filament::dropdown placement="top-start">
                                        {{-- ... trigger ... --}}
                                         <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v2.25A2.25 2.25 0 0111.25 18.5h-6.5A2.25 2.25 0 012.5 16.25V7.75A2.25 2.25 0 014.75 5.5H7V3.75A2.25 2.25 0 019.25 1.5h6.5A2.25 2.25 0 0115.988 3.012zM13.5 6.75a.75.75 0 000-1.5H9.25a.75.75 0 00-.75.75V11h3.25a.75.75 0 00.75-.75V6.75zm-3 6.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                                                <span>{{ $selectedModel }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
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
                                        {{-- ... trigger ... --}}
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"> <path d="M10 3.75a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm0 3.5a.75.75 0 01.75.75v4.01a.75.75 0 01-1.5 0V8.01a.75.75 0 01.75-.75zm0 7a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm-3.25-8.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zM13.25 5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75z" /></svg> {{-- Palette/Sliders icon --}}
                                                <span>{{ $availableStyles[$selectedStyle] ?? Str::title($selectedStyle) }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
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

                                <div class="flex items-center gap-2 justify-end sm:justify-start w-full sm:w-auto">
                                    {{-- Character count (Hidden on small screens) --}}
                                    <div class="hidden sm:block text-xs text-gray-400 dark:text-gray-500" x-data x-text="$wire.message.length + ' chars'"></div>

                                    {{-- New Chat Button --}}
                                    <button
                                        type="button"
                                        wire:click="newConversation"
                                        class="hidden sm:inline-block text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                                        title="Start a new chat"
                                    >
                                        New chat
                                    </button>
                                    {{-- Keyboard shortcut hint (Hidden on small screens) --}}
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
                            const observer = new MutationObserver(() => scrollToBottom());
                            observer.observe($el, { childList: true, subtree: true });
                            $watch('$wire.conversation.messages.length', () => $nextTick(() => scrollToBottom()));
                            $nextTick(() => scrollToBottom()); // Initial scroll
                        "
                        @refreshChat.window="$nextTick(() => scrollToBottom())"
                        x-data="{
                             scrollToBottom() {
                                 const el = $refs.messagesContainer;
                                 // Scroll more reliably, especially during streaming
                                 // Only auto-scroll if near the bottom or streaming just started/active
                                 const isNearBottom = el.scrollHeight - el.clientHeight <= el.scrollTop + 150; // Increased threshold
                                 if (isNearBottom || $wire.isStreaming) {
                                     el.scrollTop = el.scrollHeight;
                                     console.log('scrolling to bottom');
                                 }
                             }
                         }"
                    >
                        @if($conversation && $conversation->messages)
                            @foreach($conversation->messages as $index => $message)
                                {{-- Unique key for Livewire DOM diffing --}}
                                <div wire:key="message-{{ $message->id ?? 'new-' . $index }}" class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }} group" >
                                    <div
                                        class="max-w-[85%] relative {{ $message->role === 'user' ? 'bg-primary-600 dark:bg-primary-700 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600' }} rounded-xl p-3 shadow-sm"
                                        x-data="{
                                            isEditing: false,
                                            editedContent: @js($message->content ?? ''),
                                            initialContent: @js($message->content ?? ''),
                                            startEdit() {
                                                this.isEditing = true;
                                                this.editedContent = this.initialContent; // Reset to original on edit start
                                                this.$nextTick(() => {
                                                    const textarea = $el.querySelector('textarea');
                                                    textarea.focus();
                                                    textarea.style.height = 'auto';
                                                    textarea.style.height = textarea.scrollHeight + 'px';
                                                });
                                            },
                                            cancelEdit() {
                                                this.isEditing = false;
                                                this.editedContent = this.initialContent; // Revert changes
                                            },
                                            saveEdit() {
                                                if (this.editedContent.trim() && this.editedContent !== this.initialContent) {
                                                    $wire.saveEditedMessage({{ $message->id }}, this.editedContent);
                                                }
                                                this.isEditing = false; // Close editor immediately
                                            },
                                            resizeTextarea(el) {
                                                el.style.height = 'auto';
                                                el.style.height = Math.min(el.scrollHeight, 240) + 'px'; // Max height 240px
                                            }
                                        }"
                                    >
                                        <div class="text-xs {{ $message->role === 'user' ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400' }} mb-1 flex justify-between items-center">
                                            <span>{{ $message->role === 'user' ? 'You' : ($conversation->model ?? 'Assistant') }}</span>
                                            <span class="text-xs {{ $message->role === 'user' ? 'text-primary-200' : 'text-gray-400 dark:text-gray-500' }}">{{ $message->created_at ? $message->created_at->format('g:i A') : 'Sending...' }}</span>
                                        </div>

                                        {{-- Display Mode --}}
                                        <template x-if="!isEditing">
                                            <div class="prose prose-sm {{ $message->role === 'user' ? 'prose-invert' : 'dark:prose-invert' }} max-w-none message-content break-words">
                                                {!! Str::markdown($message->content ?: '...') !!} {{-- Handle potentially empty content during streaming --}}
                                            </div>
                                        </template>

                                        {{-- Editing Mode (Only for User Messages) --}}
                                        @if($message->role === 'user')
                                            <template x-if="isEditing">
                                                <div class="space-y-2">
                                                    <textarea
                                                        x-model="editedContent"
                                                        @input="resizeTextarea($el)"
                                                        @keydown.escape.prevent="cancelEdit()"
                                                        @keydown.enter.prevent="saveEdit()"
                                                        class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary-500 focus:ring-primary-500 resize-none shadow-sm p-2"
                                                        rows="3" {{-- Initial rows --}}
                                                        :disabled="$wire.isProcessing || $wire.isStreaming"
                                                    ></textarea>
                                                    <div class="flex justify-end gap-2">
                                                        <button @click="cancelEdit()" type="button" class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                                                        <button @click="saveEdit()" type="button" class="px-3 py-1 text-xs font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50" :disabled="!editedContent.trim() || editedContent === initialContent || $wire.isProcessing || $wire.isStreaming">Save</button>
                                                    </div>
                                                </div>
                                            </template>
                                        @endif

                                        {{-- Action Buttons (Copy, Edit) --}}
                                        <div class="absolute -top-3 {{ $message->role === 'user' ? 'left-2' : 'right-2' }} opacity-0 group-hover:opacity-100 transition-opacity flex gap-1 items-center z-10" x-data="{ showCopied: false }">
                                            {{-- Edit Button (Only for User Messages) --}}
                                             @if($message->role === 'user')
                                                <button
                                                    x-show="!isEditing"
                                                    @click="startEdit()"
                                                    class="p-1 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-100 disabled:opacity-50"
                                                    aria-label="Edit message"
                                                    title="Edit message"
                                                    :disabled="$wire.isProcessing || $wire.isStreaming"
                                                >
                                                   <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                                </button>
                                            @endif

                                            {{-- Copy Button --}}
                                            <button
                                                x-show="!isEditing" {{-- Hide copy when editing --}}
                                                @click="navigator.clipboard.writeText($el.closest('.group').querySelector('.message-content').innerText); showCopied = true; setTimeout(() => showCopied = false, 2000);"
                                                class="p-1 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-100"
                                                :class="{ 'bg-green-100 dark:bg-green-800/50 text-green-600 dark:text-green-400': showCopied }"
                                                aria-label="Copy message"
                                                title="Copy message"
                                            >
                                                <svg x-show="!showCopied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                                                <svg x-show="showCopied" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </div>

                                        {{-- Streaming Indicator (Check property from component/model) --}}
                                        @if($message->role === 'assistant' && ($isStreaming && $loop->last)) {{-- Show only on last assistant message if streaming --}}
                                            <div class="mt-2 flex items-center gap-1 text-xs {{ $message->role === 'user' ? 'text-primary-200' : 'text-gray-500 dark:text-gray-400' }}">
                                                <div class="typing-indicator"><span></span><span></span><span></span></div>
                                                <span>Generating...</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif

                         {{-- Loading indicator for AI response --}}
                        <div x-show="$wire.isProcessing" wire:key="ai-loading-indicator" class="flex justify-start group">
                            <div class="max-w-[85%] relative bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl p-3 shadow-sm">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between items-center">
                                    <span>{{ $conversation->model ?? 'Assistant' }}</span>
                                    {{-- <span class="text-xs text-gray-400 dark:text-gray-500">Now</span> --}}
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="typing-indicator"><span></span><span></span><span></span></div>
                                    <span x-text="$wire.isStreaming ? 'Generating...' : 'Processing...'">Generating...</span>
                                </div>
                            </div>
                        </div>

                         {{-- Loading indicator specifically for regeneration/saving edit --}}
                        <div wire:loading wire:target="regenerateLastMessage, saveEditedMessage" class="flex justify-center py-4">
                            <div class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md text-sm font-medium shadow">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Processing...</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Fixed Chat Input Form (Active Conversation) - Redesigned for Mobile --}}
                <div class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700">
                    <form wire:submit.prevent="sendMessage" class="mx-auto max-w-4xl w-full px-4 sm:px-6 lg:px-8" wire:key="active-input-form"
                          x-data="mentionHandler($wire)"> {{-- Add Alpine component --}}
                        <div class="relative rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
                             {{-- Mention Dropdown --}}
                            <div x-show="showSuggestions"
                                 @click.away="resetMentions()"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute bottom-full mb-2 w-full max-w-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden z-20 max-h-60 overflow-y-auto"
                                 style="display: none;">
                                <template x-if="mentionResults.length > 0">
                                    <ul>
                                        <template x-for="(result, index) in mentionResults" :key="index">
                                            <li @click="selectMention(index)"
                                                @mouseenter="highlightedIndex = index"
                                                class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                                :class="{ 'bg-gray-100 dark:bg-gray-700': highlightedIndex === index }">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="result.title"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="result.category"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <template x-if="mentionResults.length === 0 && mentionQuery.length > 0">
                                    <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">No resources found matching "@<span x-text="mentionQuery"></span>"</p>
                                </template>
                            </div>

                            {{-- Message input area --}}
                            <div class="flex p-3 gap-3 items-start">
                                <textarea
                                    wire:model.live="message"
                                    x-ref="textarea" {{-- Add ref --}}
                                    @input.debounce.300ms="handleInput($event)" {{-- Debounced input --}}
                                    @keydown="handleKeyDown($event)" {{-- Keydown for navigation/selection --}}
                                    placeholder="Send a message... Use @ to mention resources."
                                    class="flex-1 min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                                     {{-- x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); if (!$wire.isProcessing && !$wire.isStreaming && $wire.message.trim()) { $wire.sendMessage() } }" --}} {{-- Handled in handleKeyDown --}}
                                    aria-label="Message input"
                                    x-init="resize()"
                                    @input="resize()"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage, regenerateLastMessage, saveEditedMessage"
                                    :disabled="$wire.isProcessing || $wire.isStreaming"
                                ></textarea>
                                <button
                                    type="submit"
                                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage, regenerateLastMessage, saveEditedMessage"
                                    :disabled="!$wire.message.trim() || $wire.isProcessing || $wire.isStreaming" {{-- Alpine disable --}}
                                >
                                    {{-- Send Icon (Paper Airplane) --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 8.184l-5.357.882a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 15.016l6.895 1.724a.75.75 0 00.95-.826l1.414-4.949a.75.75 0 00-.826-.95L10 11.016l6.895-1.149a.75.75 0 00.826-.95l-1.414-4.949a.75.75 0 00-.95-.826L10 4.016 3.105 2.29z" /></svg>
                                    <span class="sr-only">Send message</span>
                                </button>
                            </div>

                            {{-- Bottom toolbar - Mobile adjustments --}}
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-t border-gray-200 dark:border-gray-700 p-2 gap-2 sm:gap-0">
                                <div class="flex flex-wrap items-center gap-2"> {{-- flex-wrap for smaller screens --}}
                                    {{-- Model selector --}}
                                    <x-filament::dropdown placement="top-start">
                                         {{-- ... trigger ... --}}
                                         <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v2.25A2.25 2.25 0 0111.25 18.5h-6.5A2.25 2.25 0 012.5 16.25V7.75A2.25 2.25 0 014.75 5.5H7V3.75A2.25 2.25 0 019.25 1.5h6.5A2.25 2.25 0 0115.988 3.012zM13.5 6.75a.75.75 0 000-1.5H9.25a.75.75 0 00-.75.75V11h3.25a.75.75 0 00.75-.75V6.75zm-3 6.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                                                <span>{{ $selectedModel }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
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
                                         {{-- ... trigger ... --}}
                                         <x-slot name="trigger">
                                            <button type="button" class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"> <path d="M10 3.75a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm0 3.5a.75.75 0 01.75.75v4.01a.75.75 0 01-1.5 0V8.01a.75.75 0 01.75-.75zm0 7a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm-3.25-8.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zM13.25 5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75z" /></svg> {{-- Palette/Sliders icon --}}
                                                <span>{{ $availableStyles[$selectedStyle] ?? Str::title($selectedStyle) }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
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

                                <div class="flex items-center gap-2 justify-end sm:justify-start w-full sm:w-auto">
                                    {{-- Character count (Hidden on small screens) --}}
                                    <div class="hidden sm:block text-xs text-gray-400 dark:text-gray-500" x-data x-text="$wire.message.length + ' chars'"></div>

                                    {{-- Stop Generation Button (Show only when streaming) --}}
                                    {{-- Implement stop functionality in component if desired --}}
                                    {{-- <button x-show="$wire.isStreaming" wire:click="stopGeneration" type="button" class="...">Stop</button> --}}

                                    <span class="text-xs text-gray-400 dark:text-gray-500">Enter to send</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </main>
    </div>

    {{-- Add Typing Indicator CSS & Alert Styling --}}
    <style>
        .typing-indicator { display: inline-flex; align-items: center; }
        .typing-indicator span {
            height: 5px; width: 5px; margin: 0 1px; background-color: currentColor; display: block; border-radius: 50%; opacity: 0.4;
            animation: typing 1s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.1s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.2s; }
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0.5); opacity: 0.4; }
            40% { transform: scale(1.0); opacity: 1; }
        }
        /* Ensure prose styles don't add excessive margins */
        .prose :first-child { margin-top: 0; }
        .prose :last-child { margin-bottom: 0; }

        /* Simple Alert Styling (optional, if you use the dispatch('show-alert', ...)) */
        /* Add a container somewhere accessible, e.g., top-right */
        /*
        .alert-container { position: fixed; top: 1rem; right: 1rem; z-index: 50; }
        .alert { padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-radius: 0.375rem; font-size: 0.875rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); }
        .alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; }
        .alert-success { background-color: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-warning { background-color: #fef3c7; border: 1px solid #fcd34d; color: #92400e; }
        */
    </style>

    {{-- Alpine listener for alerts (optional) --}}
    {{--
    <div x-data="{ alerts: [] }" @show-alert.window="alerts.push($event.detail); setTimeout(() => alerts.shift(), 3000)" class="alert-container">
        <template x-for="(alert, index) in alerts" :key="index">
            <div :class="'alert alert-' + alert.type" x-text="alert.message"></div>
        </template>
    </div>
    --}}

    <script>
        function mentionHandler($wire) {
            return {
                mentionQuery: '',
                mentionResults: [],
                showSuggestions: false,
                highlightedIndex: -1,
                mentionStartIndex: -1,
                init() {
                    console.log('Mention handler initialized.');
                    // Watch the Livewire property for results
                    this.$watch('$wire.mentionResults', (newResults) => {
                        console.log('Livewire mentionResults updated:', newResults);
                        this.mentionResults = newResults;
                        this.showSuggestions = this.$wire.showMentionResults && this.mentionQuery.length > 0;
                        this.highlightedIndex = -1; // Reset highlight when results change
                    });
                    this.$watch('$wire.showMentionResults', (newValue) => {
                         console.log('Livewire showMentionResults updated:', newValue);
                         this.showSuggestions = newValue && this.mentionQuery.length > 0;
                         if (!newValue) {
                             this.highlightedIndex = -1;
                         }
                    });
                },
                handleInput(event) {
                    console.log('handleInput triggered');
                    const textarea = this.$refs.textarea;
                    const value = textarea.value;
                    const cursorPosition = textarea.selectionStart;

                    // Regex to find "@" followed by non-space characters right before the cursor
                    const textBeforeCursor = value.substring(0, cursorPosition);
                    const match = textBeforeCursor.match(/@([\w\s.-]*)$/); // Allow spaces, dots, hyphens
                    console.log('Regex match:', match);

                    if (match) {
                        this.mentionStartIndex = match.index;
                        this.mentionQuery = match[1];
                        console.log(`Mention pattern found. Query: '${this.mentionQuery}'. Calling Livewire...`);
                        // Trigger Livewire search (debounced by @input.debounce)
                        $wire.searchResourceMentions(this.mentionQuery);
                    } else {
                        console.log('Mention pattern NOT found or cleared.');
                        this.resetMentions();
                    }
                    this.resize(); // Call existing resize function
                },
                handleKeyDown(event) {
                    // console.log('handleKeyDown:', event.key, 'Show suggestions:', this.showSuggestions);
                    if (this.showSuggestions) {
                        switch (event.key) {
                            case 'ArrowDown':
                                event.preventDefault();
                                this.highlightedIndex = (this.highlightedIndex + 1) % this.mentionResults.length;
                                this.scrollToHighlighted();
                                break;
                            case 'ArrowUp':
                                event.preventDefault();
                                this.highlightedIndex = (this.highlightedIndex - 1 + this.mentionResults.length) % this.mentionResults.length;
                                this.scrollToHighlighted();
                                break;
                            case 'Enter':
                            case 'Tab': // Allow Tab for selection too
                                if (this.highlightedIndex !== -1) {
                                    event.preventDefault();
                                    this.selectMention(this.highlightedIndex);
                                } else {
                                    // If dropdown is visible but nothing selected, maybe just close it? Or allow default Enter.
                                     this.resetMentions();
                                     // Allow default Enter to send message if Shift isn't pressed (original logic)
                                    if (event.key === 'Enter' && !event.shiftKey && !$wire.isProcessing && !$wire.isStreaming && $wire.message.trim()) {
                                         $wire.sendMessage();
                                    }
                                }
                                break;
                            case 'Escape':
                                event.preventDefault();
                                console.log('Escape pressed, resetting mentions.');
                                this.resetMentions();
                                break;
                        }
                    } else if (event.key === 'Enter' && !event.shiftKey) {
                         // Default send message logic when mention is not active
                        event.preventDefault();
                        if (!$wire.isProcessing && !$wire.isStreaming && $wire.message.trim()) {
                            $wire.sendMessage();
                        }
                    }
                },
                selectMention(index) {
                    console.log('selectMention called with index:', index);
                    if (this.mentionResults[index] && this.mentionStartIndex !== -1) {
                        const result = this.mentionResults[index];
                        // Call Livewire to store the selection details
                        $wire.addSelectedResource(result.id, result.title);

                        // const selectedText = `[Resource: ${result.title} ID: ${result.id}] `; // Old format
                        const selectedText = `@${result.title} `; // New, cleaner format for display
                        const textarea = this.$refs.textarea;
                        const value = textarea.value;
                        const before = value.substring(0, this.mentionStartIndex);
                        // Ensure we correctly calculate the end position based on the @ and the query length
                        const after = value.substring(this.mentionStartIndex + 1 + this.mentionQuery.length); // +1 for '@'

                        // Update the Livewire message property directly
                        $wire.message = before + selectedText + after;

                        // Manually set textarea value and cursor position after Livewire update
                        this.$nextTick(() => {
                            textarea.value = $wire.message;
                            const cursorPos = before.length + selectedText.length;
                             textarea.focus();
                             textarea.setSelectionRange(cursorPos, cursorPos);
                             this.resize(); // Resize after changing content
                        });

                        this.resetMentions();
                    }
                },
                resetMentions() {
                    console.log('resetMentions called.');
                    this.showSuggestions = false;
                    this.mentionQuery = '';
                    this.highlightedIndex = -1;
                    this.mentionStartIndex = -1;
                    // Don't necessarily clear wire results here, let Livewire manage its state
                     $wire.clearMentionResults(); // Tell Livewire to clear its state too
                },
                // Helper to scroll the dropdown if needed
                scrollToHighlighted() {
                     this.$nextTick(() => {
                        const listElement = this.$el.querySelector('ul');
                        if (listElement && this.highlightedIndex !== -1) {
                            const highlightedItem = listElement.children[this.highlightedIndex];
                            if (highlightedItem) {
                                highlightedItem.scrollIntoView({ block: 'nearest' });
                            }
                        }
                    });
                },
                 // Reuse existing resize logic if available, or define it
                 resize() {
                    const el = this.$refs.textarea;
                    el.style.height = 'auto';
                    // Use the same max height as before (240px based on previous code)
                    el.style.height = Math.min(el.scrollHeight, 240) + 'px';
                }
            }
        }
    </script>
</div>
