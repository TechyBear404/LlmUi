<template>
    <div
        class="flex items-center justify-between p-4 border-b border-gray-700 bg-slate-900"
    >
        <div class="flex items-center space-x-4">
            <!-- Model Selector -->
            <select
                v-model="selectedModel"
                class="px-3 py-2 text-gray-200 bg-gray-800 border-gray-700 rounded-lg"
                @change="updateModel"
            >
                <option v-for="model in models" :key="model.id" :value="model">
                    {{ model.name }}
                </option>
            </select>

            <!-- Custom Instructions Selector -->
            <div class="flex items-center space-x-2">
                <select
                    v-model="selectedInstruction"
                    class="px-3 py-2 text-gray-200 bg-gray-800 border-gray-700 rounded-lg"
                    @change="updateInstruction"
                >
                    <option :value="null">No custom instructions</option>
                    <option
                        v-for="instruction in customInstructions"
                        :key="instruction.id"
                        :value="instruction.id"
                    >
                        {{ instruction.name }}
                    </option>
                </select>
                <Link
                    :href="route('custom-instructions.index')"
                    class="p-2 text-gray-400 hover:text-purple-500"
                >
                    <font-awesome-icon icon="fa-solid fa-gear" />
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    models: Array,
    currentModel: Object,
    customInstructions: {
        type: Array,
        default: () => [],
    },
    currentInstructionId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(["selected-model", "selected-instruction"]);

const selectedModel = ref(props.currentModel);
const selectedInstruction = ref(props.currentInstructionId);

// Add watcher for currentInstructionId
watch(
    () => props.currentInstructionId,
    (newInstructionId) => {
        // console.log("Instruction changed in parent:", newInstructionId);
        selectedInstruction.value = newInstructionId;
    },
    { immediate: true }
);

// Existing model watcher
watch(
    () => props.currentModel,
    (newModel) => {
        // console.log("Model changed in parent:", newModel);
        if (newModel?.id) selectedModel.value = newModel;
    },
    { immediate: true, deep: true }
);

const updateModel = () => {
    // console.log("Model selected:", selectedModel.value);
    emit("selected-model", selectedModel.value);
};

const updateInstruction = () => {
    emit("selected-instruction", selectedInstruction.value);
};
</script>
