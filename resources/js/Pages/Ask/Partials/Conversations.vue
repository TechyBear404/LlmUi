<template>
    <div class="flex flex-col h-full overflow-hidden">
        <div class="flex-grow overflow-y-auto">
            <div class="flex flex-col space-y-2">
                <div
                    v-if="props.conversations.length === 0"
                    class="p-4 text-gray-400"
                >
                    No conversations found.
                </div>
                <div
                    v-for="conversation in props.conversations"
                    :key="conversation.id"
                    class="flex flex-col space-y-2"
                >
                    <div
                        class="flex items-center justify-between p-4 bg-gray-800 border-b border-gray-700"
                    >
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-8 h-8 bg-purple-600 rounded-full"
                            ></div>
                            <div></div>
                            <div class="text-gray-200">
                                {{ conversation.title }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    conversations: {
        type: Array,
        required: true,
    },
});
</script>
