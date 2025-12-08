<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import Table from '@/components/ui/Table.vue';
import CardData from '@/components/ui/CardData.vue';
import DataToolbar from '@/components/ui/DataToolbar.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useNotification } from '@/hooks/useNotification';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';

const props = defineProps<{
    variants: any;
}>();

const viewMode = ref<'list' | 'grid'>('grid');
const searchTerm = ref('');
const selectedVariants = ref<any[]>([]);
const selectAllAcrossPages = ref(false);

const { notify } = useNotification();
const showDeleteModal = ref(false);
const isDeleting = ref(false);

onMounted(() => {
    const savedViewMode = localStorage.getItem('inventoryViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
});

watch(viewMode, (newValue) => {
    localStorage.setItem('inventoryViewMode', newValue);
    if (newValue === 'grid') {
        clearSelection();
    }
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/finanzas/inventario/variantes',
            { search: newValue },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['variants'],
            }
        );
    }, 100);
});

const navigationItems = inventoryNavigation;

const headers = [
    { key: 'name', label: 'Nombre' },
    { key: 'sku', label: 'Referencia Interna' },
    { key: 'price', label: 'Precio' },
    { key: 'stock', label: 'Stock' },
    { key: 'updated_at', label: 'Última actualización' },
];

const getVariantName = (variant: any) => {
    const attributes = variant.attribute_values?.map((av: any) => av.value).join(', ') || 'Sin atributos';
    return `${variant.product?.name} - ${attributes}`;
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const handleRowClick = (variant: any) => {
    router.visit(`/finanzas/inventario/variantes/${variant.id}/editar`);
};

const totalItems = computed(() => props.variants.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todas las ${totalItems.value} variantes seleccionadas`;
    }
    const count = selectedVariants.value.length;
    return `${count} ${count === 1 ? 'seleccionada' : 'seleccionadas'}`;
});

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedVariants.value = [];
    selectAllAcrossPages.value = false;
};

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    const count = selectedVariants.value.length;
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/variantes/mass-destroy',
        {
            ids: selectedVariants.value.map((v) => v.id),
        },
        {
            onSuccess: () => {
                selectedVariants.value = [];
                showDeleteModal.value = false;
                isDeleting.value = false;
                notify(
                    count === 1
                        ? 'Variante eliminada correctamente'
                        : `${count} variantes eliminadas correctamente`,
                    'success'
                );
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar las variantes', 'error');
            },
        }
    );
};

const handleGenerateQr = () => {
    const ids = selectedVariants.value.map(v => v.id);
    router.post('/finanzas/inventario/variantes/qr-masivo', {
        ids: ids,
        select_all: selectAllAcrossPages.value,
        search: searchTerm.value
    });
};
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <DataToolbar title="Variantes" new-route="/finanzas/inventario/productos/crear" new-label="Nuevo"
                v-model:search-term="searchTerm" v-model:view-mode="viewMode" :pagination="variants"
                :selected-count="selectedVariants.length" :total-count="totalItems" :is-all-selected="isAllSelected"
                :selection-message="selectionMessage" @select-all-total="selectAllTotal"
                @clear-selection="clearSelection" @delete-selected="deleteSelected" @generate-qr="handleGenerateQr"
                :show-qr="true" />

            <div v-if="viewMode === 'list'" class="bg-white overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="variants.data" selectable v-model="selectedVariants"
                    :global-select="isAllSelected" row-key="id" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">


                    <template #cell-name="{ item }">
                        <span class="font-medium text-gray-900">{{ getVariantName(item) }}</span>
                    </template>

                    <template #cell-sku="{ item }">
                        <span class="text-gray-600">{{ item.sku }}</span>
                    </template>

                    <template #cell-price="{ item }">
                        <span class="text-gray-600">S/ {{ Number(item.price).toFixed(2) }}</span>
                    </template>

                    <template #cell-stock="{ item }">
                        <span class="text-gray-600">{{ item.stock }}</span>
                    </template>

                    <template #cell-updated_at="{ item }">
                        <span class="text-gray-600">{{ formatDate(item.updated_at) }}</span>
                    </template>
                </Table>
            </div>

            <div v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-7 bg-gray-300 h-[0.5px]">
                <CardData v-for="variant in variants.data" :key="variant.id" :item="variant" type="variant"
                    @click="handleRowClick(variant)" class="cursor-pointer mt-4" />
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar variantes"
            message="¿Estás seguro de que deseas eliminar las variantes seleccionadas? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
