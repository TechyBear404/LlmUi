<template>
    <AppLayout title="Custom Instructions">
        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="rounded-lg shadow bg-slate-900">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-white">
                            Custom Instructions
                        </h2>
                        <button
                            @click="showCreateForm = true"
                            class="flex items-center gap-2 px-4 py-2 text-white transition-colors bg-purple-600 rounded-lg hover:bg-purple-700"
                        >
                            <font-awesome-icon icon="fa-solid fa-plus" />
                            <span>New Instruction</span>
                        </button>
                    </div>

                    <!-- Instructions Grid -->
                    <div
                        class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="instruction in instructions"
                            :key="instruction.id"
                            class="relative p-4 bg-gray-800 rounded-lg group"
                            :class="{
                                'ring-2 ring-purple-500': instruction.is_active,
                            }"
                        >
                            <h4 class="mb-2 font-medium text-white">
                                {{ instruction.name }}
                            </h4>
                            <div class="space-y-3 text-sm">
                                <div class="text-gray-300">
                                    <p class="mb-1 font-medium">About you:</p>
                                    <p class="line-clamp-2">
                                        {{ instruction.about_user }}
                                    </p>
                                </div>
                                <div class="text-gray-300">
                                    <p class="mb-1 font-medium">
                                        Response style:
                                    </p>
                                    <p class="line-clamp-2">
                                        {{ instruction.ai_response_style }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                class="absolute hidden space-x-2 top-2 right-2 group-hover:flex"
                            >
                                <button
                                    @click="editInstruction(instruction)"
                                    class="p-1.5 text-gray-400 hover:text-purple-500 rounded"
                                >
                                    <font-awesome-icon icon="fa-solid fa-pen" />
                                </button>
                                <button
                                    v-if="!instruction.is_active"
                                    @click="setActive(instruction)"
                                    class="p-1.5 text-gray-400 hover:text-green-500 rounded"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-check"
                                    />
                                </button>
                                <button
                                    @click="deleteInstruction(instruction)"
                                    class="p-1.5 text-gray-400 hover:text-red-500 rounded"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-trash"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showCreateForm" @close="closeModal">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-medium text-white">
                    {{ editingInstruction ? "Edit" : "Create" }} Custom
                    Instruction
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300"
                            >Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">
                            What would you like the AI to know about you?
                        </label>
                        <textarea
                            v-model="form.about_user"
                            rows="4"
                            class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            required
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">
                            How would you like the AI to respond?
                        </label>
                        <textarea
                            v-model="form.ai_response_style"
                            rows="4"
                            class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end mt-6 space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-gray-300 transition-colors hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-white transition-colors bg-purple-600 rounded hover:bg-purple-700 disabled:opacity-50"
                        >
                            <font-awesome-icon
                                v-if="form.processing"
                                icon="fa-solid fa-circle-notch"
                                class="mr-2 animate-spin"
                            />
                            {{ editingInstruction ? "Update" : "Create" }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    instructions: {
        type: Array,
        required: true,
    },
});

const showCreateForm = ref(false);
const editingInstruction = ref(null);

const form = useForm({
    name: "",
    about_user: "",
    ai_response_style: "",
});

const submitForm = () => {
    if (editingInstruction.value) {
        form.put(
            route("custom-instructions.update", editingInstruction.value.id),
            {
                onSuccess: () => closeModal(),
            }
        );
    } else {
        form.post(route("custom-instructions.store"), {
            onSuccess: () => closeModal(),
        });
    }
};

const editInstruction = (instruction) => {
    editingInstruction.value = instruction;
    form.name = instruction.name;
    form.about_user = instruction.about_user;
    form.ai_response_style = instruction.ai_response_style;
    showCreateForm.value = true;
};

const setActive = (instruction) => {
    form.post(route("custom-instructions.set-active", instruction.id));
};

const deleteInstruction = (instruction) => {
    if (confirm("Are you sure you want to delete this instruction?")) {
        form.delete(route("custom-instructions.destroy", instruction.id));
    }
};

const closeModal = () => {
    showCreateForm.value = false;
    editingInstruction.value = null;
    form.reset();
};
</script>
