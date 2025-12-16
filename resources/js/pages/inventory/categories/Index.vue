<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import Table from '@/components/ui/Table.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { useNotification } from '@/hooks/useNotification';
import type { Category } from '@/types/product';

const props = defineProps<{
    categories: {
        data: Category[];
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
const selectedCategories = ref<Category[]>([]);
const selectAllAcrossPages = ref(false);
const viewMode = ref<'list' | 'grid'>('list');
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const { notify } = useNotification();

const headers = [
    { key: 'name', label: 'Nombre' },
    { key: 'description', label: 'Descripción' },
    { key: 'parent', label: 'Categoría Padre' },
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
            '/finanzas/inventario/categorias',
            { search: newValue },
            { preserveState: true, replace: true }
        );
    }, 100);
});

const handleCreate = () => {
    router.visit('/finanzas/inventario/categorias/crear');
};

const handleEdit = (category: Category) => {
    router.visit(`/finanzas/inventario/categorias/${category.id}/editar`);
};

const totalItems = computed(() => props.categories.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todas las ${totalItems.value} categorías seleccionadas`;
    }
    const count = selectedCategories.value.length;
    return `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
});

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedCategories.value = [];
    selectAllAcrossPages.value = false;
};

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/categorias/mass-destroy',
        {
            ids: selectedCategories.value.map((c) => c.id),
        },
        {
            onSuccess: (page) => {
                selectedCategories.value = [];
                showDeleteModal.value = false;
                isDeleting.value = false;

                const flash = page.props.flash as any;
                if (flash?.swalt) {
                    notify(flash.swalt.text, flash.swalt.icon === 'error' ? 'error' : (flash.swalt.icon === 'warning' ? 'warning' : 'success'));
                } else if (flash?.success) {
                    notify(flash.success, 'success');
                } else if (flash?.error) {
                    notify(flash.error, 'error');
                } else if (flash?.warning) {
                    notify(flash.warning, 'warning');
                }
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar las categorías', 'error');
            }
        }
    );
};

const paginationData = computed(() => ({
    from: props.categories.from,
    to: props.categories.to,
    total: props.categories.total,
    prev_page_url: props.categories.prev_page_url,
    next_page_url: props.categories.next_page_url,
}));
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Categorías" new-route="/finanzas/inventario/categorias/crear"
                v-model:searchTerm="searchTerm" v-model:view-mode="viewMode" :pagination="paginationData"
                :selected-count="selectedCategories.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllTotal"
                @clear-selection="clearSelection" @delete-selected="deleteSelected" @click-new="handleCreate"
                hide-view-toggle />

            <div class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="categories.data" selectable v-model="selectedCategories"
                    :global-select="isAllSelected" row-key="id" @row-click="handleEdit"
                    @header-select="selectAllAcrossPages = $event">
                    <template #cell-name="{ item }">
                        <span class="font-medium text-gray-900">{{ item.name }}</span>
                    </template>

                    <template #cell-description="{ item }">
                        <span class="text-gray-500">{{ item.description || '-' }}</span>
                    </template>

                    <template #cell-parent="{ item }">
                        <span class="text-gray-500">{{ item.parent?.name || '-' }}</span>
                    </template>
                </Table>
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar categorías"
            message="¿Estás seguro de que deseas eliminar las categorías seleccionadas? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
