<template>
    <AppLayout>
        <div class="flex h-full overflow-hidden">
            <Conversations
                :style="{ width: `${sidebarWidth}px` }"
                class="relative flex-none border-r border-gray-700"
                :conversations="conversations"
                :selectedId="currentConversation?.id"
                :currentModel="currentModel"
                @select="selectConversation"
                @delete="handleConversationDelete"
                @resize="handleResize"
                @create="createNewConversation"
            />
            <div class="flex flex-col flex-grow w-full">
                <ModelsSelector
                    class="flex-none"
                    :models="models"
                    :currentModel="currentModel"
                    :customInstructions="customInstructions"
                    :currentInstructionId="
                        currentConversation?.custom_instruction_id
                    "
                    @selected-model="handleModelSelected"
                    @selected-instruction="handleInstructionSelected"
                />
                <Chat
                    ref="chatComponent"
                    class="h-full grow"
                    :currentModel="currentModel"
                    :conversation="currentConversation"
                    :flash="flash"
                    @message-sent="handleMessageSent"
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
    customInstructions: {
        type: Array,
        default: () => [],
    },
});

const conversations = ref(props.conversations);
const currentConversation = ref(null);
const sidebarWidth = ref(300);
const currentModel = ref(
    props.models.find(
        (m) => m.id === "meta-llama/llama-3.2-11b-vision-instruct:free"
    )
);
const localMessages = ref([]);
const chatComponent = ref(null);

// Define selectConversation before it's used in watchers
const selectConversation = (conversation) => {
    currentConversation.value = conversation;
    // Update model when conversation is selected
    if (conversation?.model_id) {
        const model = props.models.find((m) => m.id === conversation.model_id);
        if (model) {
            // console.log("Setting model from conversation:", model);
            currentModel.value = model;
        }
    }
};

const conversationForm = useForm({
    model: currentModel.value,
});

// Core event handlers
const handleModelSelected = (model) => {
    currentModel.value = model;
    if (currentConversation.value) {
        useForm({ model }).put(
            route("conversations.model.update", currentConversation.value.id),
            {
                preserveScroll: true,
                onSuccess: (response) => {
                    if (response.props.conversation) {
                        currentConversation.value = response.props.conversation;
                        conversations.value = conversations.value.map((conv) =>
                            conv.id === response.props.conversation.id
                                ? response.props.conversation
                                : conv
                        );
                    }
                },
            }
        );
    }
};

const handleInstructionSelected = (instructionId) => {
    if (currentConversation.value) {
        useForm({ custom_instruction_id: instructionId }).put(
            route(
                "conversations.custom-instruction.update",
                currentConversation.value.id
            ),
            {
                preserveScroll: true,
                onSuccess: (response) => {
                    // Add this to update the conversation with new instruction
                    if (response.props.conversation) {
                        currentConversation.value = response.props.conversation;
                        conversations.value = conversations.value.map((conv) =>
                            conv.id === response.props.conversation.id
                                ? response.props.conversation
                                : conv
                        );
                    }
                },
            }
        );
    }
};

const handleMessageSent = (formData) => {
    useForm({
        // conversation_id: formData.get("conversation_id"),
        message: formData.get("message"),
        conversation: currentConversation.value,
        // files: formData.getAll("files[]"), // Fix: Get all files with correct key
    }).post(route("ask.post"), {
        preserveScroll: true,
        forceFormData: true, // Add this
        onSuccess: (page) => {
            const updatedConversation = page.props.flash?.conversation;
            if (updatedConversation) {
                currentConversation.value = { ...updatedConversation };
                conversations.value = conversations.value.map((conv) =>
                    conv.id === updatedConversation.id
                        ? updatedConversation
                        : conv
                );
            }
        },
    });
};

const createNewConversation = () => {
    useForm({
        model: currentModel.value,
    }).post(route("conversations.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.conversation) {
                conversations.value = [
                    page.props.flash.conversation,
                    ...conversations.value,
                ];
                selectConversation(page.props.flash.conversation);
            }
        },
    });
};

// Modify the flash watcher to handle both new and updated conversations
watch(
    () => props.flash?.conversation,
    (newConversation) => {
        if (!newConversation) return;

        // console.log("Flash conversation received:", newConversation);

        // Check if conversation already exists
        const existingIndex = conversations.value.findIndex(
            (c) => c.id === newConversation.id
        );

        if (existingIndex !== -1) {
            // Update existing conversation
            // console.log("Updating existing conversation");
            conversations.value[existingIndex] = newConversation;
        } else {
            // Add new conversation
            // console.log("Adding new conversation");
            conversations.value = [newConversation, ...conversations.value];
        }

        selectConversation(newConversation);
    },
    { immediate: true }
);

watch(
    () => currentConversation.value,
    (newConversation, oldConversation) => {
        if (oldConversation) {
            // Unsubscribe from old channel
            channelSubscription.value?.unsubscribe();
        }

        if (newConversation) {
            const channel = `chat.${newConversation.id}`;
            console.log("🔌 Tentative de connexion au canal:", channel);

            const subscription = window.Echo.private(channel)
                .subscribed(() => {
                    console.log("✅ Connecté avec succès au canal:", channel);
                })
                .error((error) => {
                    console.error("❌ Erreur de connexion au canal:", error);
                })
                .listen(".message.streamed", (event) => {
                    console.log("📨 Message reçu:", event);

                    const lastMessage =
                        localMessages.value[localMessages.value.length - 1];

                    if (!lastMessage || lastMessage.role !== "assistant") {
                        console.log(
                            "⚠️ Aucun message assistant ciblé pour concaténer"
                        );
                        return;
                    }

                    if (event.error) {
                        console.error("❌ Erreur reçue:", event.error);
                        localMessages.value.pop();
                        props.flash.error = event.content;
                        return;
                    }

                    if (lastMessage.isLoading && event.content) {
                        lastMessage.isLoading = false;
                    }

                    if (!event.isComplete) {
                        lastMessage.content += event.content;
                        nextTick(() => chatComponent.value?.scrollToBottom());
                    }

                    if (event.isComplete) {
                        console.log("✅ Message complet reçu");
                    }
                });

            channelSubscription.value = subscription;
        }
    },
    { immediate: true }
);
// Basic utilities
const handleResize = (width) => {
    sidebarWidth.value = width;
};

const handleConversationDelete = (conversationId) => {
    conversations.value = conversations.value.filter(
        (c) => c.id !== conversationId
    );
    if (currentConversation.value?.id === conversationId) {
        currentConversation.value = null;
    }
};

const channelSubscription = ref(null);

// Initialize first conversation
onMounted(() => {
    if (conversations.value.length > 0) {
        selectConversation(conversations.value[0]);
    }
    channelSubscription.value?.unsubscribe();
});
</script>
