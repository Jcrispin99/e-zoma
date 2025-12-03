<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X, Search } from 'lucide-vue-next';
import Button from './Button.vue';
import type { Category } from '@/types/product';

interface Props {
    modelValue: boolean;
    selectedCategoryId?: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    'select': [category: Category];
}>();

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 40;
const categories = ref<Category[]>([]);
const total = ref(0);
const isLoading = ref(false);

const filteredCategories = computed(() => {
    if (!searchQuery.value) {
        return categories.value;
    }
    return categories.value.filter(c =>
        c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        c.full_name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;
    return filteredCategories.value.slice(start, end);
});

const totalPages = computed(() =>
    Math.ceil(filteredCategories.value.length / perPage)
);

const loadCategories = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/api/categories');
        const data = await response.json();
        categories.value = data;
        total.value = data.length;
    } catch (error) {
        console.error('Error loading categories:', error);
    } finally {
        isLoading.value = false;
    }
};

watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        loadCategories();
        searchQuery.value = '';
        currentPage.value = 1;
    }
});

watch(searchQuery, () => {
    currentPage.value = 1;
});

const getCategoryFullName = (category: Category): string => {
    if (category.full_name) {
        return category.full_name;
    }
    if (category.parent) {
        return `${category.parent.name} / ${category.name}`;
    }
    return category.name;
};

const selectCategory = (category: Category) => {
    emit('select', category);
    close();
};

const close = () => {
    emit('update:modelValue', false);
};

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};
</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close"></div>

                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                        <h3 class="text-base font-medium text-gray-900">Buscar: Categoría del producto</h3>
                        <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="Buscar..."
                                class="w-full pl-10 pr-4 py-1.5 text-sm border border-gray-300 rounded focus:ring-[0.5px] focus:ring-gray-500 focus:border-gray-500" />
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="text-sm text-gray-600">
                                {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage,
                                filteredCategories.length) }} / {{ filteredCategories.length }}
                            </div>
                            <div class="flex gap-1">
                                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                    class="p-1 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                    class="p-1 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <div v-if="isLoading" class="p-8 text-center text-gray-500">
                            Cargando categorías...
                        </div>

                        <div v-else-if="paginatedCategories.length === 0" class="p-8 text-center text-gray-500">
                            No se encontraron categorías
                        </div>

                        <div v-else>
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                <h4 class="text-xs font-medium text-gray-700 uppercase">Categoría del producto</h4>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <div v-for="category in paginatedCategories" :key="category.id"
                                    class="px-4 py-2.5 hover:bg-gray-50 transition-colors cursor-pointer relative"
                                    :class="{ 'bg-blue-50': category.id === selectedCategoryId }"
                                    @click="selectCategory(category)">
                                    <div class="text-sm text-gray-900">
                                        {{ getCategoryFullName(category) }}
                                    </div>
                                    <div v-if="category.id === selectedCategoryId"
                                        class="absolute top-1/2 right-4 -translate-y-1/2 bg-black text-white text-xs px-2 py-1 rounded">
                                        {{ getCategoryFullName(category) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <Button variant="secondary" size="md" @click="close">
                            Cerrar
                        </Button>
                        <Button variant="primary" size="md">
                            Nuevo
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>