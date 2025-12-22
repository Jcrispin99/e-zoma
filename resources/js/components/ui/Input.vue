<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { ChevronDown, Loader2 } from 'lucide-vue-next';

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
    disableLocalFilter?: boolean;
    searchDebounce?: number;
    loading?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'enter', 'search-more', 'search']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref({});
let searchTimeout: ReturnType<typeof setTimeout>;

const initializeSearchQuery = () => {
    if (props.options) {
        if (props.modelValue || props.modelValue === 0 || props.modelValue === '') {
            const selectedOption = props.options.find(
                (opt) => opt.value === props.modelValue
            );
            if (selectedOption) {
                searchQuery.value = selectedOption.label;
            } else if (props.allowCustom) {
                searchQuery.value = String(props.modelValue);
            } else if (props.modelValue === '') {
                searchQuery.value = '';
            }
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

    if (props.disableLocalFilter) return props.options;

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

    if (props.showSearchMore) {
        return [...opts, { value: '__search_more__', label: 'Buscar más' }];
    }

    return opts;
});

const calculatePosition = async () => {
    if (!containerRef.value) return;
    await nextTick();
    const rect = containerRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - rect.bottom;
    const height = 250;

    let style: any = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        maxHeight: '240px',
        zIndex: 9999
    };

    if (spaceBelow < height && rect.top > spaceBelow) {
        style.bottom = `${viewportHeight - rect.top + 4}px`;
        style.top = 'auto';
    } else {
        style.top = `${rect.bottom + 4}px`;
        style.bottom = 'auto';
    }

    dropdownStyle.value = style;
};

watch(isOpen, (newVal) => {
    if (newVal) {
        calculatePosition();
        window.addEventListener('scroll', updatePosition, true);
        window.addEventListener('resize', updatePosition);
    } else {
        window.removeEventListener('scroll', updatePosition, true);
        window.removeEventListener('resize', updatePosition);
    }
});

const updatePosition = () => {
    if (isOpen.value) calculatePosition();
};

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

        if (props.disableLocalFilter || props.options.length === 0) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                emit('search', val);
            }, props.searchDebounce ?? 300);
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
    }, 200);
};

const handleClickOutside = (e: MouseEvent) => {
    const target = e.target as Node;
    const isContainer = containerRef.value && containerRef.value.contains(target);
    const isDropdown = dropdownRef.value && dropdownRef.value.contains(target);

    if (!isContainer && !isDropdown) {
        if (isOpen.value) {
            isOpen.value = false;
        }
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
});
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

            <div v-if="options" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 transition-colors"
                :class="{ 'rotate-180': isOpen, 'group-hover:text-teal-600 pointer-events-none': !disabled }">
                <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                <ChevronDown v-else class="w-4 h-4" />
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isOpen && options" ref="dropdownRef" :style="dropdownStyle"
                class="fixed bg-white border border-gray-200 rounded-md shadow-lg overflow-auto py-1">
                <div v-if="loading" class="px-4 py-3 text-sm text-gray-500 flex items-center justify-center gap-2">
                    <Loader2 class="w-4 h-4 animate-spin text-teal-600" />
                    Buscando
                </div>
                <ul v-else-if="displayOptions.length > 0">
                    <li v-for="option in displayOptions" :key="option.value" @click="selectOption(option)"
                        class="px-4 py-2 text-sm cursor-pointer flex items-center justify-between transition-colors"
                        :class="[
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
                    class="px-4 py-2 text-sm text-gray-500 italic cursor-pointer hover:bg-teal-50"
                    @click="isOpen = false">
                    Usar "{{ searchQuery }}"
                </div>
                <div v-else class="px-4 py-2 text-sm text-gray-500 italic">
                    No se encontraron resultados.
                </div>
            </div>
        </Teleport>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>