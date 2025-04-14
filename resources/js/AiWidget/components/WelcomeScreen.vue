<script setup>
import { computed } from 'vue';

const props = defineProps({
    greeting: { type: String, required: true },
    recentChats: { type: Array, default: () => [] }
});

const emit = defineEmits(['load-chat', 'delete-chat', 'set-prompt']);

// Suggestions can be hardcoded here or passed as props if dynamic
const suggestions = [
    { title: 'Create a lesson plan', detail: 'For a high school English class', prompt: 'Help me create a lesson plan for a high school English class on Shakespeare.' },
    { title: 'Generate discussion questions', detail: 'About climate change for middle school', prompt: 'Generate 5 discussion questions about climate change for a middle school science class.' },
    { title: 'Draft an email template', detail: 'For parent-teacher conferences', prompt: 'Help me draft a parent-teacher conference email template.' },
    { title: 'Suggest virtual activities', detail: 'To increase student engagement', prompt: 'Suggest activities for a virtual classroom to increase student engagement.' }
];

function loadChat(id) {
    emit('load-chat', id);
}

function deleteChat(event, id) {
    // event.stopPropagation() is needed if the delete button is inside the clickable card
    event.stopPropagation();
    // Optional: Add confirmation here
    if (confirm('Are you sure you want to delete this chat?')) {
        emit('delete-chat', id);
    }
}

function setPrompt(promptText) {
    emit('set-prompt', promptText);
}

// Helper to get first letter for avatar
const getInitial = (title) => {
    return title ? title.charAt(0).toUpperCase() : '?';
};

</script>

<template>
    <div class="flex-1 flex flex-col items-center w-full px-4 sm:px-6 lg:px-8">
        <!-- Welcome Message & Suggestions -->
        <div class="w-full max-w-3xl mb-8 text-center pt-8"> <!-- Added padding-top -->
             <div class="text-primary-600 dark:text-primary-400 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 256 256" class="mx-auto">
                    <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ greeting }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">How can I help you today?</p>

            <!-- Suggestion Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left max-w-lg mx-auto mb-8">
                 <button
                    v-for="(suggestion, index) in suggestions"
                    :key="index"
                    @click="setPrompt(suggestion.prompt)"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ suggestion.title }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ suggestion.detail }}</div>
                </button>
            </div>
        </div>

        <!-- Recent Chats Grid -->
        <div class="w-full max-w-5xl mt-12">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-gray-400 dark:text-gray-500">
                  <path fill-rule="evenodd" d="M7.75 2a.75.75 0 01.75.75V7.5h4.5V2.75a.75.75 0 011.5 0V7.5h.75a3 3 0 013 3v6.75a3 3 0 01-3 3H4a3 3 0 01-3-3V10.5a3 3 0 013-3h.75V2.75A.75.75 0 017.75 2zM4.5 10.5a1.5 1.5 0 00-1.5 1.5v6.75a1.5 1.5 0 001.5 1.5h11a1.5 1.5 0 001.5-1.5V12a1.5 1.5 0 00-1.5-1.5h-11z" clip-rule="evenodd" />
                </svg>
                Recent Chats
            </h2>
            <div v-if="recentChats.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="chat in recentChats"
                    :key="chat.id"
                    @click="loadChat(chat.id)"
                    class="cursor-pointer rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all group relative"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                            {{ getInitial(chat.title) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ chat.title }}</h3>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="truncate">{{ chat.model }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ chat.last_activity }}</span>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="deleteChat($event, chat.id)"
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded"
                        aria-label="Delete chat"
                        title="Delete chat"
                    >
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.426C1.98 4.845 1 6.148 1 7.5v1.562c0 4.97 3.493 9.13 8.25 10.438a.75.75 0 00.3 0c4.757-1.308 8.25-5.468 8.25-10.438V7.5c0-1.352-.98-2.655-2.635-2.871-.785-.206-1.57-.35-2.365-.426V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 1.5a1.25 1.25 0 011.25 1.25v.463c-.37.044-.74.1-.11.157H8.86c-.37-.056-.74-.113-1.11-.157V2.75A1.25 1.25 0 0110 1.5zM2.5 7.5c0-.718.56-1.353 1.355-1.492.715-.188 1.44-.321 2.18-.402a.75.75 0 01.75.75c0 .414-.336.75-.75.75-.52 0-1.023.08-1.5.231V15c0 .828.672 1.5 1.5 1.5h7c.828 0 1.5-.672 1.5-1.5V7.11c-.477-.15-.98-.23-1.5-.23a.75.75 0 01-.75-.75.75.75 0 01.715-.75c.74.08 1.465.214 2.18.402C16.94 6.147 17.5 6.782 17.5 7.5v1.562c0 4.1-2.92 7.74-7.03 8.895a.75.75 0 01-.44 0C5.92 17.002 2.5 13.362 2.5 9.062V7.5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M10 6a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0110 6zM8.25 6.75a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5zm3.5 0a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
            <div v-else class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">No recent chats found.</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">Start a new conversation below!</p>
            </div>
        </div>
    </div>
</template>
