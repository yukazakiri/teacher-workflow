<script setup>
import { ref, computed } from 'vue';
import ChatSidebar from './components/ChatSidebar.vue';
import ChatHeader from './components/ChatHeader.vue';
import ChatMessages from './components/ChatMessages.vue';
import ChatInput from './components/ChatInput.vue';
import ChatSuggestions from './components/ChatSuggestions.vue';
import RecentChats from './components/RecentChats.vue';

const props = defineProps({
    wire: {},
    mingleData: {}
});

const { wire, mingleData } = props;

// State management
const sidebarOpen = ref(false);
const conversation = ref(null);
const isProcessing = ref(false);
const isStreaming = ref(false);
const message = ref('');
const selectedModel = ref('gpt-4');
const selectedStyle = ref('balanced');
// console.log('This is test');
// Sample data (replace with actual data from your backend)
const availableModels = ref(['gpt-3.5-turbo', 'gpt-4', 'claude-3-opus']);
const availableStyles = ref({
    balanced: 'Balanced',
    creative: 'Creative',
    precise: 'Precise',
    professional: 'Professional'
});

const recentChats = ref([
    {
        id: 1,
        title: 'Lesson Plan Creation',
        model: 'gpt-4',
        last_activity: '2 hours ago'
    },
    {
        id: 2,
        title: 'Student Engagement Ideas',
        model: 'claude-3-opus',
        last_activity: 'Yesterday'
    },
    {
        id: 3,
        title: 'Grading Rubric Design',
        model: 'gpt-3.5-turbo',
        last_activity: '3 days ago'
    }
]);

// Methods
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const loadConversation = (id) => {
    // This would be replaced with actual API call
    console.log(`Loading conversation ${id}`);
    conversation.value = {
        id: id,
        title: recentChats.value.find(chat => chat.id === id)?.title || 'New Chat',
        model: selectedModel.value,
        messages: [
            { id: 1, role: 'user', content: 'Can you help me create a lesson plan?', created_at: '10:30 AM' },
            { id: 2, role: 'assistant', content: 'I\'d be happy to help you create a lesson plan! Could you tell me what subject and grade level you\'re teaching?', created_at: '10:31 AM' }
        ]
    };
    sidebarOpen.value = false;
};

const newConversation = () => {
    conversation.value = null;
};

const sendMessage = () => {
    if (!message.value.trim() || isProcessing.value || isStreaming.value) return;

    // If no active conversation, create one
    if (!conversation.value) {
        conversation.value = {
            id: Date.now(),
            title: 'New Chat',
            model: selectedModel.value,
            messages: []
        };
    }

    // Add user message
    const userMessage = {
        id: Date.now(),
        role: 'user',
        content: message.value,
        created_at: new Date().toLocaleTimeString([], {hour: 'numeric', minute:'2-digit'})
    };

    conversation.value.messages.push(userMessage);

    // Clear input
    message.value = '';

    // Simulate AI response
    isProcessing.value = true;
    setTimeout(() => {
        isProcessing.value = false;
        isStreaming.value = true;

        // Add AI response
        const aiMessage = {
            id: Date.now() + 1,
            role: 'assistant',
            content: 'I\'m simulating a response to your message. In a real implementation, this would come from your AI provider.',
            created_at: new Date().toLocaleTimeString([], {hour: 'numeric', minute:'2-digit'})
        };

        conversation.value.messages.push(aiMessage);

        setTimeout(() => {
            isStreaming.value = false;
        }, 1000);
    }, 1500);
};

const setPrompt = (promptText) => {
    message.value = promptText;
};

const changeModel = (model) => {
    selectedModel.value = model;
};

const changeStyle = (style) => {
    selectedStyle.value = style;
};

const deleteConversation = (id) => {
    recentChats.value = recentChats.value.filter(chat => chat.id !== id);
    if (conversation.value && conversation.value.id === id) {
        conversation.value = null;
    }
};

const regenerateLastMessage = () => {
    if (!conversation.value || !conversation.value.messages.length) return;

    // Find the last assistant message
    const messages = conversation.value.messages;
    let lastAssistantIndex = -1;

    for (let i = messages.length - 1; i >= 0; i--) {
        if (messages[i].role === 'assistant') {
            lastAssistantIndex = i;
            break;
        }
    }

    if (lastAssistantIndex >= 0) {
        // Simulate regeneration
        isProcessing.value = true;
        setTimeout(() => {
            isProcessing.value = false;
            isStreaming.value = true;

            // Update the message
            conversation.value.messages[lastAssistantIndex].content =
                'This is a regenerated response. In a real implementation, this would be a new response from your AI provider.';

            setTimeout(() => {
                isStreaming.value = false;
            }, 1000);
        }, 1500);
    }
};

// Computed properties
const timeOfDay = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'morning';
    if (hour < 18) return 'afternoon';
    return 'evening';
});
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
            @new-conversation="newConversation"
        />

        <!-- Overlay for mobile sidebar -->
        <div v-show="sidebarOpen"
             class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
             @click="sidebarOpen = false"></div>

        <!-- Main Content Area -->
        <div class="flex flex-col min-h-screen">
            <!-- Header -->
            <ChatHeader
                :conversation="conversation"
                @toggle-sidebar="toggleSidebar"
                @new-conversation="newConversation"
                @regenerate-last-message="regenerateLastMessage"
            />

            <!-- Main Content -->
            <main class="flex-1 flex flex-col mx-auto max-w-5xl w-full">
                <template v-if="!conversation">
                    <!-- Initial State: Welcome, Input, Quick Actions, Recent Chats Grid -->
                    <div class="flex-1 flex flex-col items-center w-full">
                        <!-- Welcome Message & Suggestions -->
                        <div class="w-full max-w-3xl mb-8 text-center">
                            <div class="text-primary-600 dark:text-primary-400 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 256 256" class="mx-auto">
                                    <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80a8,8,0,0,1,8-8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,176Zm32-48c0,17.65-16.42,32-36.57,32H116.57C96.42,160,80,145.65,80,128s16.42-32,36.57-32h22.86C159.58,96,176,110.35,176,128Z"></path>
                                </svg>
                            </div>
                            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                                Good {{ timeOfDay }}, Teacher!
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">How can I help you today?</p>

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
                            @new-conversation="newConversation"
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
                    <div class="flex-1 flex flex-col min-h-0 pb-32"> <!-- Added padding-bottom for fixed input -->
                        <!-- Messages Container -->
                        <ChatMessages
                            :conversation="conversation"
                            :is-processing="isProcessing"
                            :is-streaming="isStreaming"
                        />
                    </div>

                    <!-- Fixed Chat Input Form (Active Conversation) -->
                    <div class="sticky bottom-0 z-10 bg-gray-50 dark:bg-gray-900 pt-4 pb-2 border-t border-gray-200 dark:border-gray-700">
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
</style>
