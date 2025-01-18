<template>
    <div class="relative">
        <!-- Hidden file input -->
        <input
            type="file"
            ref="fileInput"
            @change="handleFileInput"
            multiple
            :accept="accept"
            class="hidden"
        />

        <!-- Compact upload button -->
        <button
            @click="$refs.fileInput.click()"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleDrop"
            :class="[
                'flex items-center gap-2 px-3 py-1.5 text-sm rounded-md transition-all',
                dragOver
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
            ]"
            type="button"
        >
            <font-awesome-icon icon="fa-solid fa-paperclip" />
            <span class="text-xs">Ajouter un fichier</span>
        </button>

        <!-- File Preview (only shows when files are present) -->
        <div v-if="modelValue.length > 0" class="mt-2 space-y-1">
            <div
                v-for="(file, index) in modelValue"
                :key="index"
                class="flex items-center justify-between px-2 py-1 text-xs text-gray-300 bg-gray-800 rounded"
            >
                <div class="flex items-center gap-2">
                    <font-awesome-icon
                        icon="fa-solid fa-file"
                        class="text-gray-400"
                    />
                    <span class="truncate max-w-[150px]">{{ file.name }}</span>
                </div>
                <button
                    @click="removeFile(index)"
                    class="text-gray-400 hover:text-red-400"
                >
                    <font-awesome-icon icon="fa-solid fa-xmark" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    accept: {
        type: String,
        default: "*",
    },
});

const emit = defineEmits(["update:modelValue"]);
const dragOver = ref(false);
const fileInput = ref(null);

const handleDrop = (e) => {
    dragOver.value = false;
    const droppedFiles = [...e.dataTransfer.files];
    emit("update:modelValue", [...props.modelValue, ...droppedFiles]);
};

const handleFileInput = (e) => {
    const selectedFiles = [...e.target.files];
    emit("update:modelValue", [...props.modelValue, ...selectedFiles]);
};

const removeFile = (index) => {
    const updatedFiles = props.modelValue.filter((_, i) => i !== index);
    emit("update:modelValue", updatedFiles);
};
</script>
