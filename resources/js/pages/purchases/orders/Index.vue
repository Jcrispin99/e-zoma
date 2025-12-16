<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { purchasesNavigation, purchasesIcon } from '@/config/purchasesNavigation';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Table from '@/components/ui/Table.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import CardData from '@/components/ui/CardData.vue';
import { PurchaseOrder, PaginatedData } from '@/types/purchases';

const props = defineProps<{
    purchaseOrders: PaginatedData<PurchaseOrder>;
}>();

const { notify } = useNotification();

const viewMode = ref<'list' | 'grid'>('list');
const selectedOrders = ref<PurchaseOrder[]>([]);

onMounted(() => {
    const savedViewMode = localStorage.getItem('ordersViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('ordersViewMode', newValue);
    if (newValue === 'grid') {
        selectedOrders.value = [];
        selectAllAcrossPages.value = false;
    }
});

const selectAllAcrossPages = ref(false);
const searchTerm = ref('');
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const headers = [
    { key: 'serie_correlative', label: 'N° Orden' },
    { key: 'supplier', label: 'Proveedor' },
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
            '/finanzas/compras/ordenes',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['purchaseOrders'],
            }
        );
    }, 300);
});

const handleRowClick = (order: PurchaseOrder) => {
    router.visit(`/finanzas/compras/ordenes/${order.id}/editar`);
};

const deleteId = ref<number | null>(null);

const deleteSelected = () => {
    deleteId.value = null;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    if (deleteId.value) {
        router.delete(`/finanzas/compras/ordenes/${deleteId.value}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                isDeleting.value = false;
                deleteId.value = null;
                notify('Orden eliminada correctamente', 'success');
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar la orden', 'error');
            }
        });
    } else {
        const count = selectedOrders.value.length;
        router.post(
            '/finanzas/compras/ordenes/mass-destroy',
            {
                ids: selectedOrders.value.map((s) => s.id),
            },
            {
                onSuccess: () => {
                    selectedOrders.value = [];
                    showDeleteModal.value = false;
                    isDeleting.value = false;
                    notify(
                        count === 1
                            ? 'Orden eliminada correctamente'
                            : `${count} ordenes eliminadas correctamente`,
                        'success'
                    );
                },
                onError: () => {
                    isDeleting.value = false;
                    notify('Error al eliminar las ordenes', 'error');
                },
            }
        );
    }
};

const totalItems = computed(() => props.purchaseOrders.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);
const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todas las ${totalItems.value} ordenes seleccionadas`;
    }
    const count = selectedOrders.value.length;
    return `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'draft': return 'bg-gray-100 text-gray-800';
        case 'approved': return 'bg-blue-100 text-blue-800';
        case 'sent': return 'bg-yellow-100 text-yellow-800';
        case 'received': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'draft': 'Borrador',
        'approved': 'Aprobada',
        'sent': 'Enviada',
        'received': 'Recibida',
        'cancelled': 'Cancelada'
    };
    return labels[status] || status;
};

</script>

<template>
    <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
        <div class="space-y-6">
            <DataToolbar title="Ordenes de Compra" new-route="/finanzas/compras/ordenes/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="purchaseOrders"
                :selected-count="selectedOrders.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllAcrossPages = true"
                @clear-selection="selectedOrders = []; selectAllAcrossPages = false"
                @delete-selected="deleteSelected" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="purchaseOrders.data" selectable v-model="selectedOrders"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">

                    <template #cell-serie_correlative="{ item }">
                        <span class="font-medium text-gray-900">
                            {{ item.serie }}-{{ item.correlative }}
                        </span>
                    </template>

                    <template #cell-supplier="{ item }">
                        <div class="text-sm">
                            <div class="font-medium text-gray-900">{{ item.supplier?.name || 'Sin proveedor' }}</div>
                            <div class="text-xs text-gray-500">{{ item.supplier?.document_number }}</div>
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

            <CardData v-else :items="purchaseOrders.data" type="purchase_order" @click="handleRowClick"
                :class="(order: PurchaseOrder) => selectedOrders.some(s => s.id === order.id)" />
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar orden"
            message="¿Estás seguro de que deseas eliminar las ordenes seleccionadas? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
