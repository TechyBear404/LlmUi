<template>
    <AppLayout>
        <div class="flex h-full overflow-hidden">
            <Conversations
                class="border-r border-gray-700 w-80"
                :conversations="conversations"
                :selectedId="currentConversation?.id"
                :loading="false"
                :defaultModel="DEFAULT_MODEL"
                @select="selectConversation"
                @delete="deleteConversation"
                @conversation-created="handleNewConversation"
            />
            <Chat
                ref="chatComponent"
                class="h-full grow"
                :models="models"
                :conversation="currentConversation"
                :flash="flash"
                :defaultModel="DEFAULT_MODEL"
                @selected-model="setCurrentModel"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Chat from "@/Pages/Ask/Partials/Chat.vue";
import Conversations from "@/Pages/Ask/Partials/Conversations.vue";
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

const DEFAULT_MODEL = {
    id: "meta-llama/llama-3.2-11b-vision-instruct:free",
    name: "Meta: Llama 3.2 11B Vision Instruct (free)",
};

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
    conversations: {
        type: Array,
        default: () => [], // Add default empty array
    },
});

const conversations = ref(props.conversations || []);
const currentConversation = ref(null);
const chatComponent = ref(null);
const currentModel = ref(DEFAULT_MODEL);
console.log(conversations.value);

// Add onMounted to select last conversation
onMounted(() => {
    if (conversations.value.length > 0) {
        selectConversation(conversations.value[0]);
    }
});

// Update the watch handlers
watch(
    () => props.flash?.conversation,
    (newConversation) => {
        if (newConversation) {
            // conversations.value.unshift(newConversation);
            currentConversation.value = newConversation;
            // chatComponent.value?.clearChat();
        }
    }
);

// Add a watch for initialConversations
watch(
    () => props.conversations,
    (newConversations) => {
        if (newConversations) {
            conversations.value = newConversations;
        }
    },
    { immediate: true }
);

// watch(
//     () => props.flash?.message,
//     (newMessage) => {
//         if (newMessage) {
//             chatComponent.value?.addMessage(newMessage);
//         }
//     }
// );

const setCurrentModel = (model) => {
    currentModel.value = model;
};

const selectConversation = async (conversation) => {
    try {
        const response = await axios.get(`/conversations/${conversation.id}`);
        currentConversation.value = response.data;
        currentModel.value =
            props.models.find((m) => m.id === response.data.model_id) ||
            DEFAULT_MODEL;
    } catch (error) {
        console.error("Error fetching conversation:", error);
    }
};

const deleteForm = useForm({});

const deleteConversation = async (id) => {
    deleteForm.delete(route("conversations.destroy", { conversation: id }), {
        onSuccess: () => {
            conversations.value = conversations.value.filter(
                (conv) => conv.id !== id
            );
            if (currentConversation.value?.id === id) {
                currentConversation.value = null;
                chatComponent.value?.clearChat();
            }
        },
    });
};

// const handleMessageSent = (newConversation) => {
//     console.log(newConversation);
//     if (!currentConversation.value) {
//         conversations.value.unshift(newConversation);
//         currentConversation.value = newConversation;
//     } else {
//         const index = conversations.value.findIndex(
//             (c) => c.id === newConversation.id
//         );
//         if (index !== -1) {
//             conversations.value[index] = newConversation;
//             currentConversation.value = newConversation;
//         }
//     }
// };

const newConversationForm = useForm({
    model: null,
});

const handleNewConversation = async (conversation) => {
    try {
        // Get the full conversation with messages
        const response = await axios.get(`/conversations/${conversation.id}`);
        conversations.value.unshift(conversation);
        currentConversation.value = response.data;
        currentModel.value =
            props.models.find((m) => m.id === response.data.model_id) ||
            DEFAULT_MODEL;
    } catch (error) {
        console.error("Error loading new conversation:", error);
    }
};
</script>
