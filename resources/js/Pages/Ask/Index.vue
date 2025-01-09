<template>
    <AppLayout>
        <div class="flex h-full overflow-hidden">
            <Conversations
                class="border-r border-gray-700 w-80"
                :conversations="conversations"
                :selectedId="currentConversation?.id"
                @select="selectConversation"
                @delete="deleteConversation"
                @new-conversation="setNewConversation"
            />
            <Chat
                ref="chatComponent"
                class="h-full grow"
                :models="models"
                :conversation="currentConversation"
                :flash="flash"
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

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    flash: {
        type: Object,
    },
    initialConversations: {
        type: Array,
        required: true,
    },
});

const conversations = ref(
    props.initialConversations ? props.initialConversations : []
);
const currentConversation = ref(null);
const chatComponent = ref(null);
const currentModel = ref(null);
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
    () => props.initialConversations,
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
    } catch (error) {
        console.error("Error fetching conversation:", error);
    }
};

const deleteConversation = async (id) => {
    try {
        await axios.delete(`/conversations/${id}`);
        conversations.value = conversations.value.filter(
            (conv) => conv.id !== id
        );
        if (currentConversation.value?.id === id) {
            currentConversation.value = null;
            // Clear the chat when deleting the current conversation
            chatComponent.value?.clearChat();
        }
    } catch (error) {
        console.error("Error deleting conversation:", error);
    }
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

const setNewConversation = async () => {
    if (!currentModel.value) {
        console.error("No model selected");
        return;
    }

    newConversationForm.model = currentModel.value;

    try {
        newConversationForm.post("/conversations", {
            onSuccess: (response) => {
                console.log(response);
            },
        });
    } catch (error) {
        console.error("Error creating conversation:", error);
    }
};
</script>
