<template>
    <div class="flex flex-col h-full bg-slate-950">
        <!-- Header with model selector -->
        <div
            class="flex-none p-4 bg-gray-800 border-b border-gray-700 shadow-sm"
        >
            <div class="flex items-center justify-center">
                <select
                    v-model="modelForm.model"
                    name="model"
                    :disabled="modelForm.processing"
                    class="w-full max-w-xs px-3 py-2 text-gray-200 bg-gray-700 border border-gray-600 rounded-md focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    @change="handleModelChange"
                >
                    <option value="">Select a model</option>
                    <option
                        v-for="model in models"
                        :key="model.id"
                        :value="model"
                        :selected="model.id === modelForm.model?.id"
                    >
                        {{ model.name }}
                    </option>
                </select>
            </div>
        </div>

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

        <!-- Chat messages area -->
        <div v-else class="flex-1 w-full overflow-hidden">
            <div ref="messageContainer" class="h-full overflow-y-auto">
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
        </div>

        <!-- Fixed Message input area -->
        <div
            class="flex-none border-t border-slate-800 bg-gradient-to-t from-slate-900 to-slate-950"
        >
            <div class="p-4">
                <form
                    class="flex gap-4"
                    @submit.prevent="sendMessageToConversation"
                >
                    <textarea
                        v-model="messageForm.message"
                        rows="1"
                        ref="messageInput"
                        placeholder="Envoyer un message..."
                        class="flex-1 p-3 overflow-y-auto transition-all duration-200 border resize-none text-slate-100 placeholder-slate-400 bg-slate-800 border-slate-700 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/10 focus:outline-none textarea-scroll"
                        @keydown.enter.exact.prevent="sendMessage"
                        @input="adjustTextareaHeight"
                    ></textarea>
                    <button
                        type="submit"
                        :disabled="
                            !props.conversation || messageForm.processing
                        "
                        class="px-4 py-2 h-[44px] text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                        :class="{
                            'cursor-not-allowed': messageForm.processing,
                        }"
                        @click.prevent="sendMessageToConversation"
                    >
                        <span v-if="messageForm.processing">
                            <svg
                                class="text-gray-300 animate-spin"
                                viewBox="0 0 64 64"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                            >
                                <path
                                    d="M32 3C35.8083 3 39.5794 3.75011 43.0978 5.20749C46.6163 6.66488 49.8132 8.80101 52.5061 11.4939C55.199 14.1868 57.3351 17.3837 58.7925 20.9022C60.2499 24.4206 61 28.1917 61 32C61 35.8083 60.2499 39.5794 58.7925 43.0978C57.3351 46.6163 55.199 49.8132 52.5061 52.5061C49.8132 55.199 46.6163 57.3351 43.0978 58.7925C39.5794 60.2499 35.8083 61 32 61C28.1917 61 24.4206 60.2499 20.9022 58.7925C17.3837 57.3351 14.1868 55.199 11.4939 52.5061C8.801 49.8132 6.66487 46.6163 5.20749 43.0978C3.7501 39.5794 3 35.8083 3 32C3 28.1917 3.75011 24.4206 5.2075 20.9022C6.66489 17.3837 8.80101 14.1868 11.4939 11.4939C14.1868 8.80099 17.3838 6.66487 20.9022 5.20749C24.4206 3.7501 28.1917 3 32 3L32 3Z"
                                    stroke="currentColor"
                                    stroke-width="5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                                <path
                                    d="M32 3C36.5778 3 41.0906 4.08374 45.1692 6.16256C49.2477 8.24138 52.7762 11.2562 55.466 14.9605C58.1558 18.6647 59.9304 22.9531 60.6448 27.4748C61.3591 31.9965 60.9928 36.6232 59.5759 40.9762"
                                    stroke="currentColor"
                                    stroke-width="5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="text-indigo-500"
                                ></path>
                            </svg>
                        </span>
                        <span v-else> Envoyer </span>
                    </button>
                </form>
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
import { router } from "@inertiajs/vue3";

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
    conversation: {
        type: Object,
        required: false,
        default: null,
    },
    defaultModel: {
        type: Object,
        required: true,
    },
});

// Initialize messages as an empty array
const messages = ref([]);

// Initialize form with the appropriate model
const messageForm = useForm({
    message: "",
    model: props.defaultModel,
    conversation_id: props.conversation?.id,
});

const modelForm = useForm({
    model: props.conversation?.model_id
        ? props.models.find((m) => m.id === props.conversation.model_id)
        : props.defaultModel,
});

const messageContainer = ref(null);
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
        if (messageContainer.value) {
            messageContainer.value.scrollTo({
                top: messageContainer.value.scrollHeight,
                behavior: "smooth",
            });
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
    form.conversation_id = null;
};

// Add clearChat to expose it to the parent component
defineExpose({ clearChat });

const handleModelChange = () => {
    if (!props.conversation) {
        return;
    }

    modelForm.put(
        route("conversations.model.update", {
            conversation: props.conversation.id,
        }),
        {
            preserveScroll: true,
            onSuccess: (response) => {
                if (response?.props?.conversation) {
                    messages.value = response.props.conversation.messages;
                    emit("selected-model", modelForm.model);
                }
            },
        }
    );
};

// Update the watch handler for the conversation to properly handle model selection
watch(
    () => props.conversation,
    (newConversation) => {
        messages.value = newConversation?.messages || [];

        if (newConversation) {
            const conversationModel = props.models.find(
                (m) => m.id === newConversation.model_id
            );
            if (conversationModel) {
                modelForm.model = conversationModel;
            }
        } else {
            modelForm.model = props.defaultModel;
        }

        emit("selected-model", modelForm.model);
        scrollToBottom();
    },
    { immediate: true }
);

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

/* Hide scrollbar when height is less than 200px */
.textarea-scroll {
    overflow-y: hidden;
}

.textarea-scroll[style*="height: 200px"] {
    overflow-y: auto;
}
</style>
