<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { useNotification } from '@/hooks/useNotification';
import type { Attribute } from '@/types/product';

const props = defineProps<{
    attributes: {
        data: Attribute[];
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
const selectedAttributes = ref<Attribute[]>([]);
const selectAllAcrossPages = ref(false);
const viewMode = ref<'list' | 'grid'>('list');
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const { notify } = useNotification();

const headers = [
    { key: 'name', label: 'Nombre del atributo' },
    { key: 'values_count', label: 'Valores' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/inventario/atributos',
            { search: newValue },
            { preserveState: true, replace: true }
        );
    }, 100);
});

const handleCreate = () => {
    router.visit('/finanzas/inventario/atributos/crear');
};

const handleEdit = (attribute: any) => {
    router.visit(`/finanzas/inventario/atributos/${attribute.id}/editar`);
};

const totalItems = computed(() => props.attributes.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} atributos seleccionados`;
    }
    const count = selectedAttributes.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedAttributes.value = [];
    selectAllAcrossPages.value = false;
};

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/atributos/mass-destroy',
        {
            ids: selectedAttributes.value.map((c) => c.id),
        },
        {
            onSuccess: (page) => {
                selectedAttributes.value = [];
                showDeleteModal.value = false;
                isDeleting.value = false;

                const flash = page.props.flash as any;
                if (flash?.success) {
                    notify(flash.success, 'success');
                } else if (flash?.error) {
                    notify(flash.error, 'error');
                }
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar los atributos', 'error');
            }
        }
    );
};

const paginationData = computed(() => ({
    from: props.attributes.from,
    to: props.attributes.to,
    total: props.attributes.total,
    prev_page_url: props.attributes.prev_page_url,
    next_page_url: props.attributes.next_page_url,
}));
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Atributos" new-route="/finanzas/inventario/atributos/crear"
                v-model:searchTerm="searchTerm" v-model:view-mode="viewMode" :pagination="paginationData"
                :selected-count="selectedAttributes.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllTotal"
                @clear-selection="clearSelection" @delete-selected="deleteSelected" @click-new="handleCreate"
                hide-view-toggle />

            <div class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="attributes.data" selectable v-model="selectedAttributes"
                    :global-select="isAllSelected" row-key="id" @row-click="handleEdit"
                    @header-select="selectAllAcrossPages = $event">
                    <template #cell-name="{ item }">
                        <span class="font-medium text-gray-900">{{ item.name }}</span>
                    </template>

                    <template #cell-values_count="{ item }">
                        <span class="text-gray-500">{{ item.attribute_values_count || 0 }}</span>
                    </template>
                </Table>
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar atributos"
            message="¿Estás seguro de que deseas eliminar los atributos seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
