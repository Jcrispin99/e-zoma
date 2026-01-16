<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { salesNavigation, salesIcon } from '@/config/salesNavigation';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Table from '@/components/ui/Table.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import CardData from '@/components/ui/CardData.vue';
import { Customer, PaginatedData } from '@/types/sales';

const props = defineProps<{
    customers: PaginatedData<Customer>;
}>();

const { notify } = useNotification();

const viewMode = ref<'list' | 'grid'>('list');
const selectedCustomers = ref<Customer[]>([]);

onMounted(() => {
    const savedViewMode = localStorage.getItem('customersViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('customersViewMode', newValue);
    if (newValue === 'grid') {
        selectedCustomers.value = [];
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
    { key: 'email', label: 'Email' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/ventas/clientes',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['customers'],
            }
        );
    }, 300);
});

const handleRowClick = (customer: Customer) => {
    router.visit(`/finanzas/ventas/clientes/${customer.identity_id}/editar`);
};

const deleteId = ref<number | null>(null);

const deleteSelected = () => {
    deleteId.value = null;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;

    if (deleteId.value) {
        router.delete(`/finanzas/ventas/clientes/${deleteId.value}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                isDeleting.value = false;
                deleteId.value = null;
                notify('Cliente eliminado correctamente', 'success');
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar el proveedor', 'error');
            }
        });
    } else {
        const count = selectedCustomers.value.length;
        router.post(
            '/finanzas/ventas/clientes/mass-destroy',
            {
                ids: selectedCustomers.value.map((c) => c.identity_id),
            },
            {
                onSuccess: () => {
                    selectedCustomers.value = [];
                    showDeleteModal.value = false;
                    isDeleting.value = false;
                    notify(
                        count === 1
                            ? 'Cliente eliminado correctamente'
                            : `${count} clientes eliminados correctamente`,
                        'success'
                    );
                },
                onError: () => {
                    isDeleting.value = false;
                    notify('Error al eliminar los clientes', 'error');
                },
            }
        );
    }
};

const totalItems = computed(() => props.customers.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);
const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} clientes seleccionados`;
    }
    const count = selectedCustomers.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

</script>

<template>
    <ModuleLayout title="Ventas" :icon="salesIcon" :navigation-items="salesNavigation">
        <div class="space-y-6">
            <DataToolbar title="Clientes" new-route="/finanzas/ventas/clientes/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="customers"
                :selected-count="selectedCustomers.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllAcrossPages = true"
                @clear-selection="selectedCustomers = []; selectAllAcrossPages = false"
                @delete-selected="deleteSelected" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="customers.data" selectable v-model="selectedCustomers"
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

            <CardData v-else :items="customers.data" type="customer" @click="handleRowClick"
                :class="(customer: Customer) => selectedCustomers.some(c => c.identity_id === customer.identity_id)" />
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar cliente"
            message="¿Estás seguro de que deseas eliminar los clientes seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>