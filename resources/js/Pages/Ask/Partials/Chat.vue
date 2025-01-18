<template>
    <div class="relative flex flex-col h-full">
        <!-- Empty state when no conversation exists -->
        <div
            v-if="!props.conversation"
            class="flex items-center justify-center flex-1 text-gray-400"
        >
            <div class="space-y-4 text-center">
                <font-awesome-icon
                    icon="fa-solid fa-comments"
                    class="text-6xl"
                />
                <p class="text-lg">
                    Select or create a conversation to start chatting
                </p>
            </div>
        </div>

        <div v-else class="absolute inset-0 flex flex-col">
            <!-- Messages container -->
            <div class="flex-1 overflow-y-auto" ref="messagesContainer">
                <div class="max-w-4xl p-4 mx-auto">
                    <div
                        class="w-full space-y-6 text-white prose-pre:bg-gray-900"
                    >
                        <div
                            v-if="flash?.error"
                            class="p-4 text-red-400 rounded-lg bg-red-900/50"
                        >
                            {{ flash.error }}
                        </div>
                        <div
                            v-for="(message, index) in messages"
                            :key="index"
                            :class="[
                                'flex gap-3 items-start p-4 w-full',
                                message.role === 'user'
                                    ? 'justify-end'
                                    : 'justify-start',
                            ]"
                        >
                            <!-- Assistant Avatar - only shown for assistant messages -->
                            <div
                                v-if="message.role === 'assistant'"
                                class="flex items-center justify-center flex-none w-8 h-8 bg-purple-600 rounded-full"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>

                            <!-- Message Content -->
                            <div
                                :class="[
                                    'max-w-[80%] p-4 rounded-2xl break-words transition-colors flex flex-col gap-4',
                                    message.role === 'user'
                                        ? 'bg-purple-600 text-purple-100 rounded-br-none '
                                        : 'bg-gray-800 text-gray-200 rounded-bl-none ',
                                    'prose prose-p:my-0 prose-invert prose-pre:bg-gray-900',
                                ]"
                            >
                                <div
                                    v-html="renderMarkdown(message.content)"
                                ></div>
                                <div
                                    class="flex justify-between"
                                    :class="
                                        message.role === 'user'
                                            ? 'text-purple-200'
                                            : 'text-gray-400'
                                    "
                                >
                                    <div class="text-xs">
                                        {{
                                            message.updated_at
                                                ? formatDateTime(
                                                      message.updated_at
                                                  )
                                                : formatDateTime(
                                                      message.created_at
                                                  )
                                        }}
                                    </div>
                                    <div
                                        class="flex items-center gap-1 hover:text-gray-300 hover:cursor-pointer"
                                        v-if="message.role === 'assistant'"
                                        @click="
                                            handleCopy(
                                                message.content,
                                                message.id
                                            )
                                        "
                                    >
                                        <span
                                            v-if="copiedStates.get(message.id)"
                                            class="text-xs"
                                            >Copié</span
                                        >
                                        <font-awesome-icon
                                            :icon="
                                                copiedStates.get(message.id)
                                                    ? 'fa-solid fa-clipboard-check'
                                                    : 'fa-regular fa-clipboard'
                                            "
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- User Avatar - only shown for user messages -->
                            <div
                                v-if="message.role === 'user'"
                                class="flex items-center justify-center flex-none w-8 h-8 bg-gray-700 rounded-full"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input container -->
            <div
                class="flex-none w-full p-4 border-t border-gray-700 bg-slate-900"
            >
                <div class="relative max-w-4xl mx-auto">
                    <!-- Replace file input with FileDropZone -->
                    <FileDropZone
                        v-model="files"
                        accept=".pdf,.docx,.txt,.js,.vue,.php,.log"
                        class="mb-4"
                    />
                    <textarea
                        ref="textarea"
                        v-model="messageForm.message"
                        @keydown="handleKeyDown"
                        @keydown.enter.prevent="handleEnter"
                        class="w-full px-4 py-3 text-sm text-gray-200 bg-gray-800 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-purple-500 textarea-scroll"
                        :rows="1"
                        style="min-height: 44px; max-height: 200px"
                        placeholder="Envoyez un message..."
                        :disabled="messageForm.processing"
                        @input="autoResize"
                        :style="{
                            minHeight: '44px',
                            maxHeight: '200px',
                            height: textareaHeight + 'px',
                        }"
                    ></textarea>
                    <button
                        @click="sendMessageToConversation"
                        class="absolute p-2 text-gray-400 right-2 bottom-2 hover:text-purple-500 disabled:opacity-50"
                        :disabled="
                            (!messageForm.message && !files?.length) ||
                            messageForm.processing
                        "
                    >
                        <font-awesome-icon
                            :icon="
                                messageForm.processing
                                    ? 'fa-solid fa-circle-notch'
                                    : 'fa-solid fa-paper-plane'
                            "
                            :class="{ 'animate-spin': messageForm.processing }"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from "vue";
import { formatDateTime, renderMarkdown, copyToClipboard } from "@/Lib/utils";
import { useForm } from "@inertiajs/vue3";
import FileDropZone from "./FileDropZone.vue";

const props = defineProps({
    conversation: {
        type: Object,
        default: null,
    },
    currentModel: {
        type: Object,
        required: true,
    },
    loading: Boolean,
    flash: {
        type: Object,
        default: () => ({}), // Add this default value
    },
});

// Add textarea ref
const textarea = ref(null);

const emit = defineEmits(["messageSent"]);

const messages = ref([]);
const messagesContainer = ref(null);
const textareaHeight = ref(44);

// Replace single isCopied with a Map to track per-message copy state
const copiedStates = ref(new Map());

// Initialize files ref with empty array
const files = ref([]);

// Modify messageForm to include files
const messageForm = useForm({
    message: "",
    conversation_id: props.conversation?.id,
    files: [],
});

// Watch for conversation changes to update form
watch(
    () => props.conversation,
    (newConv) => {
        if (newConv) {
            messageForm.conversation_id = newConv.id;
        }
    }
);

// Update watch for conversation changes to be more reactive
watch(
    () => props.conversation?.messages,
    (newMessages) => {
        if (newMessages) {
            messages.value = [...newMessages];
            scrollToBottom();
        } else {
            messages.value = [];
        }
    },
    { immediate: true, deep: true }
);

watch(
    () => props.conversation?.messages,
    (newMessages, oldMessages) => {
        if (newMessages) {
            messages.value = [...newMessages];
            scrollToBottom();
        } else {
            messages.value = [];
        }
    },
    { immediate: true, deep: true }
);

// Fonction pour gérer le changement de fichier
const handleFileChange = async (e) => {
    const file = e.target.files[0]; // Get the file
    if (file) {
        try {
            const content = await readFile(file); // Read file content
            messageForm.message = content; // Set the message content to the file content
            messageForm.file = file; // Attach file to the form data
        } catch (err) {
            console.error("Error reading file", err);
        }
    }
};

// Fonction pour lire un fichier
const readFile = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result); // Résultat de la lecture
        reader.onerror = (error) => reject(error); // Gérer les erreurs
        reader.readAsText(file); // Lire le fichier comme texte
    });
};

const sendMessageToConversation = () => {
    if (
        (!messageForm.message && files.value.length === 0) ||
        messageForm.processing
    )
        return;

    const formData = new FormData();
    formData.append("conversation_id", props.conversation.id);

    if (messageForm.message) {
        formData.append("message", messageForm.message);
    }

    // Fix: Append files with correct key
    if (files.value.length > 0) {
        files.value.forEach((file) => {
            formData.append("files[]", file);
        });
    }

    // Add optimistic message
    if (messageForm.message) {
        messages.value = [
            ...messages.value,
            {
                content: messageForm.message,
                role: "user",
                created_at: new Date().toISOString(),
            },
        ];
        scrollToBottom();
    }

    emit("messageSent", formData);

    messageForm.reset("message");
    files.value = [];
};

const handleKeyDown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessageToConversation();
    }
};

const handleEnter = () => {
    sendMessageToConversation();
};

const autoResize = (e) => {
    const textarea = e.target;
    textarea.style.height = "44px";
    const scrollHeight = Math.min(textarea.scrollHeight, 200);
    textareaHeight.value = scrollHeight;
};

// Add scrollToBottom function
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight;
        }
    });
};

const handleCopy = async (text, messageId) => {
    const success = await copyToClipboard(text);
    if (success) {
        copiedStates.value.set(messageId, true);
        setTimeout(() => {
            copiedStates.value.delete(messageId);
        }, 2000);
    } else {
        alert("Unable to copy text to clipboard");
    }
};
</script>

<style>
/* Hide scrollbar for cleaner look */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.5) transparent;
    -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(100, 116, 139, 0.5);
    border-radius: 3px;
}

/* Custom textarea scrollbar styling */
.textarea-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.5) transparent;
}

.textarea-scroll::-webkit-scrollbar {
    width: 6px;
}

.textarea-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.textarea-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(100, 116, 139, 0.5);
    border-radius: 3px;
}

/* Ensure textarea always shows scrollbar when needed */
.textarea-scroll {
    overflow-y: auto !important;
}

/* Remove conflicting styles */
textarea.textarea-scroll {
    overflow-y: auto !important;
}

/* Hide scrollbar when height is less than 200px */
.textarea-scroll {
    overflow-y: hidden;
}

.textarea-scroll[style*="height: 200px"] {
    overflow-y: auto;
} /* Remove the conflicting textarea styles */
textarea {
    overflow-y: auto;
}
.min-h-0 {
    min-height: 0;
}
</style>
