<script setup>
import { ref, defineProps, defineEmits, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    isProcessing: {
        type: Boolean,
        default: false
    },
    isStreaming: {
        type: Boolean,
        default: false
    },
    selectedModel: {
        type: String,
        default: 'gpt-4'
    },
    selectedStyle: {
        type: String,
        default: 'balanced'
    },
    availableModels: {
        type: Array,
        default: () => []
    },
    availableStyles: {
        type: Object,
        default: () => ({})
    },
    conversation: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'send-message', 'change-model', 'change-style', 'new-conversation']);

const textarea = ref(null);
const modelDropdownOpen = ref(false);
const styleDropdownOpen = ref(false);

// Resize textarea based on content
const resize = () => {
    if (!textarea.value) return;

    const el = textarea.value;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 240) + 'px'; // Max height 240px
};

// Handle input changes
const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
    resize();
};

// Handle key presses
const handleKeyDown = (event) => {
    // Send message on Enter (without Shift)
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        if (!props.isProcessing && !props.isStreaming && props.modelValue.trim()) {
            emit('send-message');
        }
    }
};

// Toggle model dropdown
const toggleModelDropdown = (event) => {
    // Stop event propagation
    if (event) event.stopPropagation();
    
    // Toggle dropdown
    modelDropdownOpen.value = !modelDropdownOpen.value;
    
    // Close other dropdown if this one is opening
    if (modelDropdownOpen.value) {
        styleDropdownOpen.value = false;
    }
};

// Toggle style dropdown
const toggleStyleDropdown = (event) => {
    // Stop event propagation
    if (event) event.stopPropagation();
    
    // Toggle dropdown
    styleDropdownOpen.value = !styleDropdownOpen.value;
    
    // Close other dropdown if this one is opening
    if (styleDropdownOpen.value) {
        modelDropdownOpen.value = false;
    }
};

// Select a model
const selectModel = (model, event) => {
    if (event) event.stopPropagation();
    emit('change-model', model);
    // Use setTimeout to avoid click event conflict
    setTimeout(() => {
        modelDropdownOpen.value = false;
    }, 50);
};

// Select a style
const selectStyle = (style, event) => {
    if (event) event.stopPropagation();
    emit('change-style', style);
    // Use setTimeout to avoid click event conflict
    setTimeout(() => {
        styleDropdownOpen.value = false;
    }, 50);
};

// Close dropdowns when clicking outside
const closeDropdowns = () => {
    modelDropdownOpen.value = false;
    styleDropdownOpen.value = false;
};

// Set up document click handler - this avoids the need for onMounted
if (typeof document !== 'undefined') {
    document.addEventListener('click', () => {
        modelDropdownOpen.value = false;
        styleDropdownOpen.value = false;
    });
}

// Watch for changes in modelValue to resize textarea
watch(() => props.modelValue, resize);

// Focus textarea when component is mounted
watch(() => textarea.value, (newVal) => {
    if (newVal) {
        resize();
        // Focus only if not on mobile
        if (window.innerWidth >= 768) {
            textarea.value.focus();
        }
    }
}, { immediate: true });
</script>

<template>
    <form @submit.prevent="emit('send-message')" class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
            <!-- Message input area -->
            <div class="flex p-3 gap-3 items-start">
                <textarea
                    ref="textarea"
                    :value="modelValue"
                    @input="handleInput"
                    @keydown="handleKeyDown"
                    placeholder="Send a message..."
                    class="flex-1 min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                    aria-label="Message input"
                    :disabled="isProcessing || isStreaming"
                ></textarea>
                <button
                    type="submit"
                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!modelValue.trim() || isProcessing || isStreaming"
                >
                    <!-- Send Icon (Paper Airplane) -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 8.184l-5.357.882a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.826L10 15.016l6.895 1.724a.75.75 0 00.95-.826l1.414-4.949a.75.75 0 00-.826-.95L10 11.016l6.895-1.149a.75.75 0 00.826-.95l-1.414-4.949a.75.75 0 00-.95-.826L10 4.016 3.105 2.29z" /></svg>
                    <span class="sr-only">Send message</span>
                </button>
            </div>

            <!-- Bottom toolbar - Mobile adjustments -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-t border-gray-200 dark:border-gray-700 p-2 gap-2 sm:gap-0">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Model selector -->
                    <div class="relative model-dropdown-container">
                        <button
                            type="button"
                            @click.stop="toggleModelDropdown"
                            class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v2.25A2.25 2.25 0 0111.25 18.5h-6.5A2.25 2.25 0 012.5 16.25V7.75A2.25 2.25 0 014.75 5.5H7V3.75A2.25 2.25 0 019.25 1.5h6.5A2.25 2.25 0 0115.988 3.012zM13.5 6.75a.75.75 0 000-1.5H9.25a.75.75 0 00-.75.75V11h3.25a.75.75 0 00.75-.75V6.75zm-3 6.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                            <span>{{ selectedModel }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                        <div
                            v-if="modelDropdownOpen"
                            class="absolute top-auto translate-y-[-100%] left-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-hidden z-50"
                            style="margin-top: -8px;"
                        >
                            <ul>
                                <li
                                    v-for="model in availableModels"
                                    :key="model"
                                    @click.stop="selectModel(model)"
                                    class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    {{ model }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Style selector -->
                    <div class="relative style-dropdown-container">
                        <button
                            type="button"
                            @click.stop="toggleStyleDropdown"
                            class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"> <path d="M10 3.75a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm0 3.5a.75.75 0 01.75.75v4.01a.75.75 0 01-1.5 0V8.01a.75.75 0 01.75-.75zm0 7a.75.75 0 01.75.75v.01a.75.75 0 01-1.5 0v-.01a.75.75 0 01.75-.75zm-3.25-8.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zM13.25 5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75zm1.5 3.5a.75.75 0 000 1.5h-.01a.75.75 0 000-1.5h.01zm-1.5 3.5a.75.75 0 01.75-.75h.01a.75.75 0 010 1.5h-.01a.75.75 0 01-.75-.75z" /></svg>
                            <span>{{ availableStyles[selectedStyle] || selectedStyle }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                        <div
                            v-if="styleDropdownOpen"
                            class="absolute top-auto translate-y-[-100%] left-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-hidden z-50"
                            style="margin-top: -8px;"
                        >
                            <ul>
                                <li
                                    v-for="(name, style) in availableStyles"
                                    :key="style"
                                    @click.stop="selectStyle(style)"
                                    class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    {{ name }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 justify-end sm:justify-start w-full sm:w-auto">
                    <!-- Character count (Hidden on small screens) -->
                    <div class="hidden sm:block text-xs text-gray-400 dark:text-gray-500">
                        {{ modelValue.length }} chars
                    </div>

                    <!-- New Chat Button (Only shown in initial state) -->
                    <button
                        v-if="!conversation"
                        type="button"
                        @click="emit('new-conversation')"
                        class="hidden sm:inline-block text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium p-1 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20"
                        title="Start a new chat"
                    >
                        New chat
                    </button>

                    <!-- Enter to send hint -->
                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">Enter to send</span>
                </div>
            </div>
        </div>
    </form>
</template>
