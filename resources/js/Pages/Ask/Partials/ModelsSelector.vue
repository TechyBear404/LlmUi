<template>
    <!-- Header with model selector -->
    <div class="flex-none p-4 bg-gray-800 border-b border-gray-700 shadow-sm">
        <div class="flex items-center justify-center">
            <select
                v-model="selectedModel"
                name="model"
                class="w-full max-w-xs px-3 py-2 text-gray-200 bg-gray-700 border border-gray-600 rounded-md focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                @change="handleModelChange"
            >
                <option value="">Select a model</option>
                <option
                    v-for="model in models"
                    :key="model.id"
                    :value="model"
                    :selected="model.id === selectedModel.id"
                >
                    {{ model.name }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    currentModel: {
        type: Object,
        required: true,
    },
});

const selectedModel = ref(
    props.models.find((model) => model.id === props.currentModel.id)
);

// Watch for changes in the currentModel prop, but keep default if null
// watch(
//     () => props.currentModel,
//     (newValue) => {
//         console.log("currentModel changed", newValue);
//         selectedModel.value = newValue || props.currentModel;
//     },
//     { immediate: true }
// );

const emit = defineEmits(["selected-model"]);

const handleModelChange = () => {
    emit("selected-model", selectedModel.value);
};
</script>
