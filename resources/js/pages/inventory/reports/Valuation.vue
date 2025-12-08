<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { DollarSign, AlertTriangle } from 'lucide-vue-next';
import StatCard from '@/components/ui/dashboard/StatCard.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useNotification } from '@/hooks/useNotification';
import { Variant, PaginatedVariants } from '@/types/product';

const { success } = useNotification();

const props = defineProps<{
    totalValuation: number | string;
    lowStockCount: number;
    variants: PaginatedVariants;
}>();

const navigationItems = inventoryNavigation;
const searchTerm = ref('');
const viewMode = ref<'grid' | 'list'>('list');
const selectedItems = ref<Variant[]>([]);

let searchTimeout: ReturnType<typeof setTimeout>;

watch(searchTerm, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/finanzas/inventario/reportes/valorizacion', {
            search: value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

const headers = [
    { key: 'product', label: 'Producto' },
    { key: 'sku', label: 'SKU' },
    { key: 'stock', label: 'Stock', align: 'right' },
    { key: 'price', label: 'Precio Unit.', align: 'right' },
    { key: 'valuation', label: 'Valor Total', align: 'right' },
];

const handleSelectAllTotal = () => {
    selectedItems.value = [...props.variants.data];
};

const handleClearSelection = () => {
    selectedItems.value = [];
};

const handleExport = () => {
    const selectedIds = selectedItems.value.map(item => item.id);
    router.post('/finanzas/inventario/reportes/valorizacion/export', {
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

const handleRowClick = (variant: any) => {
    router.visit(`/finanzas/inventario/productos/${variant.product.id}/editar`);
};
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Valorización" parent-title="Reportes" parent-route="/finanzas/inventario/reportes"
                :hide-view-toggle="true" v-model:search-term="searchTerm" v-model:view-mode="viewMode"
                :pagination="variants" :selected-count="selectedItems.length"
                :selection-message="`${selectedItems.length} seleccionados`" :total-count="variants.total"
                :show-export="selectedItems.length > 0" :show-delete="false" @clear-selection="handleClearSelection"
                @select-all-total="handleSelectAllTotal" @export-selected="handleExport">
            </DataToolbar>

            <div class="bg-gray-300 h-[0.5px]"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-4 sm:px-7">
                <StatCard title="Valor Total del Inventario" :value="totalValuation" :icon="DollarSign"
                    icon-class="text-green-600" :value-prefix="'$'">
                    <template #value>
                        {{ Number(totalValuation).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' }) }}
                    </template>
                </StatCard>

                <StatCard title="Items con Stock Bajo" :value="lowStockCount" :icon="AlertTriangle"
                    icon-class="text-red-600" />
            </div>

            <div class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>
                <Table :headers="headers" :items="variants.data" :selectable="true" v-model="selectedItems"
                    @row-click="handleRowClick">
                    <template #cell-product="{ item }">
                        <span class="font-medium text-gray-900">
                            {{ item.product.name }}
                            <template v-if="item.attribute_values?.length">
                                - {{item.attribute_values.map((v: any) => v.value).join(' / ')}}
                            </template>
                        </span>
                    </template>
                    <template #cell-sku="{ item }">
                        <span class="text-gray-500 font-mono text-xs">{{ item.sku }}</span>
                    </template>
                    <template #cell-stock="{ item }">
                        {{ item.stock }}
                    </template>
                    <template #cell-price="{ item }">
                        {{ Number(item.price).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' }) }}
                    </template>
                    <template #cell-valuation="{ item }">
                        <span class="font-bold text-gray-800">
                            {{ Number(item.valuation).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' }) }}
                        </span>
                    </template>
                </Table>
            </div>
        </div>
    </ModuleLayout>
</template>
