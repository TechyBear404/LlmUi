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
                            v-if="flash.error"
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
                                        {{ formatDateTime(message.updated_at) }}
                                    </div>
                                    <div
                                        class="flex items-center gap-1 hover:text-gray-300 hover:cursor-pointer"
                                        v-if="message.role === 'assistant'"
                                        @click="
                                            copyToClipboard(message.content)
                                        "
                                    >
                                        <span v-if="isCopied" class="text-xs"
                                            >Copié</span
                                        >
                                        <font-awesome-icon
                                            :icon="
                                                isCopied
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

            <!-- Input container - now properly fixed at bottom -->
            <div
                class="flex-none w-full p-4 border-t border-gray-700 bg-slate-900"
            >
                <div class="relative max-w-4xl mx-auto">
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
                            !messageForm.message || messageForm.processing
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
import { useForm } from "@inertiajs/vue3";
import { ref, watch, nextTick, onMounted } from "vue";
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import "highlight.js/styles/github-dark.css";
import { formatDateTime } from "@/Lib/utils";

const props = defineProps({
    flash: {
        type: Object,
        default: () => ({}),
    },
    conversation: {
        type: Object,
        required: true,
    },
    currentModel: {
        type: Object,
        required: true,
    },
});

console.log(props.conversation);

// Initialize messages as an empty array
const messages = ref([]);

// Initialize form with the appropriate model
const messageForm = useForm({
    message: "",
    model: props.currentModel,
    conversation_id: props.conversation?.id,
});

console.log("Conversations", props.conversation_id);

const messagesContainer = ref(null);
const messageInput = ref(null);
const isCopied = ref(false);

const emit = defineEmits([
    "message-sent",
    "selected-model",
    "conversation-created",
]);

// Configure markdown-it with syntax highlighting
const md = new MarkdownIt({
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return `<pre class="hljs"><code>${
                    hljs.highlight(str, { language: lang }).value
                }</code></pre>`;
            } catch (__) {}
        }
        return `<pre class="hljs"><code>${md.utils.escapeHtml(
            str
        )}</code></pre>`;
    },
    linkify: true,
    breaks: true,
});

// Render markdown content
const renderMarkdown = (content) => {
    try {
        return md.render(content);
    } catch (e) {
        return content;
    }
};

// Scroll to bottom of messages
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight;
        }
    });
};

const adjustTextareaHeight = () => {
    const textarea = messageInput.value;
    if (!textarea) return;

    // Reset height to auto to get the correct scrollHeight
    textarea.style.height = "auto";

    // Set new height based on scrollHeight
    const newHeight = Math.min(textarea.scrollHeight, 200); // Max height of 200px
    textarea.style.height = `${newHeight}px`;
};

const sendMessageToConversation = () => {
    console.log("Starting sendMessageToConversation");

    if (
        !props.conversation?.id ||
        !messageForm.message ||
        messageForm.processing
    ) {
        console.log("Validation failed:", {
            hasConversation: !!props.conversation?.id,
            hasMessage: !!messageForm.message,
            isProcessing: messageForm.processing,
        });
        return;
    }

    messageForm.processing = true;

    // Create temporary message for optimistic UI
    const tempMessage = {
        role: "user",
        content: messageForm.message,
        updated_at: new Date().toISOString(),
    };

    if (Array.isArray(messages.value)) {
        messages.value.push(tempMessage);
        scrollToBottom();
    } else {
        messages.value = [tempMessage];
    }

    // Correctly structure the data for the request
    messageForm.post(route("ask.post"), {
        preserveScroll: true,
        onSuccess: (response) => {
            console.log("Request successful:", response);
            if (response?.props?.conversation?.messages) {
                messages.value = response.props.conversation.messages;
            }
            messageForm.reset("message");
            adjustTextareaHeight();
            scrollToBottom();
        },
        onError: (errors) => {
            console.error("Request failed with errors:", errors);
            messages.value = messages.value.filter((m) => m !== tempMessage);
        },
        onFinish: () => {
            messageForm.processing = false;
        },
    });
};

const copyToClipboard = async (text) => {
    try {
        if (!navigator.clipboard) {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand("copy");
                isCopied.value = true;
                setTimeout(() => (isCopied.value = false), 1000);
            } catch (err) {
                console.error("Fallback: Impossible de copier le texte", err);
            }
            document.body.removeChild(textArea);
            return;
        }

        await navigator.clipboard.writeText(text);
        isCopied.value = true;
        setTimeout(() => (isCopied.value = false), 1000);
    } catch (err) {
        console.error("Failed to copy text: ", err);
    }
};

const clearChat = () => {
    messages.value = [];
    messageForm.conversation_id = null;
    messageForm.model = props.defaultModel;
};

// Add clearChat to expose it to the parent component
defineExpose({ clearChat });

// Update the watch handler for the conversation to properly handle model selection
watch(
    () => props.conversation,
    (newConversation) => {
        messages.value = newConversation?.messages || [];
        messageForm.conversation_id = newConversation?.id;
        scrollToBottom();
    },
    { immediate: true }
);

const handleKeyDown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessageToConversation();
    }
};

const textareaHeight = ref(44);

const autoResize = (e) => {
    const textarea = e.target;
    textarea.style.height = "44px";
    const scrollHeight = Math.min(textarea.scrollHeight, 200);
    textareaHeight.value = scrollHeight;
};

onMounted(() => {
    scrollToBottom();
    adjustTextareaHeight();
});
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
}

/* Remove the conflicting textarea styles */
textarea {
    overflow-y: auto;
}

.min-h-0 {
    min-height: 0;
}
</style>
