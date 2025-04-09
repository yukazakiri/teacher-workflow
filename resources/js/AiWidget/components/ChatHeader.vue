<script setup>
import { ref, defineProps, defineEmits } from 'vue';

const props = defineProps({
    conversation: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['toggle-sidebar', 'new-conversation', 'regenerate-last-message']);

const isRenaming = ref(false);
const newTitle = ref('');

const startRenaming = () => {
    newTitle.value = props.conversation.title;
    isRenaming.value = true;
    // Focus the input on next tick
    setTimeout(() => {
        document.getElementById('title-input')?.focus();
    }, 0);
};

const saveRename = () => {
    if (newTitle.value.trim()) {
        // In a real implementation, you would emit an event to update the title
        // For now, we'll just update it locally
        props.conversation.title = newTitle.value;
    }
    isRenaming.value = false;
};

const cancelRename = () => {
    isRenaming.value = false;
};
</script>

<template>
    <div class="flex items-center justify-between mb-4 px-1">
        <template v-if="conversation">
            <!-- Conversation Header -->
            <div class="flex items-center gap-2 min-w-0">
                <!-- Back Button -->
                <button
                    @click="emit('new-conversation')"
                    class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mr-1"
                    title="Back to home"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                </button>

                <!-- Rename Functionality -->
                <div class="min-w-0">
                    <div v-if="!isRenaming" class="flex items-center gap-1 min-w-0">
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white truncate cursor-pointer hover:text-primary-600 dark:hover:text-primary-400"
                            @click="startRenaming"
                            :title="`Rename conversation: ${conversation.title}`"
                        >
                            {{ conversation.title }}
                        </h2>
                        <button
                            @click="startRenaming"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 p-1"
                            aria-label="Rename conversation"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"> <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                        </button>
                    </div>
                    <form 
                        v-else 
                        @submit.prevent="saveRename"
                        class="flex items-center gap-1 min-w-0"
                    >
                        <input
                            id="title-input"
                            v-model="newTitle"
                            class="text-lg font-semibold text-gray-900 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-primary-500 dark:focus:border-primary-500 focus:ring-0 px-1 py-0 leading-tight w-full min-w-0"
                            @blur="saveRename"
                        />
                        <button type="submit" class="text-green-600 dark:text-green-400 flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Save">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        </button>
                        <button type="button" @click="cancelRename" class="text-red-600 dark:text-red-400 flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Cancel">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <button
                    @click="emit('regenerate-last-message')"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-1 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Regenerate last response"
                    aria-label="Regenerate last response"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"> <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.984a.75.75 0 00-.75.75v4.566a.75.75 0 001.5 0v-2.433l.313.313a7 7 0 0011.812-3.078 1.5 1.5 0 00-2.454-1.134zM9.056 4.134a1.5 1.5 0 00-2.454 1.134 5.5 5.5 0 019.201-2.466l.312.311H13.43a.75.75 0 000 1.5h4.086a.75.75 0 00.75-.75V.066a.75.75 0 00-1.5 0v2.433l-.313-.313a7 7 0 00-11.812 3.078z" clip-rule="evenodd" /></svg>
                    <span class="hidden sm:inline">Regenerate</span>
                </button>
                <button
                    @click="emit('new-conversation')"
                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                    title="Start a new chat"
                >
                    <span class="hidden sm:inline">New chat</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 sm:hidden"> <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>
                </button>
            </div>
        </template>
        <template v-else>
            <!-- Home State Header -->
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Teacher Assistant</h1>
                <span class="text-sm px-2 py-0.5 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-full">Home</span>
            </div>

            <!-- Optionally add other controls like settings, help, etc. here -->
            <div>
                <button
                    @click="emit('toggle-sidebar')"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 rounded-full"
                    title="View chat history"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
</template>
