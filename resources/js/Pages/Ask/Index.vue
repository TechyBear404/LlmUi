<template>
    <AppLayout>
        <div class="flex h-full overflow-hidden">
            <Conversations
                class="border-r border-gray-700 w-80"
                :conversations="conversations"
                :selectedId="currentConversation?.id"
                :currentModel="currentModel"
                @select="selectConversation"
                @delete="deleteConversation"
            />
            <div class="flex flex-col flex-grow w-full">
                <ModelsSelector
                    class="flex-none"
                    :models="models"
                    :currentModel="currentModel"
                    @selected-model="handleModelChange"
                />
                <Chat
                    ref="chatComponent"
                    class="h-full grow"
                    :currentModel="currentModel"
                    :conversation="currentConversation"
                    :flash="flash"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Chat from "@/Pages/Ask/Partials/Chat.vue";
import Conversations from "@/Pages/Ask/Partials/Conversations.vue";
import ModelsSelector from "./Partials/ModelsSelector.vue";
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
    conversations: {
        type: Array,
        default: () => [],
    },
});

const conversations = ref(props.conversations);
const currentConversation = ref(null);
const chatComponent = ref(null);

const defaultModel = {
    id: "meta-llama/llama-3.2-11b-vision-instruct:free",
    name: "Meta: Llama 3.2 11B Vision Instruct (free)",
};
const currentModel = ref(currentConversation.value?.model || defaultModel);
// console.log(currentModel.value);

// Add onMounted to select last conversation
onMounted(() => {
    if (conversations.value.length > 0) {
        selectConversation(conversations.value[0]);
    }
});

// Watch handlers
watch(
    () => props.flash?.conversation,
    (newConversation) => {
        if (newConversation) {
            currentConversation.value = newConversation;
        }
    }
);

watch(
    () => props.initialConversations,
    (newConversations) => {
        conversations.value = newConversations;
    }
);

const selectConversation = async (conversation) => {
    try {
        const response = await axios.get(`/conversations/${conversation.id}`);
        console.log("Selected conversation:", response.data);
        currentConversation.value = response.data;
    } catch (error) {
        console.error("Error fetching conversation:", error);
    }
};

const deleteConversation = async (id) => {
    // try {
    //     await axios.delete(`/conversations/${id}`);
    //     conversations.value = conversations.value.filter(
    //         (conv) => conv.id !== id
    //     );
    //     if (currentConversation.value?.id === id) {
    //         currentConversation.value = null;
    //         // Clear the chat when deleting the current conversation
    //         chatComponent.value?.clearChat();
    //     }
    // } catch (error) {
    //     console.error("Error deleting conversation:", error);
    // }
};

const handleModelChange = (model) => {
    currentModel.value = model;
};
</script>
