<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { purchasesNavigation, purchasesIcon } from '@/config/purchasesNavigation';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Table from '@/components/ui/Table.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import { Purchase, PaginatedData } from '@/types/purchases';
import CardData from '@/components/ui/CardData.vue';

const props = defineProps<{
    purchases: PaginatedData<Purchase>;
}>();

const { notify } = useNotification();

const viewMode = ref<'list' | 'grid'>('list');
const selectedPurchases = ref<Purchase[]>([]);

onMounted(() => {
    const savedViewMode = localStorage.getItem('purchasesViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('purchasesViewMode', newValue);
    if (newValue === 'grid') {
        selectedPurchases.value = [];
        selectAllAcrossPages.value = false;
    }
});

const selectAllAcrossPages = ref(false);
const searchTerm = ref('');
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const headers = [
    { key: 'serie_correlative', label: 'N° Compra' },
    { key: 'purchase_order', label: 'Orden de Compra' },
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
            '/finanzas/compras/facturas',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['purchases'],
            }
        );
    }, 300);
});

const handleRowClick = (purchase: Purchase) => {
    router.visit(`/finanzas/compras/facturas/${purchase.id}/editar`);
};

const deleteId = ref<number | null>(null);

const deleteSelected = () => {
    deleteId.value = null;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    if (deleteId.value) {
        router.delete(`/finanzas/compras/facturas/${deleteId.value}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                isDeleting.value = false;
                deleteId.value = null;
                notify('Compra eliminada correctamente', 'success');
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar la compra', 'error');
            }
        });
    } else {
        const count = selectedPurchases.value.length;
        router.post(
            '/finanzas/compras/facturas/mass-destroy',
            {
                ids: selectedPurchases.value.map((s) => s.id),
            },
            {
                onSuccess: () => {
                    selectedPurchases.value = [];
                    showDeleteModal.value = false;
                    isDeleting.value = false;
                    notify(
                        count === 1
                            ? 'Compra eliminada correctamente'
                            : `${count} compras eliminadas correctamente`,
                        'success'
                    );
                },
                onError: () => {
                    isDeleting.value = false;
                    notify('Error al eliminar las compras', 'error');
                },
            }
        );
    }
};

const totalItems = computed(() => props.purchases.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);
const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todas las ${totalItems.value} compras seleccionadas`;
    }
    const count = selectedPurchases.value.length;
    return `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'draft': return 'bg-gray-100 text-gray-800';
        case 'posted': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        'draft': 'Borrador',
        'posted': 'Publicado',
        'cancelled': 'Cancelada'
    };
    return labels[status] || status;
};

</script>

<template>
    <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
        <div class="space-y-6">
            <DataToolbar title="Compras (Facturas)" new-route="/finanzas/compras/facturas/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="purchases"
                :selected-count="selectedPurchases.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllAcrossPages = true"
                @clear-selection="selectedPurchases = []; selectAllAcrossPages = false"
                @delete-selected="deleteSelected" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="purchases.data" selectable v-model="selectedPurchases"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">

                    <template #cell-serie_correlative="{ item }">
                        <span class="font-medium text-gray-900">
                            {{ item.serie }}-{{ item.correlative }}
                        </span>
                    </template>

                    <template #cell-purchase_order="{ item }">
                        <div v-if="item.purchase_order" class="text-sm text-gray-700">
                            {{ item.purchase_order.serie }}-{{ item.purchase_order.correlative }}
                        </div>
                        <span v-else class="text-xs text-gray-400">Sin orden</span>
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

            <CardData v-else :items="purchases.data" type="purchase_order" @click="handleRowClick"
                :class="(purchase: Purchase) => selectedPurchases.some(s => s.id === purchase.id)" />
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar compra"
            message="¿Estás seguro de que deseas eliminar las compras seleccionadas? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
