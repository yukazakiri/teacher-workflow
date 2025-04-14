<script setup>
import { ref, computed, onMounted, watch } from "vue";
import ChatSidebar from "./components/ChatSidebar.vue";
import ChatHeader from "./components/ChatHeader.vue";
import ChatMessages from "./components/ChatMessages.vue";
import ChatInput from "./components/ChatInput.vue";
import ChatSuggestions from "./components/ChatSuggestions.vue";
import RecentChats from "./components/RecentChats.vue";
import axios from "axios";
import { debounce } from "lodash";

// Configure Axios to include CSRF token for all requests
const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
if (token) {
    axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
} else {
    console.warn("CSRF token not found. AI Widget POST requests might fail.");
}

const props = defineProps({
    /**
     * Livewire wire object (optional).
     * @type {Object}
     */
    wire: {
        type: Object,
        default: () => ({}),
    },
    /**
     * Mingle data (optional).
     * @type {any}
     */
    mingleData: {},
    /**
     * User object containing user details.
     * @type {Object}
     * @property {number|string} id - User ID.
     * @property {string} name - User name.
     */
    user: {
        type: Object,
        default: () => ({}),
    },
});

// --- Reactive State ---

/** @type {import('vue').Ref<boolean>} Controls the visibility of the chat history sidebar. */
const sidebarOpen = ref(false);
/** @type {import('vue').Ref<Object|null>} Holds the currently active conversation object, including messages. */
const conversation = ref(null);
/** @type {import('vue').Ref<Array<Object>>} List of all fetched conversations for the user. */
const conversations = ref([]);
/** @type {import('vue').Ref<boolean>} Indicates if an AI request is currently being processed (sent but not yet streaming). */
const isProcessing = ref(false);
/** @type {import('vue').Ref<boolean>} Indicates if an AI response is currently streaming. */
const isStreaming = ref(false);
/** @type {import('vue').Ref<string>} The current content of the chat input field. */
const message = ref("");
/** @type {import('vue').Ref<string>} The ID of the selected AI model. */
const selectedModel = ref("gemini-2.0-flash"); // Default model
/** @type {import('vue').Ref<string>} The selected conversation style/tone. */
const selectedStyle = ref("balanced"); // Default style
/** @type {import('vue').Ref<Array<string>>} List of available AI model IDs. */
const availableModels = ref([]);
/** @type {import('vue').Ref<Object<string, string>>} Dictionary of available conversation styles (id: name). */
const availableStyles = ref({});
/** @type {import('vue').Ref<string|null>} The ID of the currently loaded conversation. Null for a new chat. */
const currentConversationId = ref(null);
/** @type {import('vue').Ref<boolean>} Indicates if conversations are being loaded. */
const loadingConversations = ref(true);
/** @type {import('vue').Ref<boolean>} Indicates if AI models are being loaded. */
const loadingModels = ref(true);
/** @type {import('vue').Ref<boolean>} Indicates if messages for a specific conversation are being loaded. */
const loadingMessages = ref(false);
/** @type {import('vue').Ref<string|null>} Stores any user-facing error message. */
const error = ref(null);
/** @type {import('vue').Ref<boolean>} Controls the visibility of the delete confirmation dialog. */
const showDeleteConfirm = ref(false);
/** @type {import('vue').Ref<string|null>} The ID of the conversation marked for deletion. */
const conversationIdToDelete = ref(null);

// --- Computed Properties ---

/**
 * @returns {string} 'morning', 'afternoon', or 'evening' based on the current time.
 */
const timeOfDay = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return "morning";
    if (hour < 18) return "afternoon";
    return "evening";
});

// --- Methods ---

/**
 * Toggles the visibility of the chat history sidebar.
 */
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

/**
 * Fetches the list of recent conversations from the backend.
 */
const fetchRecentChats = async () => {
    loadingConversations.value = true;
    error.value = null;
    try {
        console.log("Fetching conversations");
        const response = await axios.get("/ai/conversations");
        conversations.value = response.data;
        console.log(`Received ${response.data.length} conversations`);
    } catch (err) {
        console.error("Failed to fetch conversations:", err);
        error.value =
            "Failed to load conversations. Please check your connection and try again.";
    } finally {
        loadingConversations.value = false;
    }
};

/**
 * Loads a specific conversation and its messages.
 * @param {string|number} id - The ID of the conversation to load.
 */
const loadConversation = async (id) => {
    if (currentConversationId.value === id) return; // Avoid reloading the same conversation

    loadingMessages.value = true;
    error.value = null;
    currentConversationId.value = id;

    try {
        console.log(`Loading conversation ${id}`);
        const response = await axios.get(`/ai/conversations/${id}/messages`);

        // Ensure messages have a consistent structure
        const loadedConversation = response.data.conversation;
        loadedConversation.messages = (loadedConversation.messages || []).map(
            (msg) => ({
                id: msg.id, // Ensure messages have unique IDs if possible
                role: msg.role,
                content: msg.content,
                timestamp: new Date(msg.created_at), // Standardize timestamp
                created_at: new Date(msg.created_at).toLocaleTimeString([], {
                    hour: "numeric",
                    minute: "2-digit",
                }), // Keep original format if needed by ChatMessages
            }),
        );

        conversation.value = loadedConversation;
        console.log(
            `Loaded conversation ${id} with ${conversation.value.messages.length} messages`,
        );

        // Set model and style from loaded conversation, fallback to defaults or available
        selectedModel.value =
            conversation.value.model ||
            (availableModels.value.length > 0
                ? availableModels.value[0]
                : "default-model-id"); // Provide a fallback ID
        selectedStyle.value = conversation.value.style || "balanced";

        scrollToBottom();
    } catch (err) {
        console.error(`Failed to load conversation ${id}:`, err);
        error.value = "Failed to load the conversation. Please try again.";
        startNewChat(); // Reset to a new chat state on error
    } finally {
        loadingMessages.value = false;
        sidebarOpen.value = false; // Close sidebar after loading
    }
};

/**
 * Resets the state to start a new conversation.
 */
const startNewChat = () => {
    currentConversationId.value = null;
    conversation.value = null; // Clear the conversation object
    message.value = "";
    error.value = null;
    isProcessing.value = false;
    isStreaming.value = false;

    // Reset to default model/style if available
    if (availableModels.value.length > 0) {
        selectedModel.value = availableModels.value[0];
    }
    if (Object.keys(availableStyles.value).length > 0) {
        selectedStyle.value = Object.keys(availableStyles.value)[0]; // Use the first available style key
    } else {
        selectedStyle.value = "balanced"; // Hardcoded default if fetch fails
    }
    console.log("Started new chat session");
};

/**
 * Deletes a conversation permanently.
 * @param {string|number} id - The ID of the conversation to delete.
 */
const deleteConversation = async (id) => {
    // Optimistic UI update
    const originalConversations = [...conversations.value];
    conversations.value = conversations.value.filter((c) => c.id !== id);

    // If deleting the current conversation, start a new one
    if (currentConversationId.value === id) {
        startNewChat();
    }

    // Hide confirmation modal
    showDeleteConfirm.value = false;
    conversationIdToDelete.value = null;

    try {
        await axios.delete(`/ai/conversations/${id}`);
        console.log(`Conversation ${id} deleted successfully.`);
    } catch (err) {
        console.error(`Error deleting conversation ${id}:`, err);
        error.value = "Failed to delete conversation. Please try again.";
        // Revert optimistic update on failure
        conversations.value = originalConversations;
        // Optionally reload the conversation if it was the current one
        if (currentConversationId.value === id) {
            loadConversation(id); // Attempt to reload if deletion failed
        }
    }
};

/**
 * Sends the user's message to the AI backend and handles the streaming response.
 */
const sendMessage = async () => {
    const trimmedMessage = message.value.trim();
    if (!trimmedMessage || isProcessing.value || isStreaming.value) return;

    isProcessing.value = true; // Indicate processing started
    isStreaming.value = false; // Not streaming yet
    error.value = null;

    // Extract visible content (what the user actually sees) for display
    // Assume resource UUIDs are appended after double newline
    const visibleContent = trimmedMessage.split("\n\nresource_uuid:")[0];
    const fullMessageToSend = trimmedMessage; // Send the full message including any metadata

    // If starting a new chat, initialize conversation object
    if (!conversation.value) {
        conversation.value = {
            id: null, // Will be set by backend if new
            messages: [],
            model: selectedModel.value,
            style: selectedStyle.value,
            // Add other relevant conversation metadata if needed
        };
    }

    // Add user message to the conversation's messages array
    const userMsg = {
        id: `user-${Date.now()}`, // Temporary unique ID for rendering
        role: "user",
        content: visibleContent,
        timestamp: new Date(),
        created_at: new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        }),
    };
    conversation.value.messages.push(userMsg);

    // Add temporary AI message placeholder
    const assistantMsgPlaceholder = {
        id: `assistant-${Date.now()}`, // Temporary unique ID
        role: "assistant",
        content: "",
        timestamp: new Date(),
        created_at: new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        }),
        isStreaming: true, // Add a flag for styling
    };
    conversation.value.messages.push(assistantMsgPlaceholder);

    message.value = ""; // Clear input field
    scrollToBottom();

    try {
        const data = {
            message: fullMessageToSend,
            model: selectedModel.value,
            style: selectedStyle.value,
            conversation_id: currentConversationId.value, // Send current ID (null if new)
        };

        console.log("Sending AI request:", {
            endpoint: "/ai/stream",
            messagePreview: visibleContent.substring(0, 50) + "...",
            hasResources: fullMessageToSend.includes("resource_uuid:"),
            model: selectedModel.value,
            style: selectedStyle.value,
            conversationId: currentConversationId.value,
        });

        const response = await fetch("/ai/stream", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "X-Requested-With": "XMLHttpRequest",
                Accept: "text/event-stream",
            },
            credentials: "same-origin", // Important for CSRF
            body: JSON.stringify(data),
        });

        isProcessing.value = false; // Processing done, streaming starts or error occurred

        if (!response.ok || !response.body) {
            console.error("Server response error:", {
                status: response.status,
                statusText: response.statusText,
            });
            const errorText = await response.text();
            throw new Error(
                `Server error ${response.status}: ${errorText || response.statusText}`,
            );
        }

        isStreaming.value = true; // Streaming started
        assistantMsgPlaceholder.isStreaming = true; // Keep streaming indicator

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let accumulatedResponse = "";
        let conversationDataFound = null;

        console.log("Stream started, processing chunks...");

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                console.log("Stream completed.");
                break;
            }

            const chunk = decoder.decode(value, { stream: true });
            accumulatedResponse += chunk;

            // Temporarily hide conversation data comment during streaming
            let displayResponse = accumulatedResponse.replace(
                /<!-- CONVERSATION_DATA:.+?-->/,
                "",
            );

            // Update the assistant message content
            assistantMsgPlaceholder.content = displayResponse;

            // Check for embedded conversation data without modifying the main stream yet
            const jsonMatch = accumulatedResponse.match(
                /<!-- CONVERSATION_DATA:(.+?)-->/,
            );
            if (jsonMatch && jsonMatch[1]) {
                try {
                    conversationDataFound = JSON.parse(jsonMatch[1]);
                } catch (parseErr) {
                    console.error(
                        "Failed to parse conversation data chunk:",
                        parseErr,
                    );
                    // Continue streaming, handle data at the end
                }
            }

            scrollToBottom(); // Scroll as content arrives
        }

        // Final processing after stream ends
        isStreaming.value = false;
        assistantMsgPlaceholder.isStreaming = false;

        // Permanently remove conversation data comment from final content
        assistantMsgPlaceholder.content = accumulatedResponse.replace(
            /<!-- CONVERSATION_DATA:.+?-->/,
            "",
        );

        if (conversationDataFound) {
            console.log("Applying conversation data:", conversationDataFound);
            if (
                conversationDataFound.conversation_id &&
                !currentConversationId.value
            ) {
                currentConversationId.value =
                    conversationDataFound.conversation_id;
                conversation.value.id = conversationDataFound.conversation_id; // Update conversation object ID
                console.log(
                    `New conversation ID set: ${currentConversationId.value}`,
                );
                fetchRecentChats(); // Refresh sidebar list
            }
            // Update model/style if returned by backend (optional)
            if (conversationDataFound.model)
                conversation.value.model = conversationDataFound.model;
            if (conversationDataFound.style)
                conversation.value.style = conversationDataFound.style;
        } else {
            console.log("No conversation data found in response.");
            // If it was a new chat but no ID came back, something might be wrong
            if (!currentConversationId.value) {
                console.warn(
                    "New chat initiated, but no conversation ID received from backend.",
                );
                // Optionally surface an error or retry? For now, just log.
            }
        }
    } catch (err) {
        console.error("Error sending message or processing stream:", err);
        error.value = `Failed to get response: ${err.message}. Please try again.`;
        isProcessing.value = false;
        isStreaming.value = false;

        // Update placeholder message with error
        assistantMsgPlaceholder.content = `[Error: ${err.message || "Failed to get response"}]`;
        assistantMsgPlaceholder.isStreaming = false; // Ensure streaming indicator is off
    } finally {
        // Ensure flags are reset even if errors occur mid-stream
        isProcessing.value = false;
        isStreaming.value = false;
        if (assistantMsgPlaceholder)
            assistantMsgPlaceholder.isStreaming = false;
        scrollToBottom();
    }
};

/**
 * Sets the chat input field content.
 * @param {string} promptText - The text to set as the input value.
 */
const setPrompt = (promptText) => {
    message.value = promptText;
    // Maybe focus the input field here?
    // document.getElementById('chat-input-textarea')?.focus();
};

/**
 * Updates the selected model and persists the change if in an active conversation.
 * @param {string} modelId - The ID of the model to select.
 */
const changeModel = async (modelId) => {
    if (selectedModel.value === modelId) return;
    selectedModel.value = modelId;
    // Update preference on backend only for existing conversations
    if (currentConversationId.value) {
        await updateModelPreference();
    }
};

/**
 * Updates the selected style and persists the change if in an active conversation.
 * @param {string} styleId - The ID of the style to select.
 */
const changeStyle = async (styleId) => {
    if (selectedStyle.value === styleId) return;
    selectedStyle.value = styleId;
    // Update preference on backend only for existing conversations
    if (currentConversationId.value) {
        await updateStylePreference();
    }
};

/**
 * Regenerates the last assistant response based on the preceding user message.
 */
const regenerateLastMessage = async () => {
    if (
        !conversation.value?.messages ||
        conversation.value.messages.length < 2 ||
        isProcessing.value ||
        isStreaming.value
    ) {
        console.warn(
            "Cannot regenerate: No conversation, not enough messages, or already processing.",
        );
        return;
    }

    // Find the index of the last assistant message to replace
    const lastMessageIndex = conversation.value.messages.length - 1;
    if (conversation.value.messages[lastMessageIndex]?.role !== "assistant") {
        console.warn(
            "Cannot regenerate: Last message is not from the assistant.",
        );
        return;
    }

    // Find the preceding user message
    const lastUserMessageIndex = lastMessageIndex - 1;
    if (
        lastUserMessageIndex < 0 ||
        conversation.value.messages[lastUserMessageIndex]?.role !== "user"
    ) {
        console.warn(
            "Cannot regenerate: Could not find preceding user message.",
        );
        return;
    }

    const lastUserMessageContent =
        conversation.value.messages[lastUserMessageIndex].content;

    // Prepare history (all messages up to and including the last user message)
    const history = conversation.value.messages
        .slice(0, lastUserMessageIndex + 1)
        .map((m) => ({ role: m.role, content: m.content }));

    if (!history.length) {
        console.warn("Cannot regenerate: History is empty.");
        return;
    }

    console.log("Regenerating response...");

    isProcessing.value = true;
    isStreaming.value = false; // Will be set true when stream starts
    error.value = null;

    // Replace the last assistant message with a streaming placeholder
    const assistantMsgPlaceholder = {
        id: `assistant-regen-${Date.now()}`,
        role: "assistant",
        content: "",
        timestamp: new Date(),
        created_at: new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        }),
        isStreaming: true,
    };
    // Replace the last message directly
    conversation.value.messages.splice(
        lastMessageIndex,
        1,
        assistantMsgPlaceholder,
    );

    scrollToBottom();

    try {
        const data = {
            message: lastUserMessageContent, // The prompt is the last user message
            model: selectedModel.value,
            style: selectedStyle.value,
            conversation_id: currentConversationId.value,
            history: history, // Provide context
        };

        console.log("Sending regeneration request:", {
            endpoint: "/ai/stream",
            conversationId: currentConversationId.value,
            model: selectedModel.value,
            style: selectedStyle.value,
            historyLength: history.length,
        });

        const response = await fetch("/ai/stream", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "X-Requested-With": "XMLHttpRequest",
                Accept: "text/event-stream",
            },
            credentials: "same-origin",
            body: JSON.stringify(data),
        });

        isProcessing.value = false; // Processing done

        if (!response.ok || !response.body) {
            console.error("Server response error during regeneration:", {
                status: response.status,
                statusText: response.statusText,
            });
            const errorText = await response.text();
            throw new Error(
                `Server error ${response.status}: ${errorText || response.statusText}`,
            );
        }

        isStreaming.value = true; // Stream starts
        assistantMsgPlaceholder.isStreaming = true;

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let accumulatedResponse = "";

        console.log("Regeneration stream started...");

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                console.log("Regeneration stream completed.");
                break;
            }

            const chunk = decoder.decode(value, { stream: true });
            accumulatedResponse += chunk;
            assistantMsgPlaceholder.content = accumulatedResponse.replace(
                /<!-- CONVERSATION_DATA:.+?-->/,
                "",
            ); // Update content, hide data tag
            scrollToBottom();
        }

        isStreaming.value = false;
        assistantMsgPlaceholder.isStreaming = false;
        // Ensure final content doesn't have the data tag
        assistantMsgPlaceholder.content = accumulatedResponse.replace(
            /<!-- CONVERSATION_DATA:.+?-->/,
            "",
        );

        // No need to handle conversation ID here as we are in an existing conversation.
        console.log("Regeneration successful.");
    } catch (err) {
        console.error("Error regenerating message:", err);
        error.value = `Failed to regenerate response: ${err.message}.`;
        isProcessing.value = false;
        isStreaming.value = false;

        // Update placeholder with error
        assistantMsgPlaceholder.content = `[Error regenerating: ${err.message || "Failed"}]`;
        assistantMsgPlaceholder.isStreaming = false;
    } finally {
        isProcessing.value = false;
        isStreaming.value = false;
        if (assistantMsgPlaceholder)
            assistantMsgPlaceholder.isStreaming = false;
        scrollToBottom();
    }
};

/**
 * Fetches the available AI models from the backend.
 */
const fetchAvailableModels = async () => {
    loadingModels.value = true;
    try {
        console.log("Fetching available AI models");
        const response = await axios.get("/ai/models");
        availableModels.value = response.data; // Assuming response.data is an array of model IDs/objects
        console.log("Received models:", availableModels.value);

        // Set default model if none is selected or the current one isn't available
        if (
            (!selectedModel.value ||
                !availableModels.value.includes(selectedModel.value)) &&
            availableModels.value.length > 0
        ) {
            selectedModel.value = availableModels.value[0];
            console.log(`Default model set to: ${selectedModel.value}`);
        }
    } catch (err) {
        console.error("Failed to fetch available models:", err);
        error.value = "Failed to load AI models. Using default.";
        // Keep the hardcoded default or potentially disable selection
    } finally {
        loadingModels.value = false;
    }
};

/**
 * Fetches the available conversation styles from the backend.
 */
const fetchAvailableStyles = async () => {
    try {
        console.log("Fetching available AI styles");
        const response = await axios.get("/ai/styles");
        availableStyles.value = response.data; // Assuming response.data is an object like { 'id': 'Name' }
        console.log("Received styles:", availableStyles.value);

        // Set default style if none is selected or the current one isn't available
        const styleKeys = Object.keys(availableStyles.value);
        if (
            (!selectedStyle.value ||
                !availableStyles.value[selectedStyle.value]) &&
            styleKeys.length > 0
        ) {
            selectedStyle.value = styleKeys[0]; // Use the first available style key
            console.log(`Default style set to: ${selectedStyle.value}`);
        }
    } catch (err) {
        console.error("Failed to fetch available styles:", err);
        // Provide hardcoded fallback styles
        availableStyles.value = {
            balanced: "Balanced",
            creative: "Creative",
            precise: "Precise",
        };
        if (
            !selectedStyle.value ||
            !availableStyles.value[selectedStyle.value]
        ) {
            selectedStyle.value = "balanced"; // Fallback default
        }
        console.warn("Using fallback styles.");
    }
};

/**
 * Updates the model preference for the current conversation on the backend.
 * Debounced to avoid rapid API calls if selection changes quickly.
 */
const updateModelPreference = debounce(async () => {
    if (!currentConversationId.value) return;

    try {
        console.log(
            `Updating model preference for ${currentConversationId.value} to ${selectedModel.value}`,
        );
        await axios.put(
            `/ai/conversations/${currentConversationId.value}/model`,
            {
                model: selectedModel.value,
            },
        );
    } catch (err) {
        console.error("Failed to update model preference on backend:", err);
        // Non-critical, don't show user error unless necessary
    }
}, 500); // Debounce for 500ms

/**
 * Updates the style preference for the current conversation on the backend.
 * Debounced to avoid rapid API calls.
 */
const updateStylePreference = debounce(async () => {
    if (!currentConversationId.value) return;

    try {
        console.log(
            `Updating style preference for ${currentConversationId.value} to ${selectedStyle.value}`,
        );
        await axios.put(
            `/ai/conversations/${currentConversationId.value}/style`,
            {
                style: selectedStyle.value,
            },
        );
    } catch (err) {
        console.error("Failed to update style preference on backend:", err);
        // Non-critical
    }
}, 500); // Debounce for 500ms

/**
 * Scrolls the chat messages container to the bottom.
 * Uses a short timeout to allow the DOM to update after new messages are added.
 */
const scrollToBottom = () => {
    setTimeout(() => {
        const container = document.getElementById("chat-messages-container");
        if (container) {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: "smooth",
            });
        }
    }, 100);
};

/**
 * Sets the conversation ID to be deleted and shows the confirmation modal.
 * @param {string|number} conversationId - The ID of the conversation to confirm deletion for.
 */
const confirmDelete = (conversationId) => {
    conversationIdToDelete.value = conversationId;
    showDeleteConfirm.value = true; // Assuming a modal component reacts to this
    console.log(
        `Confirmation requested for deleting conversation ${conversationId}`,
    );
    // In a real app, you'd likely use a modal component bound to showDeleteConfirm
    // For now, we'll just call delete directly for simplicity if no modal exists.
    // Replace this direct call with your modal logic if available.
    if (
        confirm(
            `Are you sure you want to delete conversation ${conversationId}?`,
        )
    ) {
        deleteConversation(conversationId);
    } else {
        cancelDelete();
    }
};

/**
 * Hides the delete confirmation modal and clears the ID.
 */
const cancelDelete = () => {
    showDeleteConfirm.value = false;
    conversationIdToDelete.value = null;
    console.log("Deletion cancelled.");
};

// --- Lifecycle Hooks ---

onMounted(() => {
    fetchAvailableModels();
    fetchAvailableStyles();
    fetchRecentChats();
    startNewChat(); // Ensure a clean initial state
});

// --- Watchers ---

// Watchers trigger debounced preference updates when model/style change *during an active conversation*.
watch(selectedModel, (newModel, oldModel) => {
    if (newModel !== oldModel && currentConversationId.value) {
        updateModelPreference();
    }
});

watch(selectedStyle, (newStyle, oldStyle) => {
    if (newStyle !== oldStyle && currentConversationId.value) {
        updateStylePreference();
    }
});
</script>

<template>
    <div class="min-h-screen bg-background flex flex-col">
        <!-- Right Sidebar (Chat History) -->
        <ChatSidebar
            :open="sidebarOpen"
            @close="sidebarOpen = false"
            :chats="conversations"
            :current-conversation-id="currentConversationId"
            :loading="loadingConversations"
            @load-conversation="loadConversation"
            @delete-conversation="confirmDelete"
            @new-conversation="startNewChat"
        />

        <!-- Overlay for mobile sidebar -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 min-h-0">
            <!-- Header -->
            <ChatHeader
                :conversation="conversation"
                :can-regenerate="
                    conversation?.messages?.length >= 2 &&
                    conversation.messages[conversation.messages.length - 1]
                        ?.role === 'assistant'
                "
                :is-processing="isProcessing || isStreaming"
                @toggle-sidebar="toggleSidebar"
                @new-conversation="startNewChat"
                @regenerate-last-message="regenerateLastMessage"
            />

            <!-- User-facing Error Display -->
            <div
                v-if="error"
                class="px-4 py-2 bg-red-100 border-l-4 border-red-500 text-red-700 dark:bg-red-900 dark:border-red-700 dark:text-red-200"
                role="alert"
            >
                <p class="font-bold">Error</p>
                <p>{{ error }}</p>
            </div>

            <!-- Main Chat Area -->
            <main class="flex-1 flex flex-col mx-auto max-w-5xl w-full min-h-0">
                <!-- Initial State (No Active Conversation) -->
                <template v-if="!conversation">
                    <div
                        class="flex-1 flex flex-col items-center justify-center p-4 w-full"
                    >
                        <!-- Welcome Message & Suggestions -->
                        <div class="w-full max-w-3xl mb-8 text-center">
                            <div
                                class="text-primary-600 dark:text-primary-400 mb-4"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="48"
                                    height="48"
                                    fill="currentColor"
                                    viewBox="0 0 256 256"
                                    class="mx-auto"
                                >
                                    <path
                                        d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"
                                    ></path>
                                </svg>
                            </div>
                            <h1
                                class="text-2xl font-semibold text-gray-900 dark:text-white mb-2"
                            >
                                Good {{ timeOfDay }}, Teacher!
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                How can I assist you? Select a suggestion or
                                type your query below.
                            </p>
                            <ChatSuggestions @set-prompt="setPrompt" />
                        </div>

                        <!-- Recent Chats Grid (Only if not loading and has items) -->
                        <RecentChats
                            v-if="
                                !loadingConversations &&
                                conversations.length > 0
                            "
                            :chats="conversations"
                            @load-conversation="loadConversation"
                            @delete-conversation="confirmDelete"
                        />
                        <div
                            v-else-if="loadingConversations"
                            class="text-center text-gray-500 dark:text-gray-400 mt-8"
                        >
                            Loading recent chats...
                        </div>
                        <div
                            v-else
                            class="text-center text-gray-500 dark:text-gray-400 mt-8"
                        >
                            No recent chats found. Start a new conversation!
                        </div>
                    </div>
                    <!-- Chat Input Form (Always visible at bottom in initial state) -->
                    <div
                        class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700 w-full max-w-5xl mx-auto px-4"
                    >
                        <ChatInput
                            v-model="message"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                            :selected-model="selectedModel"
                            :selected-style="selectedStyle"
                            :available-models="availableModels"
                            :available-styles="availableStyles"
                            :loading-models="loadingModels"
                            @send-message="sendMessage"
                            @change-model="changeModel"
                            @change-style="changeStyle"
                        />
                    </div>
                </template>

                <!-- Active Conversation State -->
                <template v-else>
                    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <!-- Messages Container - Takes remaining space, scrolls internally -->
                        <ChatMessages
                            id="chat-messages-container"
                            class="flex-1 overflow-y-auto p-4"
                            :conversation="conversation"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                            :loading="loadingMessages"
                        />
                    </div>
                    <!-- Fixed Chat Input Form (Active Conversation) -->
                    <div
                        class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700 w-full max-w-5xl mx-auto px-4"
                    >
                        <ChatInput
                            v-model="message"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                            :selected-model="selectedModel"
                            :selected-style="selectedStyle"
                            :available-models="availableModels"
                            :available-styles="availableStyles"
                            :loading-models="loadingModels"
                            :conversation="conversation"
                            @send-message="sendMessage"
                            @change-model="changeModel"
                            @change-style="changeStyle"
                        />
                    </div>
                </template>
            </main>
        </div>
    </div>
</template>

<style>
/* Basic Typing Indicator (can be enhanced) */
.typing-indicator span {
    height: 5px;
    width: 5px;
    margin: 0 1px;
    background-color: currentColor; /* Inherits text color */
    display: inline-block; /* Changed from block */
    border-radius: 50%;
    opacity: 0.4;
    animation: typing 1s infinite ease-in-out;
}
.typing-indicator span:nth-child(1) {
    animation-delay: 0s;
}
.typing-indicator span:nth-child(2) {
    animation-delay: 0.1s;
}
.typing-indicator span:nth-child(3) {
    animation-delay: 0.2s;
}

@keyframes typing {
    0%,
    80%,
    100% {
        transform: scale(0.5);
        opacity: 0.4;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Ensure prose styles within chat messages don't add excessive margins */
.prose :first-child {
    margin-top: 0;
}
.prose :last-child {
    margin-bottom: 0;
}
</style>
