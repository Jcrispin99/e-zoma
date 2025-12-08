<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import CardData from '@/components/ui/CardData.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { useNotification } from '@/hooks/useNotification';
import type { Warehouse } from '@/types/warehouse';

const props = defineProps<{
    warehouses: {
        data: Warehouse[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const navigationItems = inventoryNavigation;
const searchTerm = ref('');
const selectedWarehouses = ref<Warehouse[]>([]);
const selectAllAcrossPages = ref(false);
const viewMode = ref<'list' | 'grid'>('list');
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const { notify } = useNotification();

const headers = [
    { key: 'name', label: 'Nombre' },
    { key: 'location', label: 'Ubicación' },
];

watch(viewMode, (newValue) => {
    localStorage.setItem('inventoryViewMode', newValue);
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/inventario/almacenes',
            { search: newValue },
            { preserveState: true, replace: true }
        );
    }, 100);
});

const handleCreate = () => {
    router.visit('/finanzas/inventario/almacenes/crear');
};

const handleEdit = (warehouse: Warehouse) => {
    router.visit(`/finanzas/inventario/almacenes/${warehouse.id}/editar`);
};

const totalItems = computed(() => props.warehouses.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} almacenes seleccionados`;
    }
    const count = selectedWarehouses.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedWarehouses.value = [];
    selectAllAcrossPages.value = false;
};

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    const count = selectedWarehouses.value.length;
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/almacenes/mass-destroy',
        {
            ids: selectedWarehouses.value.map((w) => w.id),
        },
        {
            onSuccess: () => {
                selectedWarehouses.value = [];
                showDeleteModal.value = false;
                isDeleting.value = false;
                notify(
                    count === 1
                        ? 'Almacén eliminado correctamente'
                        : `${count} almacenes eliminados correctamente`,
                    'success'
                );
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar los almacenes', 'error');
            }
        }
    );
};

const paginationData = computed(() => ({
    from: props.warehouses.from,
    to: props.warehouses.to,
    total: props.warehouses.total,
    prev_page_url: props.warehouses.prev_page_url,
    next_page_url: props.warehouses.next_page_url,
}));
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Almacenes" new-route="/finanzas/inventario/almacenes/crear"
                v-model:searchTerm="searchTerm" v-model:view-mode="viewMode" :pagination="paginationData"
                :selected-count="selectedWarehouses.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllTotal"
                @clear-selection="clearSelection" @delete-selected="deleteSelected" @click-new="handleCreate"
                hide-view-toggle />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="warehouses.data" selectable v-model="selectedWarehouses"
                    :global-select="isAllSelected" row-key="id" @row-click="handleEdit"
                    @header-select="selectAllAcrossPages = $event">
                    <template #cell-name="{ item }">
                        <span class="font-medium text-gray-900">{{ item.name }}</span>
                    </template>

                    <template #cell-location="{ item }">
                        <span class="text-gray-500">{{ item.location || '-' }}</span>
                    </template>
                </Table>
            </div>

            <div v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-7 bg-gray-300 h-[0.5px]">
                <CardData v-for="warehouse in warehouses.data" :key="warehouse.id" :item="warehouse" type="warehouse"
                    @click="handleEdit(warehouse)" class="cursor-pointer mt-4" />
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar almacenes"
            message="¿Estás seguro de que deseas eliminar los almacenes seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
