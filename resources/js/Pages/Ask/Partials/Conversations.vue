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
                    v-if="
                        props.conversations && props.conversations.length === 0
                    "
                    class="p-4 text-center text-gray-400"
                >
                    Aucune conversation.
                </div>
                <div
                    v-for="conversation in props.conversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    class="flex items-center p-3 transition-colors rounded-lg cursor-pointer"
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
                        v-if="conversation.id === selectedId"
                        @click.stop="deleteConversation(conversation.id)"
                        class="ml-auto text-gray-300 hover:text-red-500"
                    >
                        <font-awesome-icon icon="fa-solid fa-trash" />
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

const emit = defineEmits(["select", "delete", "conversation-created"]);
const props = defineProps({
    conversations: {
        type: Array,
        required: true,
        default: () => [],
    },
    selectedId: {
        type: [String, Number],
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    defaultModel: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    model: props.defaultModel,
});

const conversationForm = useForm({
    model: props.defaultModel,
});

const selectConversation = (conversation) => {
    emit("select", conversation);
};

const deleteConversation = (id) => {
    emit("delete", id);
};

const createNewConversation = () => {
    conversationForm.post(route("conversations.store"), {
        onSuccess: (response) => {
            if (response?.props?.conversation) {
                emit("conversation-created", response.props.conversation);
            }
        },
    });
};
</script>
