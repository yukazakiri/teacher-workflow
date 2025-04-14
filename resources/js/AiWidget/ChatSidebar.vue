<script setup>
import { ref, computed } from 'vue';
import { PlusIcon, TrashIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'; // Or your preferred icons

const props = defineProps({
  allChats: {
    type: Array,
    required: true,
    default: () => []
  },
  currentConversationId: {
    type: [Number, String, null], // Allow int or UUID string
    default: null
  },
  userName: {
    type: String,
    default: 'User'
  }
});

const emit = defineEmits(['new-chat', 'load-chat', 'delete-chat']);

const searchQuery = ref('');

const filteredChats = computed(() => {
  if (!searchQuery.value) {
    return props.allChats;
  }
  const lowerQuery = searchQuery.value.toLowerCase();
  return props.allChats.filter(chat =>
    chat.title.toLowerCase().includes(lowerQuery)
  );
});

function startNewChat() {
  emit('new-chat');
}

function loadChat(chatId) {
    // Don't reload if it's already the current chat
    if (chatId !== props.currentConversationId) {
        emit('load-chat', chatId);
    }
}

function deleteChat(event, chatId) {
  event.stopPropagation(); // Prevent loadChat from firing when clicking delete
  if (confirm('Are you sure you want to delete this chat?')) {
    emit('delete-chat', chatId);
  }
}
</script>

<template>
  <div class="flex h-full min-h-0 flex-col bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 w-64">
    <!-- Header / New Chat Button -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Chats</h2>
      <button
        @click="startNewChat"
        class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
        aria-label="New Chat"
      >
        <PlusIcon class="h-6 w-6" />
      </button>
    </div>

    <!-- Search Input (Optional) -->
    <div class="p-2 border-b border-gray-200 dark:border-gray-700">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="Search chats..."
        class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
      />
    </div>

    <!-- Chat List -->
    <div class="flex-1 overflow-y-auto">
      <ul v-if="filteredChats.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
        <li
          v-for="chat in filteredChats"
          :key="chat.id"
          @click="loadChat(chat.id)"
          :class="[
            'p-3 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer group flex justify-between items-center',
            { 'bg-indigo-100 dark:bg-indigo-900': chat.id === currentConversationId }
          ]"
        >
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ chat.title }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ chat.model }} - {{ chat.last_activity }}
            </p>
          </div>
          <button
            @click="deleteChat($event, chat.id)"
            class="ml-2 p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-500 opacity-0 group-hover:opacity-100 focus:opacity-100 focus:outline-none rounded"
            aria-label="Delete Chat"
          >
            <TrashIcon class="h-4 w-4" />
          </button>
        </li>
      </ul>
      <div v-else class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
        <ChatBubbleLeftRightIcon class="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-2" />
        No chats found. <br> Start a new conversation!
      </div>
    </div>

    <!-- Footer (Optional: User Info/Settings) -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300">
      Logged in as {{ userName }}
      <!-- Add settings/logout links if needed -->
    </div>
  </div>
</template> 