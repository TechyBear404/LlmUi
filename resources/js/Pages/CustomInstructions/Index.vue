<template>
    <AppLayout title="Custom Instructions">
        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with Search -->
            <div class="rounded-lg shadow bg-slate-900">
                <div class="p-6">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2 class="text-xl font-semibold text-white">
                            Custom Instructions
                        </h2>
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-center"
                        >
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search instructions..."
                                    class="w-full pl-10 text-white bg-gray-800 border-gray-700 rounded-md"
                                />
                                <font-awesome-icon
                                    icon="fa-solid fa-search"
                                    class="absolute text-gray-400 transform -translate-y-1/2 left-3 top-1/2"
                                />
                            </div>
                            <select
                                v-model="selectedDomain"
                                class="text-white bg-gray-800 border-gray-700 rounded-md"
                            >
                                <option value="">All Domains</option>
                                <option
                                    v-for="domain in domains"
                                    :key="domain.id"
                                    :value="domain.id"
                                >
                                    {{ domain.name }}
                                </option>
                            </select>
                            <button
                                @click="showCreateForm = true"
                                class="flex items-center gap-2 px-4 py-2 text-white transition-colors bg-purple-600 rounded-lg hover:bg-purple-700"
                            >
                                <font-awesome-icon icon="fa-solid fa-plus" />
                                <span>New Instruction</span>
                            </button>
                        </div>
                    </div>

                    <!-- Instructions Grid -->
                    <div
                        v-if="filteredInstructions.length"
                        class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="instruction in filteredInstructions"
                            :key="instruction.id"
                            class="relative p-4 transition-all bg-gray-800 rounded-lg group hover:bg-gray-750"
                        >
                            <div class="flex items-start justify-between">
                                <h4 class="mb-2 font-medium text-white">
                                    {{ instruction.name }}
                                </h4>
                                <div class="flex space-x-2">
                                    <button
                                        @click="editInstruction(instruction)"
                                        class="p-1.5 text-gray-400 hover:text-purple-500 rounded"
                                        :title="'Edit ' + instruction.name"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-pen"
                                        />
                                    </button>

                                    <button
                                        @click="confirmDelete(instruction)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 rounded"
                                        :title="'Delete ' + instruction.name"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-trash"
                                        />
                                    </button>
                                </div>
                            </div>

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
                                <div
                                    v-if="instruction.settings?.length"
                                    class="text-gray-300"
                                >
                                    <p class="mb-1 font-medium">Settings:</p>
                                    <div
                                        v-for="setting in instruction.settings"
                                        :key="setting.id"
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <span class="text-gray-400"
                                            >{{
                                                setting.setting_type?.name
                                            }}:</span
                                        >
                                        <span>{{
                                            setting.selected_option?.name
                                        }}</span>
                                        <span
                                            v-if="setting.custom_value"
                                            class="text-gray-400"
                                        >
                                            ({{ setting.custom_value }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mt-6 text-center text-gray-400">
                        No instructions found
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
                            :class="{ 'border-red-500': form.errors.name }"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300">
                            What would you like the AI to know about you?
                        </label>
                        <textarea
                            v-model="form.about_user"
                            rows="4"
                            class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            :class="{
                                'border-red-500': form.errors.about_user,
                            }"
                            required
                        ></textarea>
                        <p
                            v-if="form.errors.about_user"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.about_user }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300">
                            How would you like the AI to respond?
                        </label>
                        <textarea
                            v-model="form.ai_response_style"
                            rows="4"
                            class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            :class="{
                                'border-red-500': form.errors.ai_response_style,
                            }"
                            required
                        ></textarea>
                        <p
                            v-if="form.errors.ai_response_style"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.ai_response_style }}
                        </p>
                    </div>

                    <!-- Settings Section -->
                    <div v-if="settingTypes.length > 0">
                        <h4 class="mb-3 text-sm font-medium text-gray-300">
                            Additional Settings
                        </h4>
                        <div
                            v-for="settingType in settingTypes"
                            :key="settingType.id"
                            class="mb-4"
                        >
                            <label
                                class="block text-sm font-medium text-gray-300"
                            >
                                {{ settingType.name }}
                            </label>
                            <select
                                v-if="form.settings[settingType.id]"
                                v-model="
                                    form.settings[settingType.id].option_id
                                "
                                class="w-full mt-1 text-white bg-gray-800 border-gray-700 rounded-md"
                            >
                                <option
                                    v-for="option in settingType.options"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </option>
                            </select>
                            <input
                                v-if="form.settings[settingType.id]"
                                v-model="
                                    form.settings[settingType.id].custom_value
                                "
                                type="text"
                                class="w-full mt-2 text-white bg-gray-800 border-gray-700 rounded-md"
                                placeholder="Custom value (optional)"
                            />
                        </div>
                    </div>

                    <!-- Domains Section -->
                    <div v-if="domains.length > 0">
                        <h4 class="mb-3 text-sm font-medium text-gray-300">
                            Applicable Domains
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="domain in domains"
                                :key="domain.id"
                                class="flex items-center"
                            >
                                <input
                                    type="checkbox"
                                    :id="'domain-' + domain.id"
                                    v-model="form.domains"
                                    :value="domain.id"
                                    class="text-purple-600 bg-gray-800 border-gray-700 rounded"
                                />
                                <label
                                    :for="'domain-' + domain.id"
                                    class="ml-2 text-sm text-gray-300"
                                >
                                    {{ domain.name }}
                                </label>
                            </div>
                        </div>
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
import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    instructions: {
        type: Array,
        required: true,
    },
    settingTypes: {
        type: Array,
        default: () => [],
    },
    domains: {
        type: Array,
        default: () => [],
    },
});

const showCreateForm = ref(false);
const editingInstruction = ref(null);
const searchQuery = ref("");
const selectedDomain = ref("");

const form = useForm({
    name: "",
    about_user: "",
    ai_response_style: "",
    settings: {},
    domains: [],
});

const filteredInstructions = computed(() => {
    let filtered = [...props.instructions];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (inst) =>
                inst.name
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                inst.about_user
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                inst.ai_response_style
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedDomain.value) {
        filtered = filtered.filter((inst) =>
            inst.domains.some((d) => d.id === parseInt(selectedDomain.value))
        );
    }

    return filtered;
});

onMounted(() => {
    initializeSettings();
});

watch(showCreateForm, (newValue) => {
    if (newValue && !editingInstruction.value) {
        initializeSettings();
    }
});

const initializeSettings = () => {
    if (!props.settingTypes) return;

    const newSettings = {};
    props.settingTypes.forEach((type) => {
        if (type && type.id) {
            newSettings[type.id] = {
                option_id: type.options?.[0]?.id || null,
                custom_value: null,
            };
        }
    });
    form.settings = newSettings;
};

const submitForm = () => {
    if (editingInstruction.value) {
        form.put(
            route("custom-instructions.update", editingInstruction.value.id),
            {
                onSuccess: () => closeModal(),
                onError: () =>
                    console.error("Form submission error:", form.errors),
            }
        );
    } else {
        form.post(route("custom-instructions.store"), {
            onSuccess: () => closeModal(),
            onError: () => console.error("Form submission error:", form.errors),
        });
    }
};

const editInstruction = (instruction) => {
    editingInstruction.value = instruction;
    form.name = instruction.name;
    form.about_user = instruction.about_user;
    form.ai_response_style = instruction.ai_response_style;
    form.domains = instruction.domains.map((d) => d.id);

    initializeSettings();

    if (instruction.settings) {
        instruction.settings.forEach((setting) => {
            if (setting && setting.setting_type_id) {
                form.settings[setting.setting_type_id] = {
                    option_id: setting.setting_option_id,
                    custom_value: setting.custom_value,
                };
            }
        });
    }

    showCreateForm.value = true;
};

const confirmDelete = (instruction) => {
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
