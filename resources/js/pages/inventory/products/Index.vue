<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import Table from '@/components/ui/Table.vue';
import CardData from '@/components/ui/CardData.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import {
    Image as ImageIcon,
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import type { ProductsData, Product } from '@/types/product';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';

const props = defineProps<{
    products: ProductsData;
}>();

const viewMode = ref<'list' | 'grid'>('grid');
const selectedProducts = ref<Product[]>([]);
const selectAllAcrossPages = ref(false);
const searchTerm = ref('');

const { notify } = useNotification();
const showDeleteModal = ref(false);
const isDeleting = ref(false);

onMounted(() => {
    const savedViewMode = localStorage.getItem('inventoryViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('inventoryViewMode', newValue);
    if (newValue === 'grid') {
        clearSelection();
    }
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/inventario/productos',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['products'],
            }
        );
    }, 100);
});

const navigationItems = inventoryNavigation;

const headers = [
    { key: 'name', label: 'Nombre' },
    { key: 'sku', label: 'Referencia interna' },
    { key: 'category', label: 'Categoría' },
    { key: 'price', label: 'Precio' },
    { key: 'stock', label: 'Stock' },
    { key: 'updated_at', label: 'Última actualización' },
];

const getStock = (product: any) => {
    return (
        product.variants?.reduce(
            (acc: number, v: any) => acc + (Number(v.stock) || 0),
            0
        ) || 0
    );
};

const getSku = (product: any) => {
    return product.variants?.[0]?.sku || '';
};

const handleRowClick = (product: Product) => {
    router.visit(`/finanzas/inventario/productos/${product.id}/editar`);
};

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedProducts.value = [];
    selectAllAcrossPages.value = false;
};

const totalItems = computed(() => props.products.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} productos seleccionados`;
    }
    const count = selectedProducts.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    const count = selectedProducts.value.length;
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/productos/mass-destroy',
        {
            ids: selectedProducts.value.map((p) => p.id),
        },
        {
            onSuccess: () => {
                selectedProducts.value = [];
                showDeleteModal.value = false;
                isDeleting.value = false;
                notify(
                    count === 1
                        ? 'Producto eliminado correctamente'
                        : `${count} productos eliminados correctamente`,
                    'success'
                );
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar los productos', 'error');
            },
        }
    );
};

const handleGenerateQr = () => {
    const ids = selectedProducts.value.map(p => p.id);
    router.post('/finanzas/inventario/productos/qr-masivo', {
        ids: ids,
        select_all: selectAllAcrossPages.value,
        search: searchTerm.value
    });
};
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Productos" new-route="/finanzas/inventario/productos/crear"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="products"
                :selected-count="selectedProducts.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllTotal"
                @clear-selection="clearSelection" @delete-selected="deleteSelected" @generate-qr="handleGenerateQr"
                :show-qr="true" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="products.data" selectable v-model="selectedProducts"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">
                    <template #cell-image="{ item }">
                        <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden">
                            <img v-if="item.image" :src="item.image" :alt="item.name"
                                class="h-full w-full object-cover" />
                            <ImageIcon v-else class="h-5 w-5 text-gray-400" />
                        </div>
                    </template>

                    <template #cell-name="{ value }">
                        <span class="font-medium text-gray-900">{{ value }}</span>
                    </template>

                    <template #cell-sku="{ item }">
                        <span class="text-gray-600">{{ getSku(item) }}</span>
                    </template>

                    <template #cell-category="{ item }">
                        <span class="text-gray-600">{{
                            item.category?.name || 'Sin categoría'
                            }}</span>
                    </template>

                    <template #cell-stock="{ item }">
                        <span class="text-gray-600">{{ getStock(item) }}</span>
                    </template>

                    <template #cell-updated_at="{ item }">
                        <span class="text-gray-600 text-sm">
                            {{ new Date(item.updated_at).toLocaleString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false
                            }).replace(',', '') }}
                        </span>
                    </template>
                </Table>
            </div>

            <div v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-7 bg-gray-300 h-[0.5px]">
                <CardData v-for="product in products.data" :key="product.id" :item="product"
                    @click="handleRowClick(product)" class="cursor-pointer mt-4" />
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar productos"
            message="¿Estás seguro de que deseas eliminar los productos seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
