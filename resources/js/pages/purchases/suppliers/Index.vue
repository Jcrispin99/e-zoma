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
import { Supplier, PaginatedData } from '@/types/purchases';

const props = defineProps<{
    suppliers: PaginatedData<Supplier>;
}>();

const { notify } = useNotification();

const viewMode = ref<'list' | 'grid'>('list');
const selectedSuppliers = ref<Supplier[]>([]);

onMounted(() => {
    const savedViewMode = localStorage.getItem('suppliersViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('suppliersViewMode', newValue);
    if (newValue === 'grid') {
        selectedSuppliers.value = [];
        selectAllAcrossPages.value = false;
    }
});
const selectAllAcrossPages = ref(false);
const searchTerm = ref('');
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const headers = [
    { key: 'document_number', label: 'Documento' },
    { key: 'name', label: 'Nombre' },
    { key: 'contact', label: 'Contacto' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/compras/proveedores',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['suppliers'],
            }
        );
    }, 300);
});

const handleRowClick = (supplier: Supplier) => {
    router.visit(`/finanzas/compras/proveedores/${supplier.id}/editar`);
};

const deleteId = ref<number | null>(null);

const deleteSelected = () => {
    deleteId.value = null;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    if (deleteId.value) {
        router.delete(`/finanzas/compras/proveedores/${deleteId.value}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                isDeleting.value = false;
                deleteId.value = null;
                notify('Proveedor eliminado correctamente', 'success');
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar el proveedor', 'error');
            }
        });
    } else {
        const count = selectedSuppliers.value.length;
        router.post(
            '/finanzas/compras/proveedores/mass-destroy',
            {
                ids: selectedSuppliers.value.map((s) => s.id),
            },
            {
                onSuccess: () => {
                    selectedSuppliers.value = [];
                    showDeleteModal.value = false;
                    isDeleting.value = false;
                    notify(
                        count === 1
                            ? 'Proveedor eliminado correctamente'
                            : `${count} proveedores eliminados correctamente`,
                        'success'
                    );
                },
                onError: () => {
                    isDeleting.value = false;
                    notify('Error al eliminar los proveedores', 'error');
                },
            }
        );
    }
};

const totalItems = computed(() => props.suppliers.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);
const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} proveedores seleccionados`;
    }
    const count = selectedSuppliers.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

</script>

<template>
    <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
        <div class="space-y-6">
            <DataToolbar title="Proveedores" new-route="/finanzas/compras/proveedores/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="suppliers"
                :selected-count="selectedSuppliers.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllAcrossPages = true"
                @clear-selection="selectedSuppliers = []; selectAllAcrossPages = false"
                @delete-selected="deleteSelected" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="suppliers.data" selectable v-model="selectedSuppliers"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">

                    <template #cell-contact="{ item }">
                        <div class="text-sm">
                            <div>{{ item.phone }}</div>
                            <div class="text-xs text-gray-500">{{ item.email }}</div>
                        </div>
                    </template>
                </Table>
            </div>

            <CardData v-else :items="suppliers.data" type="supplier" @click="handleRowClick"
                :class="(supplier: Supplier) => selectedSuppliers.some(s => s.id === supplier.id)" />
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar proveedor"
            message="¿Estás seguro de que deseas eliminar los proveedores seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>