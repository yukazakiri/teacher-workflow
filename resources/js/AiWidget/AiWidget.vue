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
}

const props = defineProps({
    wire: {
        type: Object,
        default: () => ({}),
    },
    mingleData: {},
    user: {
        type: Object,
        default: () => ({}),
    },
});

const { wire, mingleData, user } = props;

// State management
const sidebarOpen = ref(false);
const conversation = ref(null);
const conversations = ref([]);
const isProcessing = ref(false);
const isStreaming = ref(false);
const message = ref("");
const selectedModel = ref("gemini-2.0-flash");
const selectedStyle = ref("balanced");
const eventSource = ref(null);
const availableModels = ref([]);
const availableStyles = ref({});

const recentChats = ref([]);
const isNewConversation = ref(true);
const currentConversationId = ref(null);
const messages = ref([]);
const userMessage = ref("");
const loading = ref(false);
const loadingConversations = ref(true);
const loadingModels = ref(true);
const loadingMessages = ref(false);
const error = ref(null);
const showDeleteConfirm = ref(false);
const conversationIdToDelete = ref(null);
const streamedResponse = ref("");

// Computed properties
const timeOfDay = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return "morning";
    if (hour < 18) return "afternoon";
    return "evening";
});

// Methods
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const fetchRecentChats = async () => {
    try {
        loadingConversations.value = true;
        console.log("Fetching recent conversations");
        const response = await axios.get("/ai/conversations");
        conversations.value = response.data;
        recentChats.value = response.data;
        console.log("Received conversations:", response.data.length);
    } catch (err) {
        console.error("Failed to fetch recent chats:", err);
        error.value =
            "Failed to load recent conversations. Please try again later.";
    } finally {
        loadingConversations.value = false;
    }
};

const loadConversation = async (id) => {
    loadingMessages.value = true;
    error.value = null;
    currentConversationId.value = id;
    isNewConversation.value = false;

    try {
        console.log(`Loading conversation ${id}`);
        const response = await axios.get(`/ai/conversations/${id}/messages`);
        conversation.value = response.data.conversation;
        console.log("Loaded conversation:", response.data);

        // Set selected model and style based on the loaded conversation
        selectedModel.value =
            conversation.value.model ||
            (availableModels.value.length > 0
                ? availableModels.value[0]
                : null);
        selectedStyle.value = conversation.value.style || "balanced";

        // Load messages
        messages.value = conversation.value.messages.map((message) => ({
            role: message.role,
            content: message.content,
            timestamp: new Date(message.created_at),
        }));

        console.log(`Loaded ${messages.value.length} messages for conversation ${id}`);
        scrollToBottom();
    } catch (err) {
        console.error(`Failed to load conversation ${id}:`, err);
        error.value = "Failed to load conversation. Please try again later.";

        // Reset to new conversation
        startNewChat();
    } finally {
        loadingMessages.value = false;
    }
};

const startNewChat = () => {
    currentConversationId.value = null;
    isNewConversation.value = true;
    messages.value = [];
    userMessage.value = "";
    error.value = null;

    // Default model and style
    if (availableModels.value.length > 0 && !selectedModel.value) {
        selectedModel.value = availableModels.value[0].id;
    }

    if (!selectedStyle.value) {
        selectedStyle.value = "normal";
    }
};

const deleteConversation = async (id) => {
    try {
        await axios.delete(`/conversations/${id}`);
        conversations.value = conversations.value.filter((c) => c.id !== id);
        recentChats.value = recentChats.value.filter((chat) => chat.id !== id);
        if (conversation.value && conversation.value.id === id) {
            startNewChat();
        }
        showDeleteConfirm.value = false;
        conversationIdToDelete.value = null;
    } catch (error) {
        console.error(`Error deleting conversation ${id}:`, error);
        error.value = "Failed to delete conversation. Please try again later.";
    }
};

const closeEventSource = () => {
    if (eventSource.value) {
        eventSource.value.close();
        eventSource.value = null;
    }
};

const sendMessage = async () => {
    if (!message.value.trim() || loading.value) return;

    loading.value = true;
    isProcessing.value = true;
    isStreaming.value = true;
    error.value = null;

    // Extract visible content (what the user actually sees) for display
    const visibleContent = message.value.split('\n\nresource_uuid:')[0];

    // Add user message to the conversation
    const userMsg = {
        role: "user",
        content: visibleContent, // Use only the visible part for display
        timestamp: new Date(),
    };

    // Add user message to both arrays
    messages.value.push(userMsg);
    
    // Initialize conversation object if it doesn't exist yet
    if (!conversation.value) {
        conversation.value = { messages: [] };
    }
    
    // Ensure conversation.messages exists
    if (!conversation.value.messages) {
        conversation.value.messages = [];
    }
    
    // Add to conversation messages array for ChatMessages component
    conversation.value.messages.push({
        id: Date.now(),
        role: "user",
        content: userMsg.content, // Display only the visible message content
        created_at: new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        }),
    });
    
    const sentMessage = message.value; // Keep full message with resource IDs for API
    message.value = ""; // Clear input field

    scrollToBottom();

    // Add temporary AI message for streaming
    const aiMessageIndex = messages.value.length;
    messages.value.push({
        role: "assistant",
        content: "",
        timestamp: new Date(),
    });
    
    // Add to conversation messages array for ChatMessages component
    conversation.value.messages.push({
        id: Date.now() + 1,
        role: "assistant",
        content: "",
        created_at: new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        }),
    });

    try {
        // Create data for the request
        const data = {
            message: sentMessage, // Send the full message with resource IDs
            model: selectedModel.value,
            style: selectedStyle.value,
            conversation_id: currentConversationId.value,
        };

        console.log("Sending AI request:", {
            endpoint: "/ai/stream",
            message: visibleContent, // Log only visible part for privacy/debugging
            hasResources: sentMessage.includes('resource_uuid:'),
            model: selectedModel.value,
            style: selectedStyle.value,
            conversationId: currentConversationId.value
        });

        // Use fetch with proper streaming support
        const response = await fetch("/ai/stream", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "text/event-stream"
            },
            credentials: "same-origin",
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            console.error("Server response error:", {
                status: response.status,
                statusText: response.statusText
            });
            throw new Error(
                `Server responded with ${response.status}: ${response.statusText}`,
            );
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let responseText = "";

        console.log("Stream started, processing chunks...");

        // Process the stream
        while (true) {
            const { done, value } = await reader.read();

            if (done) {
                console.log("Stream completed.");
                break;
            }

            // Decode and handle the chunk
            const chunk = decoder.decode(value, { stream: true });
            responseText += chunk;

            console.log("Received chunk:", chunk.substring(0, 50) + (chunk.length > 50 ? "..." : ""));

            // Update the AI message with the current accumulated text in both arrays
            if (messages.value[aiMessageIndex]) {
                messages.value[aiMessageIndex].content = responseText;
            }
            
            // Update in conversation messages array for ChatMessages component
            if (conversation.value && conversation.value.messages && conversation.value.messages.length >= 2) {
                const lastMessageIndex = conversation.value.messages.length - 1;
                conversation.value.messages[lastMessageIndex].content = responseText;
            }
            
            scrollToBottom();
        }

        // Check if we received a conversation ID from the response
        try {
            console.log("Checking for conversation data in response");
            const jsonMatch = responseText.match(
                /<!-- CONVERSATION_DATA:(.+?)-->/,
            );
            if (jsonMatch && jsonMatch[1]) {
                const conversationData = JSON.parse(jsonMatch[1]);
                console.log("Found conversation data:", conversationData);

                if (conversationData.conversation_id) {
                    currentConversationId.value = conversationData.conversation_id;
                    isNewConversation.value = false;

                    // Remove the JSON comment from the message content in both arrays
                    if (messages.value[aiMessageIndex]) {
                        messages.value[aiMessageIndex].content = messages.value[
                            aiMessageIndex
                        ].content.replace(/<!-- CONVERSATION_DATA:.+?-->/, "");
                    }
                    
                    // Also clean the content in the conversation messages array
                    if (conversation.value && conversation.value.messages && conversation.value.messages.length > 0) {
                        const lastMessageIndex = conversation.value.messages.length - 1;
                        conversation.value.messages[lastMessageIndex].content = 
                            conversation.value.messages[lastMessageIndex].content.replace(/<!-- CONVERSATION_DATA:.+?-->/, "");
                    }

                    // Refresh recent chats
                    fetchRecentChats();
                }
            } else {
                console.log("No conversation data found in response");
            }
        } catch (parseErr) {
            console.error("Failed to parse conversation data:", parseErr);
        }
    } catch (err) {
        console.error("Error sending message:", err);
        error.value = "Failed to send message. Please try again later.";

        // Remove the empty AI message
        if (
            messages.value.length === aiMessageIndex + 1 &&
            !messages.value[aiMessageIndex].content
        ) {
            messages.value.pop();
        } else if (messages.value[aiMessageIndex]) {
            messages.value[aiMessageIndex].content +=
                "\n\n[Error: Message transmission interrupted]";
        }
    } finally {
        loading.value = false;
        isProcessing.value = false;
        isStreaming.value = false;
        scrollToBottom();
    }
};

const setPrompt = (promptText) => {
    message.value = promptText;
};

const changeModel = async (model) => {
    selectedModel.value = model;

    // Only update the model on the server if we have an existing conversation
    if (conversation.value && conversation.value.id) {
        try {
            await axios.put(`/conversations/${conversation.value.id}/model`, {
                model: selectedModel.value,
            });
        } catch (error) {
            console.error("Failed to update model preference:", error);
        }
    }
};

const changeStyle = async (style) => {
    selectedStyle.value = style;

    // Only update the style on the server if we have an existing conversation
    if (conversation.value && conversation.value.id) {
        try {
            await axios.put(`/conversations/${conversation.value.id}/style`, {
                style: selectedStyle.value,
            });
        } catch (error) {
            console.error("Failed to update style preference:", error);
        }
    }
};

const regenerateLastMessage = async () => {
    if (
        !conversation.value ||
        !conversation.value.id ||
        !conversation.value.messages ||
        conversation.value.messages.length < 2
    )
        return;

    // Find the last pair of messages (user and assistant)
    const messages = conversation.value.messages;
    let lastAssistantIndex = -1;
    let lastUserIndex = -1;

    for (let i = messages.length - 1; i >= 0; i--) {
        if (messages[i].role === "assistant" && lastAssistantIndex === -1) {
            lastAssistantIndex = i;
        } else if (
            messages[i].role === "user" &&
            lastAssistantIndex !== -1 &&
            lastUserIndex === -1
        ) {
            lastUserIndex = i;
            break;
        }
    }

    if (lastAssistantIndex >= 0 && lastUserIndex >= 0) {
        isProcessing.value = true;
        isStreaming.value = true;

        // Remove the last assistant message
        conversation.value.messages.splice(lastAssistantIndex, 1);

        // Prepare history for regeneration
        const history = conversation.value.messages
            .slice(0, lastUserIndex + 1)
            .map((m) => ({
                role: m.role,
                content: m.content,
            }));

        console.log("Regenerating response with history:", history);

        // Close any existing event source
        closeEventSource();

        try {
            // Create data for the request
            const data = {
                conversation_id: conversation.value.id,
                model: selectedModel.value,
                style: selectedStyle.value,
                history: history,
                message: history[history.length - 1].content, // Send the last user message as the main message
            };

            console.log("Sending regeneration request:", {
                endpoint: "/ai/stream",
                conversationId: conversation.value.id,
                model: selectedModel.value,
                style: selectedStyle.value,
            });

            // Use fetch with proper streaming support - much more reliable than EventSource
            const response = await fetch("/ai/stream", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "text/event-stream"
                },
                credentials: "same-origin",
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                console.error("Server response error:", {
                    status: response.status,
                    statusText: response.statusText
                });
                throw new Error(
                    `Server responded with ${response.status}: ${response.statusText}`,
                );
            }

            // Add placeholder for the assistant's response
            conversation.value.messages.push({
                id: Date.now(),
                role: "assistant",
                content: "",
                created_at: new Date().toLocaleTimeString([], {
                    hour: "numeric",
                    minute: "2-digit",
                }),
            });

            const lastMessageIndex = conversation.value.messages.length - 1;
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let responseText = "";

            console.log("Regeneration stream started, processing chunks...");

            // Process the stream
            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    console.log("Regeneration stream completed.");
                    break;
                }

                // Decode and handle the chunk
                const chunk = decoder.decode(value, { stream: true });
                responseText += chunk;

                console.log("Received chunk:", chunk.substring(0, 50) + (chunk.length > 50 ? "..." : ""));

                // Update the AI message with current accumulated text
                if (conversation.value && conversation.value.messages && lastMessageIndex < conversation.value.messages.length) {
                    conversation.value.messages[lastMessageIndex].content = responseText;
                }
                
                scrollToBottom();
            }

            // Check if we received a conversation ID from the response
            try {
                const jsonMatch = responseText.match(
                    /<!-- CONVERSATION_DATA:(.+?)-->/,
                );
                if (jsonMatch && jsonMatch[1]) {
                    const conversationData = JSON.parse(jsonMatch[1]);
                    console.log("Found conversation data in regeneration:", conversationData);

                    // Clean up the conversation data comment
                    if (conversation.value && conversation.value.messages && lastMessageIndex < conversation.value.messages.length) {
                        conversation.value.messages[lastMessageIndex].content = 
                            conversation.value.messages[lastMessageIndex].content.replace(/<!-- CONVERSATION_DATA:.+?-->/, "");
                    }
                }
            } catch (parseErr) {
                console.error("Failed to parse conversation data:", parseErr);
            }
        } catch (error) {
            console.error("Error regenerating message:", error);
            conversation.value.messages.push({
                id: Date.now(),
                role: "assistant",
                content: `Error regenerating response: ${error.message}`,
                created_at: new Date().toLocaleTimeString([], {
                    hour: "numeric",
                    minute: "2-digit",
                }),
            });
        } finally {
            isStreaming.value = false;
            isProcessing.value = false;
            scrollToBottom();
        }
    }
};

const fetchAvailableModels = async () => {
    try {
        loadingModels.value = true;
        console.log("Fetching available AI models");
        const response = await axios.get("/ai/models");
        availableModels.value = response.data;
        console.log("Received models:", response.data);
        
        // Set default model if none selected and we have models
        if (!selectedModel.value && response.data.length > 0) {
            selectedModel.value = response.data[0];
        }
    } catch (err) {
        console.error("Failed to fetch available models:", err);
        error.value =
            "Failed to load available AI models. Please try again later.";
    } finally {
        loadingModels.value = false;
    }
};

const fetchAvailableStyles = async () => {
    try {
        console.log("Fetching available AI styles");
        const response = await axios.get("/ai/styles");
        availableStyles.value = response.data;
        console.log("Received styles:", response.data);
        
        // Set default style if none selected and we have styles
        if (!selectedStyle.value && Object.keys(response.data).length > 0) {
            selectedStyle.value = Object.keys(response.data)[0];
        }
    } catch (err) {
        console.error("Failed to fetch available styles:", err);
        // Fallback to default styles if API fails
        availableStyles.value = {
            "default": "Default",
            "creative": "Creative", 
            "precise": "Precise",
            "balanced": "Balanced"
        };
    }
};

// Debugging helper - call this from browser console with aiWidgetDebug()
window.aiWidgetDebug = () => {
    console.group('AiWidget Debug Information');
    console.log('Current state:', {
        conversation: conversation.value,
        currentConversationId: currentConversationId.value,
        selectedModel: selectedModel.value,
        selectedStyle: selectedStyle.value,
        isProcessing: isProcessing.value,
        isStreaming: isStreaming.value,
        isNewConversation: isNewConversation.value,
        availableModels: availableModels.value
    });
    
    // Test endpoint connectivity
    fetch('/ai/test-student-tool')
        .then(response => response.json())
        .then(data => {
            console.log('StudentTool test result:', data);
        })
        .catch(error => {
            console.error('StudentTool test failed:', error);
        });
        
    fetch('/ai/test-prism?prompt=Who%20is%20Emma%20in%20my%20class')
        .then(response => response.json())
        .then(data => {
            console.log('Prism test result:', data);
        })
        .catch(error => {
            console.error('Prism test failed:', error);
        });
    
    console.groupEnd();
    
    return "Debug info logged to console. Check network requests for test results.";
};

// Component lifecycle hooks
onMounted(() => {
    fetchAvailableModels();
    fetchRecentChats();
    fetchAvailableStyles();
});

// Watch for changes to model/style and update preferences
watch(selectedModel, async (newModel, oldModel) => {
    if (
        newModel !== oldModel &&
        currentConversationId.value &&
        !isNewConversation.value
    ) {
        await updateModelPreference();
    }
});

watch(selectedStyle, async (newStyle, oldStyle) => {
    if (
        newStyle !== oldStyle &&
        currentConversationId.value &&
        !isNewConversation.value
    ) {
        await updateStylePreference();
    }
});

/**
 * Update model preference for current conversation
 */
const updateModelPreference = async () => {
    if (!currentConversationId.value || isNewConversation.value) return;

    try {
        await axios.put(`/conversations/${currentConversationId.value}/model`, {
            model: selectedModel.value,
        });
    } catch (err) {
        console.error("Failed to update model preference:", err);
        // Don't show error to user
    }
};

/**
 * Update style preference for current conversation
 */
const updateStylePreference = async () => {
    if (!currentConversationId.value || isNewConversation.value) return;

    try {
        await axios.put(`/conversations/${currentConversationId.value}/style`, {
            style: selectedStyle.value,
        });
    } catch (err) {
        console.error("Failed to update style preference:", err);
        // Don't show error to user
    }
};

const scrollToBottom = () => {
    setTimeout(() => {
        document.getElementById("chat-messages-container")?.scrollTo({
            top: document.getElementById("chat-messages-container")
                ?.scrollHeight,
            behavior: "smooth",
        });
    }, 100);
};

// Confirm deletion
const confirmDelete = (conversationId) => {
    conversationIdToDelete.value = conversationId;
    showDeleteConfirm.value = true;
};

// Cancel deletion
const cancelDelete = () => {
    showDeleteConfirm.value = false;
    conversationIdToDelete.value = null;
};
</script>

<template>
    <div class="min-h-screen bg-background" x-data="{ sidebarOpen: false }">
        <!-- Right Sidebar (Chat History) -->
        <ChatSidebar
            :open="sidebarOpen"
            @close="sidebarOpen = false"
            :chats="recentChats"
            @load-conversation="loadConversation"
            @delete-conversation="deleteConversation"
            @new-conversation="startNewChat"
        />

        <!-- Overlay for mobile sidebar -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Main Content Area -->
        <div class="flex flex-col min-h-screen">
            <!-- Header -->
            <ChatHeader
                :conversation="conversation"
                @toggle-sidebar="toggleSidebar"
                @new-conversation="startNewChat"
                @regenerate-last-message="regenerateLastMessage"
            />

            <!-- Main Content -->
            <main class="flex-1 flex flex-col mx-auto max-w-5xl w-full">
                <template v-if="!conversation">
                    <!-- Initial State: Welcome, Input, Quick Actions, Recent Chats Grid -->
                    <div class="flex-1 flex flex-col items-center w-full">
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
                                How can I help you today?
                            </p>

                            <!-- Suggestion Buttons -->
                            <ChatSuggestions @set-prompt="setPrompt" />
                        </div>

                        <!-- Chat Input Form (Initial State) -->
                        <ChatInput
                            v-model="message"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                            :selected-model="selectedModel"
                            :selected-style="selectedStyle"
                            :available-models="availableModels"
                            :available-styles="availableStyles"
                            @send-message="sendMessage"
                            @change-model="changeModel"
                            @change-style="changeStyle"
                            @new-conversation="startNewChat"
                        />

                        <!-- Recent Chats Grid -->
                        <RecentChats
                            :chats="recentChats"
                            @load-conversation="loadConversation"
                            @delete-conversation="deleteConversation"
                        />
                    </div>
                </template>

                <template v-else>
                    <!-- Active Conversation State: Messages + Fixed Input -->
                    <div class="flex-1 flex flex-col min-h-0 pb-32">
                        <!-- Added padding-bottom for fixed input -->
                        <!-- Messages Container -->
                        <ChatMessages
                            :conversation="conversation"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                        />
                    </div>

                    <!-- Fixed Chat Input Form (Active Conversation) -->
                    <div
                        class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700"
                    >
                        <ChatInput
                            v-model="message"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                            :selected-model="selectedModel"
                            :selected-style="selectedStyle"
                            :available-models="availableModels"
                            :available-styles="availableStyles"
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
.typing-indicator {
    display: inline-flex;
    align-items: center;
}
.typing-indicator span {
    height: 5px;
    width: 5px;
    margin: 0 1px;
    background-color: currentColor;
    display: block;
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
/* Ensure prose styles don't add excessive margins */
.prose :first-child {
    margin-top: 0;
}
.prose :last-child {
    margin-bottom: 0;
}
</style>
