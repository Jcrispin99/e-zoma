<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { salesNavigation, salesIcon } from '@/config/salesNavigation';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Table from '@/components/ui/Table.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import { Sale, PaginatedData } from '@/types/sales';
import CardData from '@/components/ui/CardData.vue';

const props = defineProps<{
    sales: PaginatedData<Sale>;
}>();

const { notify } = useNotification();

const viewMode = ref<'list' | 'grid'>('list');
const selectedSales = ref<Sale[]>([]);

onMounted(() => {
    const savedViewMode = localStorage.getItem('salesViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('salesViewMode', newValue);
    if (newValue === 'grid') {
        selectedSales.value = [];
        selectAllAcrossPages.value = false;
    }
});

const selectAllAcrossPages = ref(false);
const searchTerm = ref('');
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const headers = [
    { key: 'serie_correlative', label: 'N° Venta' },
    { key: 'customer', label: 'Cliente' },
    { key: 'total', label: 'Total' },
    { key: 'status', label: 'Estado' },
    { key: 'created_at', label: 'Fecha' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/ventas/ordenes',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['sales'],
            }
        );
    }, 300);
});

const handleRowClick = (sale: Sale) => {
    router.visit(`/finanzas/ventas/ordenes/${sale.id}/editar`);
};

const deleteId = ref<number | null>(null);

const deleteSelected = () => {
    deleteId.value = null;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    if (deleteId.value) {
        router.delete(`/finanzas/ventas/ordenes/${deleteId.value}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                isDeleting.value = false;
                deleteId.value = null;
                notify('Venta eliminada correctamente', 'success');
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar la venta', 'error');
            }
        });
    } else {
        const count = selectedSales.value.length;
        router.post(
            '/finanzas/ventas/ordenes/mass-destroy',
            {
                ids: selectedSales.value.map((s) => s.id),
            },
            {
                onSuccess: () => {
                    selectedSales.value = [];
                    showDeleteModal.value = false;
                    isDeleting.value = false;
                    notify(
                        count === 1
                            ? 'Venta eliminada correctamente'
                            : `${count} ventas eliminadas correctamente`,
                        'success'
                    );
                },
                onError: () => {
                    isDeleting.value = false;
                    notify('Error al eliminar las ventas', 'error');
                },
            }
        );
    }
};

const totalItems = computed(() => props.sales.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);
const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todas las ${totalItems.value} ventas seleccionadas`;
    }
    const count = selectedSales.value.length;
    return `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'draft': return 'bg-gray-100 text-gray-800';
        case 'posted': return 'bg-teal-100 text-teal-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'draft': 'Borrador',
        'posted': 'Publicada',
        'cancelled': 'Cancelada'
    };
    return labels[status] || status;
};
</script>

<template>
    <ModuleLayout title="Ventas" :icon="salesIcon" :navigation-items="salesNavigation">
        <div class="space-y-6">
            <DataToolbar title="Ventas (Ordenes)" new-route="/finanzas/ventas/ordenes/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="sales"
                :selected-count="selectedSales.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllAcrossPages = true"
                @clear-selection="selectedSales = []; selectAllAcrossPages = false" @delete-selected="deleteSelected" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="sales.data" selectable v-model="selectedSales"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">

                    <template #cell-serie_correlative="{ item }">
                        <span class="font-medium text-gray-900">
                            {{ item.serie }}-{{ item.correlative }}
                        </span>
                    </template>

                    <template #cell-customer="{ item }">
                        <div class="text-sm">
                            <div class="font-medium text-gray-900">{{ item.customer?.name || 'Cliente General' }}</div>
                            <div class="text-xs text-gray-500">{{ item.customer?.document_number }}</div>
                        </div>
                    </template>

                    <template #cell-total="{ item }">
                        <span class="font-medium text-gray-900">S/ {{ Number(item.total || 0).toFixed(2) }}</span>
                    </template>

                    <template #cell-status="{ item }">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            :class="getStatusColor(item.status)">
                            {{ getStatusLabel(item.status) }}
                        </span>
                    </template>

                    <template #cell-created_at="{ item }">
                        <span class="text-gray-500 text-sm">
                            {{ new Date(item.created_at).toLocaleDateString('es-ES') }}
                        </span>
                    </template>
                </Table>
            </div>

            <CardData v-else :items="sales.data" type="sale" @click="handleRowClick"
                :class="(sale: Sale) => selectedSales.some(s => s.id === sale.id)" />
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar venta"
            message="¿Estás seguro de que deseas eliminar las ventas seleccionadas? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>