<template>
    <AppLayout>
        <div class="flex h-full overflow-hidden">
            <Conversations
                class="w-1/6 border-r border-gray-700"
                :conversations="conversations"
            />
            <Chat
                class="h-full grow"
                :models="models"
                :selectedModel="selectedModel"
                :conversation="currentConversation"
                :flash="flash"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Chat from "@pages/Ask/Partials/Chat.vue";
import Conversations from "@pages/Ask/Partials/Conversations.vue";

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    selectedModel: {
        type: String,
        required: true,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
    conversations: {
        type: Array,
        required: true,
    },
    currentConversation: {
        type: Object,
        required: false,
        default: null,
    },
});
</script>
