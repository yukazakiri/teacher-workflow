<script setup>
import { ref, defineProps, defineEmits, watch, nextTick, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

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
        default: 'gemini-2.0-flash'
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
const messageContent = ref('');
const hiddenResources = ref([]);

// Resource mention state
const mentionDropdownOpen = ref(false);
const resources = ref([]);
const isLoadingResources = ref(false);
const mentionFilterText = ref('');
const mentionStartIndex = ref(-1);
const mentionEndIndex = ref(-1);
const cursorPosition = ref(0);
const selectedResourceIndex = ref(0); // Track currently selected resource in dropdown

// Track if this is a rich editor or plain textarea (will start with plain)
const editorMode = ref('plain');

// Clear all referenced resources
const clearResources = () => {
    hiddenResources.value = [];
    updateModelValue();
};

// Remove a specific resource from the references
const removeResource = (resourceId) => {
    hiddenResources.value = hiddenResources.value.filter(r => r.id !== resourceId);
    updateModelValue();
};

// Resize textarea based on content
const resize = () => {
    if (!textarea.value) return;

    const el = textarea.value;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 240) + 'px'; // Max height 240px
};

// Update the model with hidden resources
const updateModelValue = () => {
    let finalValue = messageContent.value;
    
    // Add resource UUIDs for each mention, but don't display them
    if (hiddenResources.value.length > 0) {
        // Add each resource UUID on a separate line after the main content
        const resourceIds = hiddenResources.value.map(resource => 
            `resource_uuid:${resource.id}`
        ).join('\n');
        
        // Append to message but only for the backend - not shown to user
        finalValue = `${finalValue.trim()}\n\n${resourceIds}`;
    }
    
    emit('update:modelValue', finalValue);
};

// Fetch class resources from API
const fetchResources = async () => {
    try {
        isLoadingResources.value = true;
        // Call web route to fetch resources
        const response = await axios.get('/class-resources/list');
        resources.value = response.data;
        // Reset selection index when fetching new resources
        selectedResourceIndex.value = 0;
    } catch (error) {
        console.error('Error fetching resources:', error);
    } finally {
        isLoadingResources.value = false;
    }
};

// Filtered resources based on input
const filteredResources = computed(() => {
    if (!mentionFilterText.value) return resources.value;
    const searchTerm = mentionFilterText.value.toLowerCase();
    return resources.value.filter(resource => 
        resource.title.toLowerCase().includes(searchTerm)
    );
});

// Currently selected resource in dropdown
const selectedResource = computed(() => {
    if (filteredResources.value.length === 0) return null;
    if (selectedResourceIndex.value >= filteredResources.value.length) {
        selectedResourceIndex.value = 0;
    }
    return filteredResources.value[selectedResourceIndex.value];
});

// Sync messageContent with modelValue when initializing
// And when modelValue changes externally
watch(() => props.modelValue, (newValue) => {
    // Only update if the value actually changed
    if (messageContent.value !== newValue) {
        // Strip hidden resource IDs when displaying to user
        const visibleContent = newValue.split('\n\nresource_uuid:')[0];
        messageContent.value = visibleContent;
        resize();
    }
}, { immediate: true });

// Watch for changes in filtered resources to reset selection index when needed
watch(filteredResources, (newResources) => {
    if (newResources.length > 0 && selectedResourceIndex.value >= newResources.length) {
        selectedResourceIndex.value = 0;
    }
});

// Also reset selection when opening dropdown
watch(mentionDropdownOpen, (isOpen) => {
    if (isOpen) {
        selectedResourceIndex.value = 0;
    }
});

// Watch for changes in editorContent to resize textarea
watch(() => messageContent.value, resize);

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

// Handle input changes and check for @ mentions
const handleInput = (event) => {
    const text = event.target.value;
    messageContent.value = text;
    cursorPosition.value = event.target.selectionStart;
    
    // Check for @ character at current cursor position
    checkForMention(text, event.target.selectionStart);
    
    // Always update model value with current resources
    updateModelValue();
    resize();
};

// Add a direct handler for '@' key to immediately open dropdown
const handleKeyDown = (event) => {
    // Special case for the @ key to immediately trigger resource dropdown
    if (event.key === '@') {
        // Let the @ character be added first then open the dropdown on next tick
        nextTick(() => {
            if (!textarea.value) return;
            
            const text = textarea.value.value;
            const cursorPos = textarea.value.selectionStart;
            
            // Immediately fetch resources and open dropdown
            fetchResources();
            mentionDropdownOpen.value = true;
            mentionStartIndex.value = cursorPos - 1; // @ position
            mentionEndIndex.value = cursorPos; // cursor position right after @
            mentionFilterText.value = '';
        });
    }
    
    // If mention dropdown is open
    if (mentionDropdownOpen.value) {
        if (event.key === 'Escape') {
            mentionDropdownOpen.value = false;
            event.preventDefault();
            return;
        }
        
        // Add handling for Shift+Enter to exit mention mode
        if (event.key === 'Enter' && event.shiftKey) {
            mentionDropdownOpen.value = false;
            event.preventDefault(); // Don't insert a newline yet
            
            // Insert a newline at cursor position
            const cursorPos = textarea.value.selectionStart;
            const text = messageContent.value;
            messageContent.value = text.substring(0, cursorPos) + '\n' + text.substring(cursorPos);
            
            // Update cursor position after the newline
            nextTick(() => {
                textarea.value.selectionStart = cursorPos + 1;
                textarea.value.selectionEnd = cursorPos + 1;
                updateModelValue();
                resize();
            });
            return;
        }
        
        // Add handling for space key to exit mention mode when appropriate
        if (event.key === ' ' && mentionFilterText.value.trim().length > 0) {
            // Only close if we've typed something after @ (not just "@")
            mentionDropdownOpen.value = false;
            // Let the space be inserted normally
            return;
        }
        
        if (event.key === 'ArrowDown') {
            event.preventDefault(); // Prevent default scrolling
            if (filteredResources.value.length > 0) {
                selectedResourceIndex.value = (selectedResourceIndex.value + 1) % filteredResources.value.length;
            }
            return;
        }
        
        if (event.key === 'ArrowUp') {
            event.preventDefault(); // Prevent default scrolling
            if (filteredResources.value.length > 0) {
                selectedResourceIndex.value = (selectedResourceIndex.value - 1 + filteredResources.value.length) % filteredResources.value.length;
            }
            return;
        }
        
        if (event.key === 'Enter' || event.key === 'Tab') {
            // Select the currently highlighted resource if available
            if (filteredResources.value.length > 0) {
                event.preventDefault();
                selectResource(filteredResources.value[selectedResourceIndex.value]);
                return;
            }
        }
    }
    
    // Regular Enter key handling
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        if (!props.isProcessing && !props.isStreaming && messageContent.value.trim()) {
            emit('send-message');
            // Clear hidden resources after sending
            hiddenResources.value = [];
        }
    }
};

// Check if user is typing @ to trigger mention
const checkForMention = (text, cursorPos) => {
    // Get the text before the cursor
    const textBeforeCursor = text.substring(0, cursorPos);
    
    // Find the last @ character before cursor
    const lastAtPos = textBeforeCursor.lastIndexOf('@');
    
    // If there's an @ character and it's either at the start or preceded by whitespace
    if (lastAtPos !== -1 && (lastAtPos === 0 || /\s/.test(textBeforeCursor.charAt(lastAtPos - 1)))) {
        // Get text between @ and cursor
        const textAfterAt = text.substring(lastAtPos + 1, cursorPos);
        
        // Set the filter text
        mentionFilterText.value = textAfterAt;
        
        // Check if we're in an existing completed mention
        const isInCompletedMention = hiddenResources.value.some(r => {
            const mentionText = `@${r.title}`;
            const mentionPos = textBeforeCursor.lastIndexOf(mentionText);
            // Check if this @ is part of an existing mention
            return mentionPos !== -1 && mentionPos <= lastAtPos && 
                   mentionPos + mentionText.length >= lastAtPos;
        });
        
        // If we're still typing after @ and not in a completed mention,
        // show the dropdown
        if (!isInCompletedMention && cursorPos > lastAtPos) {
            // Only open dropdown and fetch if not already open
            if (!mentionDropdownOpen.value) {
                fetchResources();
                mentionDropdownOpen.value = true;
            }
            mentionStartIndex.value = lastAtPos;
            mentionEndIndex.value = cursorPos;
        }
    } else {
        // No @ found or invalid position, close dropdown
        mentionDropdownOpen.value = false;
    }
};

// Insert selected resource mention at current position
const selectResource = (resource) => {
    if (!textarea.value) return;
    
    const text = messageContent.value;
    const mentionText = `@${resource.title} `;  // Add space after mention
    
    // Insert mention text in place of the @filterText
    messageContent.value = 
        text.substring(0, mentionStartIndex.value) + 
        mentionText + 
        text.substring(mentionEndIndex.value);
    
    // Store the resource in our hidden resources array
    // Check if it's already there first
    if (!hiddenResources.value.some(r => r.id === resource.id)) {
        hiddenResources.value.push(resource);
    }
    
    // Update the model with the mention but without displaying the UUID
    updateModelValue();
    
    // Close the dropdown
    mentionDropdownOpen.value = false;
    
    // Focus back on textarea and set cursor after mention
    nextTick(() => {
        const newPosition = mentionStartIndex.value + mentionText.length;
        textarea.value.focus();
        textarea.value.setSelectionRange(newPosition, newPosition);
        resize();
    });
};

// Resource mention button handler - show all resources
const showResourceDropdown = () => {
    // Only proceed if we can show the dropdown
    if (props.isProcessing || props.isStreaming) return;
    
    // Fetch all resources
    fetchResources();
    
    // Reset filter text since we're opening via button
    mentionFilterText.value = '';
    
    // Get current cursor position
    if (textarea.value) {
        const cursorPos = textarea.value.selectionStart;
        mentionStartIndex.value = cursorPos;
        mentionEndIndex.value = cursorPos;
    }
    
    // Open the dropdown
    mentionDropdownOpen.value = true;
};

// Handle cursor position change
const handleCursorPosition = () => {
    if (!textarea.value) return;
    cursorPosition.value = textarea.value.selectionStart;
    
    // If cursor moved to a different position, check mention state
    if (mentionDropdownOpen.value) {
        const currentPos = textarea.value.selectionStart;
        
        // Close dropdown if cursor moved before the @ or to a different part entirely
        if (currentPos < mentionStartIndex.value || currentPos > mentionEndIndex.value + 2) {
            mentionDropdownOpen.value = false;
        }
    }
};

// Toggle model dropdown
const toggleModelDropdown = (event) => {
    // Stop event propagation
    if (event) event.stopPropagation();
    
    // Toggle dropdown
    modelDropdownOpen.value = !modelDropdownOpen.value;
    
    // Close other dropdowns if this one is opening
    if (modelDropdownOpen.value) {
        styleDropdownOpen.value = false;
        mentionDropdownOpen.value = false;
    }
};

// Toggle style dropdown
const toggleStyleDropdown = (event) => {
    // Stop event propagation
    if (event) event.stopPropagation();
    
    // Toggle dropdown
    styleDropdownOpen.value = !styleDropdownOpen.value;
    
    // Close other dropdowns if this one is opening
    if (styleDropdownOpen.value) {
        modelDropdownOpen.value = false;
        mentionDropdownOpen.value = false;
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
    mentionDropdownOpen.value = false;
};

// Set up document click handler
const handleDocumentClick = () => {
    modelDropdownOpen.value = false;
    styleDropdownOpen.value = false;
    mentionDropdownOpen.value = false;
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <form @submit.prevent="emit('send-message')" class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500">
            <!-- Message input area -->
            <div class="flex p-3 gap-3 items-start">
                <div class="relative flex-1">
                    <!-- Rich text editor for mentions -->
                    <textarea
                        ref="textarea"
                        :value="messageContent"
                        @input="handleInput"
                        @keydown="handleKeyDown"
                        @click="handleCursorPosition"
                        @blur="handleCursorPosition"
                        placeholder="Send a message... (Use @ to mention resources)"
                        class="w-full min-h-[3.5rem] max-h-60 resize-none border-0 bg-transparent p-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm"
                        aria-label="Message input"
                        :disabled="isProcessing || isStreaming"
                    ></textarea>
                    
                    <!-- Resource mention dropdown -->
                    <div 
                        v-if="mentionDropdownOpen && !isProcessing && !isStreaming" 
                        class="absolute z-50 max-h-60 w-full sm:w-3/4 overflow-y-auto rounded-md bg-white dark:bg-gray-800 shadow-md border border-gray-300 dark:border-gray-700 mt-1"
                    >
                        <div class="p-2 text-xs font-medium text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <span>Resources</span>
                            <span class="text-xs text-gray-400 flex items-center">
                                <kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md">Tab</kbd>
                                <span class="mx-1">or</span>
                                <kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md">Enter</kbd>
                                <span class="mx-1">to select</span>
                                <kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md">Esc</kbd>
                                <span class="mx-1">to cancel</span>
                            </span>
                        </div>
                        <div v-if="isLoadingResources" class="p-3 text-sm text-gray-600 dark:text-gray-300">
                            Loading resources...
                        </div>
                        <div v-else-if="filteredResources.length === 0" class="p-3 text-sm text-gray-600 dark:text-gray-300">
                            No resources found matching "{{ mentionFilterText }}"
                        </div>
                        <ul v-else>
                            <li 
                                v-for="(resource, index) in filteredResources" 
                                :key="resource.id" 
                                @click.stop="selectResource(resource)"
                                class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center"
                                :class="{'bg-gray-100 dark:bg-gray-700': index === selectedResourceIndex}"
                            >
                                <!-- Resource icon (file type) -->
                                <span class="mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-primary-500">
                                        <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                                    </svg>
                                </span>
                                <!-- Resource title & category -->
                                <div>
                                    <div class="font-medium">{{ resource.title }}</div>
                                    <div v-if="resource.category" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ resource.category.name }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                        
                        <!-- Mention helper text -->
                        <div class="p-2 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
                            <div>Type <kbd class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md">Space</kbd> or <kbd class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md">Shift+Enter</kbd> to exit mention mode</div>
                        </div>
                    </div>
                    
                    <!-- Resource badges - show the mentioned resources -->
                    <div v-if="hiddenResources.length > 0" class="flex flex-wrap gap-1 mt-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mr-1 flex items-center">
                            Referenced resources:
                            <button 
                                @click="clearResources" 
                                class="ml-2 text-red-500 hover:text-red-700 text-xs underline"
                                title="Clear all resource references"
                            >
                                Clear all
                            </button>
                        </div>
                        <div 
                            v-for="resource in hiddenResources" 
                            :key="resource.id"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-primary-100 text-primary-800 dark:bg-primary-900/20 dark:text-primary-300 group"
                        >
                            <span class="mr-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                    <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                                </svg>
                            </span>
                            {{ resource.title }}
                            <button 
                                @click.stop="removeResource(resource.id)" 
                                class="ml-1 text-gray-500 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                title="Remove this resource reference"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button
                    type="submit"
                    class="self-end rounded-lg bg-primary-600 p-2 text-white transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!messageContent.trim() || isProcessing || isStreaming"
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
                    
                    <!-- Resource mention button -->
                    <button
                        type="button"
                        @click.stop="showResourceDropdown"
                        class="flex items-center gap-1 rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        :disabled="isProcessing || isStreaming"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                            <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                        </svg>
                        <span>Add Resource</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 justify-end sm:justify-start w-full sm:w-auto">
                    <!-- Character count and resource count -->
                    <div class="hidden sm:block text-xs text-gray-400 dark:text-gray-500">
                        {{ messageContent.length }} chars
                        <span v-if="hiddenResources.length > 0" class="ml-2">| {{ hiddenResources.length }} resources</span>
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
