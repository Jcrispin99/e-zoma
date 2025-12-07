<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    inputClass?: string;
    options?: { value: string | number; label: string }[];
    allowCustom?: boolean;
    showSearchMore?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'enter', 'search-more']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);

const initializeSearchQuery = () => {
    if (props.options && (props.modelValue || props.modelValue === 0 || props.modelValue === '')) {
        const selectedOption = props.options.find(
            (opt) => opt.value === props.modelValue
        );
        if (selectedOption) {
            searchQuery.value = selectedOption.label;
        } else if (props.allowCustom) {
            searchQuery.value = String(props.modelValue);
        }
    } else if (props.modelValue || props.modelValue === 0) {
        searchQuery.value = String(props.modelValue);
    }
};

watch(() => props.modelValue, initializeSearchQuery);
onMounted(initializeSearchQuery);

const filteredOptions = computed(() => {
    if (!props.options) return [];
    if (!searchQuery.value) return props.options;

    if (props.modelValue !== undefined && props.modelValue !== null) {
        const selectedOption = props.options.find(opt => opt.value === props.modelValue);
        if (selectedOption && searchQuery.value === selectedOption.label) {
            return props.options;
        }
    }

    const query = searchQuery.value.toLowerCase();
    return props.options.filter((opt) => opt.label.toLowerCase().includes(query));
});

const displayOptions = computed(() => {
    const opts = filteredOptions.value;

    if (props.showSearchMore && !searchQuery.value && opts.length > 0) {
        return [...opts, { value: '__search_more__', label: 'Buscar más' }];
    }

    return opts;
});

const handleInput = (e: Event) => {
    const val = (e.target as HTMLInputElement).value;
    searchQuery.value = val;

    if (props.options) {
        isOpen.value = true;
        if (!val) {
            emit('update:modelValue', '');
        } else if (props.allowCustom) {
            emit('update:modelValue', val);
        }
    } else {
        emit('update:modelValue', val);
    }
};

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        emit('enter', searchQuery.value);
        if (props.options && props.allowCustom) {
            isOpen.value = false;
        }
    }
};

const selectOption = (option: { value: string | number; label: string }) => {
    if (option.value === '__search_more__') {
        emit('search-more');
        isOpen.value = false;
        return;
    }

    emit('update:modelValue', option.value);
    searchQuery.value = option.label;
    isOpen.value = false;
};

const handleBlur = () => {
    if (props.options && !props.allowCustom) {
        const match = props.options.find(
            (opt) => opt.label.toLowerCase() === searchQuery.value.toLowerCase()
        );

        if (match) {
            emit('update:modelValue', match.value);
            searchQuery.value = match.label;
        } else {
            initializeSearchQuery();
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

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="w-full relative" ref="containerRef">
        <div class="relative group">
            <input :value="options ? searchQuery : modelValue" @input="handleInput" @focus="options && (isOpen = true)"
                @keydown="handleKeydown" @blur="handleBlur" :type="type || 'text'" :placeholder="placeholder"
                :disabled="disabled"
                class="w-full border-0 border-b border-gray-300 focus:border-teal-500 focus:ring-0 px-0 py-2 bg-transparent transition-colors placeholder-gray-300 text-gray-900"
                :class="[
                    inputClass || 'text-base font-normal',
                    { 'border-red-500 focus:border-red-500': error },
                    { 'cursor-pointer': options && !isOpen },
                    { 'disabled:text-gray-400': disabled },
                ]" />

            <div v-if="options"
                class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none group-hover:text-teal-600 transition-colors"
                :class="{ 'rotate-180': isOpen }">
                <ChevronDown class="w-4 h-4" />
            </div>
        </div>

        <div v-if="isOpen && options"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto py-1">
            <ul v-if="displayOptions.length > 0">
                <li v-for="option in displayOptions" :key="option.value" @click="selectOption(option)"
                    class="px-4 py-2 text-sm cursor-pointer flex items-center justify-between transition-colors" :class="[
                        option.value === '__search_more__'
                            ? 'text-teal-600 hover:bg-teal-50 font-medium border-t border-gray-200'
                            : 'text-gray-700 hover:bg-teal-50 hover:text-teal-700',
                        {
                            'bg-teal-50 text-teal-700 font-medium': modelValue === option.value && option.value !== '__search_more__',
                        }
                    ]">
                    {{ option.label }}
                    <span v-if="modelValue === option.value && option.value !== '__search_more__'"
                        class="text-teal-600 text-xs">✓</span>
                </li>
            </ul>
            <div v-else-if="allowCustom && searchQuery"
                class="px-4 py-2 text-sm text-gray-500 italic cursor-pointer hover:bg-teal-50" @click="isOpen = false">
                Usar "{{ searchQuery }}"
            </div>
            <div v-else class="px-4 py-2 text-sm text-gray-500 italic">
                No se encontraron resultados.
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>