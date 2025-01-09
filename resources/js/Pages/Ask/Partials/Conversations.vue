<template>
    <div class="flex flex-col h-full bg-slate-900">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-gray-200">Conversations</h2>
        </div>
        <div class="flex-grow overflow-y-auto">
            <div class="flex flex-col p-2 space-y-1">
                <!-- New Conversation Button -->
                <button
                    @click="createNewConversation"
                    class="flex items-center p-3 space-x-3 text-gray-300 transition-colors rounded-lg cursor-pointer hover:bg-gray-800"
                >
                    <font-awesome-icon icon="fa-solid fa-plus" />
                    <span>Nouvelle conversation</span>
                </button>

                <div
                    v-if="props.conversations.length === 0"
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

const emit = defineEmits(["select", "delete", "new-conversation"]);
const props = defineProps({
    conversations: {
        type: Array,
        required: true,
    },
    selectedId: {
        type: Number,
        default: null,
    },
});

const selectConversation = (conversation) => {
    emit("select", conversation);
};

const deleteConversation = (id) => {
    emit("delete", id);
};

// Add new method
const createNewConversation = () => {
    emit("new-conversation");
};
</script>
