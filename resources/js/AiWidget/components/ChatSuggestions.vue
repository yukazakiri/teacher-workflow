<script setup>
import { defineEmits } from 'vue';

const emit = defineEmits(['set-prompt']);

const suggestions = [
    {
        title: 'Create a lesson plan',
        description: 'For a high school English class',
        prompt: 'Help me create a lesson plan for a high school English class on Shakespeare.'
    },
    {
        title: 'Generate discussion questions',
        description: 'About climate change for middle school',
        prompt: 'Generate 5 discussion questions about climate change for a middle school science class.'
    },
    {
        title: 'Draft an email template',
        description: 'For parent-teacher conferences',
        prompt: 'Help me draft a parent-teacher conference email template.'
    },
    {
        title: 'Suggest virtual activities',
        description: 'To increase student engagement',
        prompt: 'Suggest activities for a virtual classroom to increase student engagement.'
    }
];

const setPrompt = (prompt) => {
    emit('set-prompt', prompt);
};
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left max-w-lg mx-auto mb-8">
        <button
            v-for="suggestion in suggestions"
            :key="suggestion.title"
            @click="setPrompt(suggestion.prompt)"
            class="rounded-lg border border-gray-300 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-left transition-colors shadow-sm"
        >
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ suggestion.title }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ suggestion.description }}</div>
        </button>
    </div>
</template>
