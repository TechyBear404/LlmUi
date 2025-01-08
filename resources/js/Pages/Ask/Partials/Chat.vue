<template>
    <div class="flex flex-col h-full bg-slate-950">
        <!-- Header with model selector -->
        <div
            class="flex-none p-4 bg-gray-800 border-b border-gray-700 shadow-sm"
        >
            <div class="flex items-center justify-center">
                <select
                    v-model="form.model"
                    name="model"
                    class="w-full max-w-xs px-3 py-2 text-gray-200 bg-gray-700 border border-gray-600 rounded-md focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                >
                    <option
                        v-for="model in models"
                        :value="model.id"
                        :key="model.id"
                    >
                        {{ model.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Chat messages area -->
        <div class="flex-1 w-full overflow-hidden">
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
                                    'max-w-[80%] p-4 rounded-2xl break-words transition-colors',
                                    message.role === 'user'
                                        ? 'bg-purple-600 text-white rounded-br-none hover:bg-purple-700'
                                        : 'bg-gray-800 text-gray-200 rounded-bl-none hover:bg-gray-700',
                                    'prose prose-invert prose-pre:bg-gray-900',
                                ]"
                                v-html="renderMarkdown(message.content)"
                            ></div>

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
                <form class="flex gap-4" @submit.prevent="sendMessage">
                    <textarea
                        v-model="form.message"
                        rows="1"
                        ref="messageInput"
                        placeholder="Envoyer un message..."
                        class="flex-1 p-3 overflow-y-auto transition-all duration-200 border resize-none text-slate-100 placeholder-slate-400 bg-slate-800 border-slate-700 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/10 focus:outline-none textarea-scroll"
                        @keydown.enter.exact.prevent="sendMessage"
                        @input="adjustTextareaHeight"
                    ></textarea>
                    <button
                        type="submit"
                        class="px-4 py-2 h-[44px] text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                        @click.prevent="sendMessage"
                    >
                        Envoyer
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

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    selectedModel: {
        type: String,
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
});
console.log(props.conversation);

const messages = ref([]);

const form = useForm({
    message: "",
    model: props.selectedModel,
    conversation_id: null,
});

const messageContainer = ref(null);
const messageInput = ref(null);

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

const sendMessage = () => {
    if (!form.message) return;

    messages.value.push({
        role: "user",
        content: form.message,
    });

    // Add user message to chat
    // messages.value.push({
    //     role: "user",
    //     content: form.message,
    // });

    // Send complete message history
    // form.messages = messages.value;

    form.post(route("ask.post"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("message");
        },
    });

    scrollToBottom();

    // Reset textarea height after sending
    nextTick(() => {
        if (messageInput.value) {
            messageInput.value.style.height = "auto";
        }
    });
};

watch(
    () => props.conversation,
    (newConversation) => {
        if (newConversation) {
            messages.value = newConversation.messages;
            form.conversation_id = newConversation.id;
            scrollToBottom();
        }
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
