<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import { RefreshCw, Eye } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import { useNotification } from '@/hooks/useNotification';
import { PaginatedVariants, Variant } from '@/types/product';

const { success } = useNotification();

const props = defineProps<{
    variants: PaginatedVariants;
    threshold: number | string;
}>();

const navigationItems = inventoryNavigation;
const currentThreshold = ref(Number(props.threshold));
const searchTerm = ref('');
const viewMode = ref<'grid' | 'list'>('list');
const selectedItems = ref<Variant[]>([]);
const selectAllMatches = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

watch(searchTerm, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/finanzas/inventario/reportes/bajo-stock', {
            threshold: currentThreshold.value,
            search: value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

watch(selectedItems, () => {
    if (selectedItems.value.length !== props.variants.data.length && selectAllMatches.value) {
        selectAllMatches.value = false;
    }
});

const headers = [
    { key: 'product', label: 'Producto' },
    { key: 'variant', label: 'Variante' },
    { key: 'sku', label: 'SKU' },
    { key: 'stock', label: 'Stock Actual', align: 'right' },
];

const updateReport = () => {
    router.get('/finanzas/inventario/reportes/bajo-stock', {
        threshold: currentThreshold.value,
        search: searchTerm.value
    }, { preserveState: true, replace: true });
};

const handleRowClick = (variant: any) => {
    router.visit(`/finanzas/inventario/productos/${variant.product.id}/editar`);
};

const handleSelectAllTotal = () => {
    selectAllMatches.value = true;
    selectedItems.value = [...props.variants.data];
};

const handleClearSelection = () => {
    selectedItems.value = [];
    selectAllMatches.value = false;
};

const handleExport = () => {
    const selectedIds = selectAllMatches.value ? [] : selectedItems.value.map(item => item.id);
    router.post('/finanzas/inventario/reportes/bajo-stock/export', {
        selected_ids: selectedIds,
        threshold: currentThreshold.value,
        search: searchTerm.value
    }, {
        onSuccess: () => {
            success('Reporte exportado correctamente');
            const page = usePage();
            const downloadUrl = (page.props.flash as any).download_url;
            if (downloadUrl) {
                window.open(downloadUrl, '_blank');
            }
        }
    });
};
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Stock Bajo" parent-title="Reportes" parent-route="/finanzas/inventario/reportes"
                :hide-view-toggle="true" v-model:search-term="searchTerm" v-model:view-mode="viewMode"
                :pagination="variants" :selected-count="selectAllMatches ? variants.total : selectedItems.length"
                :is-all-selected="selectAllMatches"
                :selection-message="selectAllMatches ? `${variants.total} seleccionados (Todos)` : `${selectedItems.length} seleccionados`"
                :total-count="variants.total" :show-export="selectedItems.length > 0 || selectAllMatches"
                :show-delete="false" @clear-selection="handleClearSelection" @select-all-total="handleSelectAllTotal"
                @export-selected="handleExport">
                <template #toolbar-end>
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-2 py-1 shadow-sm">
                        <span class="text-sm text-gray-500 font-medium">Umbral:</span>
                        <input v-model="currentThreshold" type="number"
                            class="w-16 border-none p-0 text-right focus:ring-0 font-bold text-gray-800"
                            @keydown.enter="updateReport" />
                    </div>
                    <Button @click="updateReport" variant="primary" size="sm">
                        <RefreshCw class="w-4 h-4 mr-2" />
                        Actualizar
                    </Button>
                </template>
            </DataToolbar>

            <div class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>
                <Table :headers="headers" :items="variants.data" :selectable="true" v-model="selectedItems"
                    @row-click="handleRowClick">
                    <template #cell-product="{ item }">
                        <span class="font-medium text-gray-900">{{ item.product.name }}</span>
                    </template>
                    <template #cell-variant="{ item }">
                        <span class="text-gray-500">
                            {{item.attribute_values?.map((v: any) => v.value).join(' / ') || '-'}}
                        </span>
                    </template>
                    <template #cell-sku="{ item }">
                        <span class="text-gray-500 font-mono text-xs">{{ item.sku }}</span>
                    </template>
                    <template #cell-stock="{ item }">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ item.stock }}
                        </span>
                    </template>
                </Table>
            </div>
        </div>
    </ModuleLayout>
</template>
