<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';
import Form from '@/components/ui/Form.vue';
import Table from '@/components/ui/Table.vue';
import Label from '@/components/ui/Label.vue';
import Input from '@/components/ui/Input.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { Product } from '@/types/product';
import { Warehouse } from '@/types/warehouse';
import { PaginatedInventory } from '@/types/inventory';

interface KardexFilters {
    warehouse_id: number | string | null;
    fecha_inicial: string | null;
    fecha_final: string | null;
    variant_id: number | string | null;
}

const props = defineProps<{
    product: Product;
    warehouses: Warehouse[];
    inventories: PaginatedInventory;
    variants: any[];
    filters: KardexFilters;
}>();

const navigationItems = inventoryNavigation;

const filters = ref({
    warehouse_id: props.filters.warehouse_id || (props.warehouses.length > 0 ? props.warehouses[0].id : ''),
    fecha_inicial: props.filters.fecha_inicial || null,
    fecha_final: props.filters.fecha_final || null,
    variant_id: props.filters.variant_id || '',
});

const warehouseOptions = computed(() => {
    return props.warehouses.map(w => ({
        value: w.id,
        label: w.name
    }));
});

const variantOptions = computed(() => {
    const opts = props.variants.map(v => ({
        value: v.id,
        label: `${v.sku ? v.sku + ' - ' : ''}${v.name}`
    }));
    return [{ value: '', label: 'Todas las variantes' }, ...opts];
});

const breadcrumbs = [
    { label: 'Productos', route: '/finanzas/inventario/productos' },
    { label: 'Editar', route: `/finanzas/inventario/productos/${props.product.id}/editar` },
    { label: 'Kardex' }
];

const headers = [
    { key: 'created_at', label: 'Fecha' },
    { key: 'warehouse', label: 'Almacén' },
    { key: 'detail', label: 'Detalle' },
    { key: 'quantity_in', label: 'Entrada' },
    { key: 'quantity_out', label: 'Salida' },
    { key: 'quantity_balance', label: 'Saldo' },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const updateFilters = () => {
    router.get(
        `/finanzas/inventario/productos/${props.product.id}/kardex`,
        filters.value,
        {
            preserveState: true,
            preserveScroll: true,
            only: ['inventories', 'filters'],
        }
    );
};

watch(() => filters.value.warehouse_id, updateFilters);
watch(() => filters.value.fecha_inicial, updateFilters);
watch(() => filters.value.fecha_final, updateFilters);
watch(() => filters.value.variant_id, updateFilters);

const productTitle = computed(() => {
    if (filters.value.variant_id) {
        const selectedVariant = props.variants.find(v => v.id === filters.value.variant_id);
        if (selectedVariant) {
            return `${props.product.name} - ${selectedVariant.name}`;
        }
    }
    return props.product.name;
});
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form title="Kardex" :breadcrumbs="breadcrumbs" :hide-default-actions="true">
            <template #actions>
                <div class="py-1.5">
                    <h2 class="text-lg font-bold text-gray-700">{{ productTitle }}</h2>
                </div>
            </template>
            <template #top-left>
                <div class="flex flex-col md:flex-row gap-4 mb-6">
                    <div class="w-full md:w-64">
                        <Label>Variante</Label>
                        <Input v-model="filters.variant_id" :options="variantOptions"
                            placeholder="Filtrar por variante" />
                    </div>
                    <div class="w-full md:w-64">
                        <Label>Almacén</Label>
                        <Input v-model="filters.warehouse_id" :options="warehouseOptions"
                            placeholder="Seleccionar almacén" />
                    </div>
                    <div class="w-full md:w-48">
                        <Label>Fecha Inicial</Label>
                        <DatePicker v-model="filters.fecha_inicial" />
                    </div>
                    <div class="w-full md:w-48">
                        <Label>Fecha Final</Label>
                        <DatePicker v-model="filters.fecha_final" />
                    </div>
                </div>
            </template>

            <Table :headers="headers" :items="inventories.data" :clickable="false">
                <template #cell-created_at="{ item }">
                    <span class="text-sm text-gray-600">{{ formatDate(item.created_at) }}</span>
                </template>
                <template #cell-warehouse="{ item }">
                    <span class="text-sm text-gray-600">{{ item.warehouse?.name || '-' }}</span>
                </template>
                <template #cell-detail="{ item }">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900">{{ item.detail }}</span>
                        <div v-if="!filters.variant_id && item.variant" class="mt-1">
                            <span class="text-xs text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">
                                {{item.variant.attribute_values?.map((av: any) => av.value).join(', ') || 'Variante General' }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1" v-if="item.inventoryable_type">
                            Ref: {{ item.inventoryable_id }}
                        </span>
                    </div>
                </template>
                <template #cell-quantity_in="{ item }">
                    <span v-if="item.quantity_in > 0" class="text-green-600 font-medium">+{{ item.quantity_in }}</span>
                    <span v-else class="text-gray-400">-</span>
                </template>
                <template #cell-quantity_out="{ item }">
                    <span v-if="item.quantity_out > 0" class="text-red-600 font-medium">-{{ item.quantity_out }}</span>
                    <span v-else class="text-gray-400">-</span>
                </template>
                <template #cell-quantity_balance="{ item }">
                    <span class="font-bold text-gray-900">{{ item.quantity_balance }}</span>
                </template>
            </Table>

            <div class="mt-4 flex justify-end" v-if="inventories.total > 0">
                <Pagination :pagination="inventories" />
            </div>

        </Form>
    </ModuleLayout>
</template>