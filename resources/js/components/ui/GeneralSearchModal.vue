<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X, Search, Loader2 } from 'lucide-vue-next';
import Button from './Button.vue';
import type { Category } from '@/types/product';
import axios from 'axios';

interface Props {
    modelValue: boolean;
    type?: 'category' | 'product';
    selectedId?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'category'
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    'select': [item: any];
}>();

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 40;

const categories = ref<Category[]>([]);
const products = ref<any[]>([]);
const total = ref(0);
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const displayItems = computed(() => {
    if (props.type === 'category') {
        return categories.value;
    } else {
        return products.value;
    }
});

const totalPages = computed(() => {
    return Math.ceil(total.value / perPage);
});


const searchCategories = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post('/api/categories/search', {
            search: searchQuery.value,
            page: currentPage.value
        });
        categories.value = response.data.data;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error searching categories:', error);
        categories.value = [];
        total.value = 0;
    } finally {
        isLoading.value = false;
    }
};

const searchProducts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post('/api/product/search', {
            search: searchQuery.value,
            page: currentPage.value
        });
        products.value = response.data.data;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error searching products:', error);
        products.value = [];
        total.value = 0;
    } finally {
        isLoading.value = false;
    }
};

watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        searchQuery.value = '';
        currentPage.value = 1;
        if (props.type === 'category') {
            searchCategories();
        } else {
            searchProducts();
        }
    }
});

watch(searchQuery, () => {
    currentPage.value = 1;
    if (searchTimeout) clearTimeout(searchTimeout);

    if (props.type === 'category') {
        searchTimeout = setTimeout(searchCategories, 300);
    } else {
        searchTimeout = setTimeout(searchProducts, 300);
    }
});

watch(currentPage, () => {
    if (!props.modelValue) return;

    if (props.type === 'category') {
        searchCategories();
    } else {
        searchProducts();
    }
});

const getItemName = (item: any): string => {
    if (props.type === 'category') {
        if (item.full_name) return item.full_name;
        if (item.parent) return `${item.parent.name} / ${item.name}`;
        return item.name;
    } else {
        return item.name;
    }
};

const getItemSubtext = (item: any): string => {
    if (props.type === 'product') {
        return `SKU: ${item.sku || '-'} | Stock: ${item.stock || 0} | Precio: S/ ${Number(item.price || 0).toFixed(2)}`;
    }
    return '';
};

const selectItem = (item: any) => {
    emit('select', item);
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

const title = computed(() => props.type === 'category' ? 'Buscar: Categoría del producto' : 'Buscar: Producto');
const subTitle = computed(() => props.type === 'category' ? 'Categoría' : 'Producto');

</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close"></div>

                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                        <h3 class="text-base font-medium text-gray-900">{{ title }}</h3>
                        <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="Buscar" autofocus
                                class="w-full pl-10 pr-4 py-1.5 text-sm border border-gray-300 rounded focus:ring-[0.5px] focus:ring-gray-500 focus:border-gray-500" />
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="text-sm text-gray-600">
                                {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage,
                                    type === 'category' ? categories.length : total) }}
                                / {{ type === 'category' ? categories.length : total }}
                            </div>
                            <div class="flex gap-1">
                                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                    class="p-1 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-normal transition-colors">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                    class="p-1 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-normal transition-colors">
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
                        <div v-if="isLoading"
                            class="p-8 text-center text-gray-500 flex flex-col items-center justify-center">
                            <Loader2 class="w-8 h-8 mb-2 animate-spin text-teal-600" />
                            Cargando
                        </div>

                        <div v-else-if="displayItems.length === 0" class="p-8 text-center text-gray-500">
                            No se encontraron resultados
                        </div>

                        <div v-else>
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                <h4 class="text-xs font-medium text-gray-700 uppercase">{{ subTitle }}</h4>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <div v-for="item in displayItems" :key="item.id"
                                    class="px-4 py-2.5 hover:bg-gray-50 transition-colors cursor-pointer relative"
                                    :class="{ 'bg-blue-50': item.id === selectedId }" @click="selectItem(item)">
                                    <div class="text-sm text-gray-900 font-medium">
                                        {{ getItemName(item) }}
                                    </div>
                                    <div v-if="getItemSubtext(item)" class="text-xs text-gray-500">
                                        {{ getItemSubtext(item) }}
                                    </div>
                                    <div v-if="item.id === selectedId"
                                        class="absolute top-1/2 right-4 -translate-y-1/2 bg-black text-white text-xs px-2 py-1 rounded">
                                        Seleccionado
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-lg">
                        <Button variant="secondary" size="md" @click="close">
                            Cerrar
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
