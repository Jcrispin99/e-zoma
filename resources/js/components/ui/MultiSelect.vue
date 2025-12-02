<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown, X } from 'lucide-vue-next';

const props = defineProps<{
    modelValue?: (string | number)[];
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    options?: { value: string | number; label: string }[];
    allowCustom?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

const selectedLabels = computed(() => {
    if (!props.modelValue) return [];
    return props.modelValue.map(val => {
        const opt = props.options?.find(o => o.value === val);
        return opt ? opt.label : String(val);
    });
});

const filteredOptions = computed(() => {
    if (!props.options) return [];
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt =>
        !props.modelValue?.includes(opt.value) &&
        opt.label.toLowerCase().includes(query)
    );
});

const handleInput = (e: Event) => {
    searchQuery.value = (e.target as HTMLInputElement).value;
    isOpen.value = true;
};

const addValue = (val: string | number) => {
    const newValue = [...(props.modelValue || [])];
    if (!newValue.includes(val)) {
        newValue.push(val);
        emit('update:modelValue', newValue);
    }
    searchQuery.value = '';
    inputRef.value?.focus();
};

const removeValue = (index: number) => {
    const newValue = [...(props.modelValue || [])];
    newValue.splice(index, 1);
    emit('update:modelValue', newValue);
};

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (searchQuery.value) {
            if (props.allowCustom) {
                addValue(searchQuery.value);
            } else if (filteredOptions.value.length > 0) {
                addValue(filteredOptions.value[0].value);
            }
        }
    } else if (e.key === 'Backspace' && !searchQuery.value && (props.modelValue?.length || 0) > 0) {
        removeValue((props.modelValue?.length || 0) - 1);
    }
};

const selectOption = (option: { value: string | number; label: string }) => {
    addValue(option.value);
};

const handleBlur = () => {
    if (searchQuery.value) {
        if (props.allowCustom) {
            addValue(searchQuery.value);
        } else if (props.options) {
            const match = props.options.find(
                (opt) => opt.label.toLowerCase() === searchQuery.value.toLowerCase()
            );
            if (match) {
                addValue(match.value);
            } else {
                searchQuery.value = '';
            }
        }
    }
    setTimeout(() => {
        isOpen.value = false;
    }, 200);
};

const handleClickOutside = (e: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        if (isOpen.value) {
            handleBlur();
        }
    }
};

const focusInput = () => {
    inputRef.value?.focus();
    isOpen.value = true;
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="w-full relative" ref="containerRef">
        <div class="relative group min-h-[38px] border-b border-gray-300 focus-within:border-teal-500 transition-colors bg-transparent flex flex-wrap items-center gap-1 py-1 pr-6 cursor-text"
            :class="{ 'border-red-500 focus-within:border-red-500': error }" @click="focusInput">

            <span v-for="(label, index) in selectedLabels" :key="index"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-700 border border-teal-100">
                {{ label }}
                <button type="button" @click.stop="removeValue(index)"
                    class="ml-1 text-teal-400 hover:text-teal-600 focus:outline-none">
                    <X class="w-3 h-3" />
                </button>
            </span>

            <input ref="inputRef" v-model="searchQuery" @input="handleInput" @keydown="handleKeydown"
                @focus="isOpen = true" @blur="handleBlur" :placeholder="selectedLabels.length === 0 ? placeholder : ''"
                :disabled="disabled"
                class="flex-1 min-w-[60px] border-none p-0 focus:ring-0 text-sm bg-transparent placeholder-gray-300 text-gray-900 h-6" />

            <div class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none group-hover:text-teal-600 transition-colors"
                :class="{ 'rotate-180': isOpen }">
                <ChevronDown class="w-4 h-4" />
            </div>
        </div>

        <div v-if="isOpen && (filteredOptions.length > 0 || (allowCustom && searchQuery))"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto py-1">
            <ul v-if="filteredOptions.length > 0">
                <li v-for="option in filteredOptions" :key="option.value" @click="selectOption(option)"
                    class="px-4 py-2 text-sm text-gray-700 hover:bg-teal-50 hover:text-teal-700 cursor-pointer">
                    {{ option.label }}
                </li>
            </ul>
            <div v-if="allowCustom && searchQuery && !filteredOptions.find(o => o.label.toLowerCase() === searchQuery.toLowerCase())"
                class="px-4 py-2 text-sm text-gray-500 italic cursor-pointer hover:bg-teal-50"
                @click="addValue(searchQuery)">
                Usar "{{ searchQuery }}"
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
