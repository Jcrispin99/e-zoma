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
const perPage = 10;
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
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Buscar categoría</h3>
                            <p class="text-sm text-gray-500 mt-1">Selecciona una categoría de la lista</p>
                        </div>
                        <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 border-b border-gray-200">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="Buscar por nombre..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <div v-if="isLoading" class="p-8 text-center text-gray-500">
                            Cargando categorías...
                        </div>

                        <div v-else-if="paginatedCategories.length === 0" class="p-8 text-center text-gray-500">
                            No se encontraron categorías
                        </div>

                        <table v-else class="w-full">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Categoría
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="category in paginatedCategories" :key="category.id"
                                    class="hover:bg-gray-50 transition-colors cursor-pointer"
                                    :class="{ 'bg-teal-50': category.id === selectedCategoryId }"
                                    @click="selectCategory(category)">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ getCategoryFullName(category) }}
                                        </div>
                                        <div v-if="category.description" class="text-xs text-gray-500 mt-1">
                                            {{ category.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Button variant="secondary" size="sm" @click.stop="selectCategory(category)">
                                            Seleccionar
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="totalPages > 1"
                        class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ (currentPage - 1) * perPage + 1 }} a
                            {{ Math.min(currentPage * perPage, filteredCategories.length) }} de
                            {{ filteredCategories.length }} categorías
                        </div>

                        <div class="flex gap-2">
                            <Button variant="secondary" size="sm" :disabled="currentPage === 1"
                                @click="goToPage(currentPage - 1)">
                                Anterior
                            </Button>

                            <div class="hidden sm:flex gap-1">
                                <button v-for="page in totalPages" :key="page" @click="goToPage(page)"
                                    class="px-3 py-1 text-sm rounded transition-colors" :class="page === currentPage
                                        ? 'bg-teal-500 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100'">
                                    {{ page }}
                                </button>
                            </div>

                            <Button variant="secondary" size="sm" :disabled="currentPage === totalPages"
                                @click="goToPage(currentPage + 1)">
                                Siguiente
                            </Button>
                        </div>
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