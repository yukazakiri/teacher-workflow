<script setup>
import { ref, defineProps, onMounted, nextTick, watch } from 'vue';
import { marked } from 'marked';

const props = defineProps({
    conversation: {
        type: Object,
        default: null
    },
    isProcessing: {
        type: Boolean,
        default: false
    },
    isStreaming: {
        type: Boolean,
        default: false
    }
});

const messagesContainer = ref(null);
const editingMessageId = ref(null);
const editedContent = ref('');

// Scroll to bottom of messages
const scrollToBottom = () => {
    if (messagesContainer.value) {
        const el = messagesContainer.value;
        // Only auto-scroll if near the bottom or streaming just started/active
        const isNearBottom = el.scrollHeight - el.clientHeight <= el.scrollTop + 150;
        if (isNearBottom || props.isStreaming) {
            el.scrollTop = el.scrollHeight;
        }
    }
};

// Watch for changes in messages to scroll to bottom
watch(() => props.conversation?.messages?.length, () => {
    nextTick(scrollToBottom);
});

// Watch for streaming state changes
watch(() => props.isStreaming, (newVal) => {
    if (newVal) {
        nextTick(scrollToBottom);
    }
});

// Start editing a message
const startEdit = (message) => {
    editingMessageId.value = message.id;
    editedContent.value = message.content;
    nextTick(() => {
        const textarea = document.getElementById(`edit-textarea-${message.id}`);
        if (textarea) {
            textarea.focus();
            resizeTextarea(textarea);
        }
    });
};

// Cancel editing
const cancelEdit = () => {
    editingMessageId.value = null;
    editedContent.value = '';
};

// Save edited message
const saveEdit = (message) => {
    if (editedContent.value.trim() && editedContent.value !== message.content) {
        // In a real implementation, you would emit an event to update the message
        // For now, we'll just update it locally
        message.content = editedContent.value;
    }
    editingMessageId.value = null;
    editedContent.value = '';
};

// Resize textarea based on content
const resizeTextarea = (el) => {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 240) + 'px'; // Max height 240px
};

// Copy message content to clipboard
const copyToClipboard = (content) => {
    navigator.clipboard.writeText(content);
    // You could add a toast notification here
};

// Render markdown content
const renderMarkdown = (content) => {
    if (!content) return '';
    return marked(content);
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto space-y-4 px-1 scroll-smooth"
    >
        <!-- Messages -->
        <template v-if="conversation && conversation.messages">
            <div
                v-for="message in conversation.messages"
                :key="`message-${message.id}`"
                class="flex group"
                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[85%] relative rounded-xl p-3 shadow-sm"
                    :class="message.role === 'user' ? 'bg-primary-600 dark:bg-primary-700 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600'"
                >
                    <div class="text-xs mb-1 flex justify-between items-center"
                         :class="message.role === 'user' ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400'">
                        <span>{{ message.role === 'user' ? 'You' : (conversation.model || 'Assistant') }}</span>
                        <span :class="message.role === 'user' ? 'text-primary-200' : 'text-gray-400 dark:text-gray-500'">
                            {{ message.created_at }}
                        </span>
                    </div>

                    <!-- Display Mode -->
                    <div v-if="editingMessageId !== message.id">
                        <div
                            class="prose prose-sm max-w-none message-content break-words"
                            :class="message.role === 'user' ? 'prose-invert' : 'dark:prose-invert'"
                            v-html="renderMarkdown(message.content || '...')"
                        ></div>
                    </div>

                    <!-- Editing Mode (Only for User Messages) -->
                    <div v-else-if="message.role === 'user'" class="space-y-2">
                        <textarea
                            :id="`edit-textarea-${message.id}`"
                            v-model="editedContent"
                            @input="resizeTextarea($event.target)"
                            @keydown.escape.prevent="cancelEdit"
                            class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary-500 focus:ring-primary-500 resize-none shadow-sm p-2"
                            rows="3"
                            :disabled="isProcessing || isStreaming"
                        ></textarea>
                        <div class="flex justify-end gap-2">
                            <button
                                @click="cancelEdit"
                                type="button"
                                class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </button>
                            <button
                                @click="saveEdit(message)"
                                type="button"
                                class="px-3 py-1 text-xs font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
                                :disabled="!editedContent.trim() || editedContent === message.content || isProcessing || isStreaming"
                            >
                                Save
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons (Copy, Edit) -->
                    <div
                        class="absolute -top-3 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1 items-center z-10"
                        :class="message.role === 'user' ? 'left-2' : 'right-2'"
                    >
                        <!-- Edit Button (Only for User Messages) -->
                        <button
                            v-if="message.role === 'user' && editingMessageId !== message.id"
                            @click="startEdit(message)"
                            class="p-1 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-100 disabled:opacity-50"
                            aria-label="Edit message"
                            title="Edit message"
                            :disabled="isProcessing || isStreaming"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                        </button>

                        <!-- Copy Button -->
                        <button
                            v-if="editingMessageId !== message.id"
                            @click="copyToClipboard(message.content)"
                            class="p-1 rounded bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-100"
                            aria-label="Copy message"
                            title="Copy message"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                        </button>
                    </div>

                    <!-- Streaming Indicator -->
                    <div
                        v-if="message.role === 'assistant' && isStreaming && message.id === conversation.messages[conversation.messages.length - 1].id"
                        class="mt-2 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div class="typing-indicator"><span></span><span></span><span></span></div>
                        <span>Generating...</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Loading indicator for AI response -->
        <div v-if="isProcessing" class="flex justify-start group">
            <div class="max-w-[85%] relative bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl p-3 shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between items-center">
                    <span>{{ conversation?.model || 'Assistant' }}</span>
                </div>
                <div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <div class="typing-indicator"><span></span><span></span><span></span></div>
                    <span>{{ isStreaming ? 'Generating...' : 'Processing...' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
