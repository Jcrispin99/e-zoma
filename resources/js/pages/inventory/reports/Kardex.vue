<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import { formatDate } from '@/utils/formatDate';
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { PaginatedInventory, Inventory } from '@/types/inventory';
import { useNotification } from '@/hooks/useNotification';

const props = defineProps<{
    inventories: PaginatedInventory;
}>();

const { success } = useNotification();
const navigationItems = inventoryNavigation;
const searchTerm = ref('');
const viewMode = ref<'grid' | 'list'>('list');
const selectedItems = ref<Inventory[]>([]);
const selectAllMatches = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

watch(searchTerm, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/finanzas/inventario/reportes/transacciones', {
            search: value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

watch(selectedItems, () => {
    if (selectedItems.value.length !== props.inventories.data.length && selectAllMatches.value) {
        selectAllMatches.value = false;
    }
});

const headers = [
    { key: 'date', label: 'Fecha' },
    { key: 'warehouse', label: 'Almacén' },
    { key: 'product', label: 'Producto' },
    { key: 'detail', label: 'Detalle' },
    { key: 'in', label: 'Entrada', align: 'right' },
    { key: 'out', label: 'Salida', align: 'right' },
    { key: 'balance', label: 'Saldo', align: 'right' },
];

const handleRowClick = (item: any) => {
    if (item.variant?.product_id) {
        router.visit(`/finanzas/inventario/productos/${item.variant.product_id}/kardex`);
    }
};

const handleSelectAllTotal = () => {
    selectAllMatches.value = true;
    selectedItems.value = [...props.inventories.data];
};

const handleClearSelection = () => {
    selectedItems.value = [];
    selectAllMatches.value = false;
};

const handleExport = () => {
    const selectedIds = selectAllMatches.value ? [] : selectedItems.value.map(item => item.id);
    router.post('/finanzas/inventario/reportes/transacciones/export', {
        selected_ids: selectedIds,
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
            <DataToolbar title="Movimientos (Kardex)" parent-title="Reportes"
                parent-route="/finanzas/inventario/reportes" :hide-view-toggle="true" v-model:search-term="searchTerm"
                v-model:view-mode="viewMode" :pagination="inventories"
                :selected-count="selectAllMatches ? inventories.total : selectedItems.length"
                :is-all-selected="selectAllMatches"
                :selection-message="selectAllMatches ? `${inventories.total} seleccionados (Todos)` : `${selectedItems.length} seleccionados`"
                :total-count="inventories.total" :show-export="selectedItems.length > 0 || selectAllMatches"
                :show-delete="false" @clear-selection="handleClearSelection" @select-all-total="handleSelectAllTotal"
                @export-selected="handleExport" />

            <div class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>
                <Table :headers="headers" :items="inventories.data" :selectable="true" v-model="selectedItems"
                    @row-click="handleRowClick">
                    <template #cell-date="{ item }">
                        <span class="text-gray-500 whitespace-nowrap">{{ formatDate(item.created_at, {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) }}</span>
                    </template>
                    <template #cell-warehouse="{ item }">
                        <span class="text-gray-900">{{ item.warehouse?.name || '-' }}</span>
                    </template>
                    <template #cell-product="{ item }">
                        <div class="font-medium text-gray-900">{{ item.variant?.product?.name || 'Desconocido' }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-[200px]">
                            {{item.variant?.attributeValues?.map((v: any) => v.value).join(' / ')}}
                        </div>
                    </template>
                    <template #cell-detail="{ item }">
                        <span class="text-gray-500">{{ item.detail }}</span>
                    </template>
                    <template #cell-in="{ item }">
                        <span class="font-medium text-green-700">
                            {{ item.quantity_in ? `+${item.quantity_in}` : '-' }}
                        </span>
                    </template>
                    <template #cell-out="{ item }">
                        <span class="font-medium text-red-700">
                            {{ item.quantity_out ? `-${item.quantity_out}` : '-' }}
                        </span>
                    </template>
                    <template #cell-balance="{ item }">
                        <span class="font-bold text-gray-800">{{ item.quantity_balance }}</span>
                    </template>
                </Table>
            </div>
        </div>
    </ModuleLayout>
</template>
