<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    chats: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'load-conversation', 'delete-conversation', 'new-conversation']);

const loadConversation = (id) => {
    emit('load-conversation', id);
};

const deleteConversation = (id, event) => {
    event.stopPropagation();
    if (confirm('Are you sure you want to delete this chat?')) {
        emit('delete-conversation', id);
    }
};

const newConversation = () => {
    emit('new-conversation');
    emit('close');
};
</script>

<template>
    <div
        v-show="open"
        class="fixed inset-y-0 right-0 z-40 w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 shadow-xl flex flex-col"
        @click.away="emit('close')"
    >
        <!-- Sidebar Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Chat History</h2>
            <button
                @click="emit('close')"
                class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
            >
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Sidebar Content (Chat List) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <template v-if="chats.length > 0">
                <div
                    v-for="chat in chats"
                    :key="`chat-item-${chat.id}`"
                    @click="loadConversation(chat.id)"
                    class="cursor-pointer rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all group relative"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                            {{ chat.title.charAt(0).toUpperCase() }}
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
                        @click="deleteConversation(chat.id, $event)"
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded"
                        aria-label="Delete chat"
                        title="Delete chat"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.426C1.98 4.845 1 6.148 1 7.5v1.562c0 4.97 3.493 9.13 8.25 10.438a.75.75 0 00.3 0c4.757-1.308 8.25-5.468 8.25-10.438V7.5c0-1.352-.98-2.655-2.635-2.871-.785-.206-1.57-.35-2.365-.426V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 1.5a1.25 1.25 0 011.25 1.25v.463c-.37.044-.74.1-.11.157H8.86c-.37-.056-.74-.113-1.11-.157V2.75A1.25 1.25 0 0110 1.5zM2.5 7.5c0-.718.56-1.353 1.355-1.492.715-.188 1.44-.321 2.18-.402a.75.75 0 01.75.75c0 .414-.336.75-.75.75-.52 0-1.023.08-1.5.231V15c0 .828.672 1.5 1.5 1.5h7c.828 0 1.5-.672 1.5-1.5V7.11c-.477-.15-.98-.23-1.5-.23a.75.75 0 01-.75-.75.75.75 0 01.715-.75c.74.08 1.465.214 2.18.402C16.94 6.147 17.5 6.782 17.5 7.5v1.562c0 4.1-2.92 7.74-7.03 8.895a.75.75 0 01-.44 0C5.92 17.002 2.5 13.362 2.5 9.062V7.5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M10 6a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0110 6zM8.25 6.75a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5zm3.5 0a.75.75 0 00-1.5 0v5.5a.75.75 0 001.5 0v-5.5z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </template>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No chats yet.</p>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <button
                @click="newConversation"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>
                New Chat
            </button>
        </div>
    </div>
</template>
