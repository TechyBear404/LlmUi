<template>
    <div
        class="flex flex-col h-full bg-slate-900"
        :style="{ width: `${width}px` }"
    >
        <div class="flex-none p-4">
            <button
                @click="createConversation"
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
                    class="relative flex items-center p-3 transition-colors rounded-lg cursor-pointer group"
                    :class="[
                        conversation.id === selectedId
                            ? 'bg-purple-600 text-white'
                            : 'text-gray-300 hover:bg-gray-800',
                    ]"
                >
                    <div class="flex items-center flex-1 min-w-0 space-x-3">
                        <font-awesome-icon icon="fa-solid fa-comments" />
                        <div class="flex flex-col min-w-0">
                            <div
                                class="text-sm font-medium truncate"
                                :title="conversation.title"
                            >
                                {{ conversation.title }}
                            </div>
                            <div class="text-xs opacity-75">
                                {{ getLastMessageDate(conversation) }}
                            </div>
                        </div>
                    </div>
                    <button
                        @click.stop="deleteConversation(conversation)"
                        class="absolute hidden text-gray-300 right-2 group-hover:block hover:text-red-500"
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

        <!-- Resizer handle -->
        <div
            class="absolute top-0 z-50 w-2 h-full -right-1 cursor-col-resize"
            @mousedown="startResize"
        >
            <div
                class="w-1 h-full transition-colors bg-transparent hover:bg-purple-500"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { formatDateTime } from "@/Lib/utils";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    conversations: {
        type: Array,
        required: true,
    },
    selectedId: Number,
    loading: Boolean,
});

const emit = defineEmits([
    "select", // Changed from conversationSelected
    "create", // Changed from conversationCreated
    "delete", // Changed from conversationDeleted
    "resize",
]);

const deleteForm = useForm({}); // Add this line to define deleteForm

const sortedConversations = computed(() => {
    return [...props.conversations].sort((a, b) => {
        // Get the latest message date for conversation a
        const aLastMessage =
            a.messages?.length > 0 ? a.messages[a.messages.length - 1] : null;
        const aDate = aLastMessage
            ? new Date(aLastMessage.created_at)
            : new Date(a.created_at);

        // Get the latest message date for conversation b
        const bLastMessage =
            b.messages?.length > 0 ? b.messages[b.messages.length - 1] : null;
        const bDate = bLastMessage
            ? new Date(bLastMessage.created_at)
            : new Date(b.created_at);

        // Sort in descending order (most recent first)
        return bDate - aDate;
    });
});

const selectConversation = (conversation) => {
    emit("select", conversation);
};

const deleteConversation = (conversation) => {
    if (confirm("Are you sure you want to delete this conversation?")) {
        deleteForm.delete(route("conversations.destroy", conversation.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit("delete", conversation.id);
            },
        });
    }
};

const createConversation = () => {
    emit("create");
};

const getLastMessageDate = (conversation) => {
    const lastMessage =
        conversation.messages?.length > 0
            ? conversation.messages[conversation.messages.length - 1]
            : null;
    return formatDateTime(
        lastMessage ? lastMessage.created_at : conversation.created_at
    );
};

const width = ref(300); // Default width
const minWidth = 200;
const maxWidth = 600;
let startX = 0;
let startWidth = 0;
let isResizing = false;

const startResize = (event) => {
    isResizing = true;
    startX = event.clientX;
    startWidth = event.target.closest(".bg-slate-900").offsetWidth;

    document.addEventListener("mousemove", handleResize);
    document.addEventListener("mouseup", stopResize);
    // Prevent text selection during resize
    document.body.style.userSelect = "none";
};

const handleResize = (event) => {
    if (!isResizing) return;

    const diff = event.clientX - startX;
    const newWidth = Math.min(Math.max(startWidth + diff, minWidth), maxWidth);

    emit("resize", newWidth);
};

const stopResize = () => {
    isResizing = false;
    document.removeEventListener("mousemove", handleResize);
    document.removeEventListener("mouseup", stopResize);
    document.body.style.userSelect = "";
};
</script>

<style scoped>
/* Prevent text selection during resize */
.cursor-col-resize {
    user-select: none;
}
</style>
