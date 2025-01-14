<template>
    <div class="flex flex-col h-full bg-slate-900">
        <div class="flex-none p-4">
            <button
                @click="createNewConversation"
                class="flex items-center justify-center w-full gap-2 px-4 py-2 text-white transition-colors rounded-lg bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800"
            >
                <font-awesome-icon icon="fa-solid fa-plus" />
                <span>Nouvelle conversation</span>
            </button>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="flex items-center justify-center flex-1">
            <font-awesome-icon
                icon="fa-solid fa-circle-notch"
                class="text-4xl text-gray-600 animate-spin"
            />
        </div>

        <!-- Empty state -->
        <div
            v-else-if="!props.conversations.length"
            class="flex items-center justify-center flex-1 p-4 text-gray-500"
        >
            <div class="text-center">
                <font-awesome-icon
                    icon="fa-solid fa-comments"
                    class="mb-2 text-4xl"
                />
                <p>No conversations yet</p>
            </div>
        </div>

        <!-- Conversations list -->
        <div v-else class="flex-grow overflow-y-auto">
            <div class="flex flex-col p-2 space-y-1">
                <div
                    v-if="sortedConversations.length === 0"
                    class="p-4 text-center text-gray-400"
                >
                    Aucune conversation.
                </div>
                <div
                    v-for="conversation in sortedConversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    class="flex items-center justify-between p-3 transition-colors rounded-lg cursor-pointer group"
                    :class="[
                        conversation.id === selectedId
                            ? 'bg-purple-600 text-white'
                            : 'text-gray-300 hover:bg-gray-800',
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <font-awesome-icon icon="fa-solid fa-comments" />
                        <div class="flex flex-col">
                            <div
                                class="text-sm font-medium truncate max-w-[200px]"
                            >
                                {{ conversation.title }}
                            </div>
                            <div class="text-xs opacity-75">
                                {{ formatDateTime(conversation.updated_at) }}
                            </div>
                        </div>
                    </div>
                    <button
                        @click.stop="deleteConversation(conversation)"
                        class="hidden text-gray-300 group-hover:block hover:text-red-500"
                        :disabled="deleteForm.processing"
                    >
                        <font-awesome-icon
                            :icon="
                                deleteForm.processing
                                    ? 'fa-solid fa-circle-notch'
                                    : 'fa-solid fa-trash'
                            "
                            :class="{ 'animate-spin': deleteForm.processing }"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDateTime } from "@/Lib/utils";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { ref, watch, computed } from "vue";

const emit = defineEmits(["select", "delete", "conversation-created"]);
const props = defineProps({
    conversations: {
        type: Array,
        required: true,
    },
    selectedId: {
        type: [String, Number],
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    currentModel: {
        type: Object,
    },
});

const conversations = ref(props.conversations);

// Watch for changes in props.conversations and update the ref
watch(
    () => props.conversations,
    (newConversations) => {
        console.log("Conversations updated", newConversations);
        conversations.value = newConversations;
    }
);

watch(
    () => props.selectedId,
    (newSelectedId) => {
        console.log("Selected conversation changed", newSelectedId);
    }
);

const form = useForm({
    model: props.defaultModel,
});

const conversationForm = useForm({
    model: props.currentModel,
});

const deleteForm = useForm({});

const selectConversation = async (conversation) => {
    try {
        const response = await axios.get(
            route("conversations.show", conversation.id)
        );
        emit("select", response.data);
    } catch (error) {
        console.error("Error fetching conversation:", error);
    }
};

const deleteConversation = (conversation) => {
    deleteForm.delete(route("conversations.destroy", conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            conversations.value = conversations.value.filter(
                (c) => c.id !== conversation.id
            );
            // emit("delete", conversation.id);
        },
    });
};

const createNewConversation = () => {
    conversationForm.post(route("conversations.store"), {
        onSuccess: (page) => {
            const newConversation = page.props.flash.conversation;
            if (newConversation) {
                // Create a new array with the new conversation
                conversations.value = [...conversations.value, newConversation];
                // Automatically select the new conversation
                selectConversation(newConversation);
                emit("conversation-created", newConversation);
            }
        },
    });
};

const sortedConversations = computed(() => {
    return [...conversations.value].sort((a, b) => {
        const aDate = a.messages?.length
            ? new Date(a.messages[a.messages.length - 1].created_at)
            : new Date(a.updated_at);
        const bDate = b.messages?.length
            ? new Date(b.messages[b.messages.length - 1].created_at)
            : new Date(b.updated_at);
        return bDate - aDate;
    });
});
</script>
