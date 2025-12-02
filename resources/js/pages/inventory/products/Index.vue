<script setup lang="ts">
import ModuleLayout, {
    NavigationItem,
} from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import Button from '@/components/ui/Button.vue';
import Table from '@/components/ui/Table.vue';
import CardData from '@/components/ui/CardData.vue';
import {
    Plus,
    LayoutGrid,
    List,
    X,
    ChevronLeft,
    ChevronRight,
    Settings,
    Trash2,
    Image as ImageIcon,
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import { useNotification } from '@/hooks/useNotification';
import type { ProductsData, Product } from '@/types/product';

const props = defineProps<{
    products: ProductsData;
}>();

const viewMode = ref<'list' | 'grid'>('grid');
const selectedProducts = ref<Product[]>([]);
const selectAllAcrossPages = ref(false);

const { notify } = useNotification();
const showDeleteModal = ref(false);
const isDeleting = ref(false);

onMounted(() => {
    const savedViewMode = localStorage.getItem('inventoryViewMode');
    if (savedViewMode === 'list' || savedViewMode === 'grid') {
        viewMode.value = savedViewMode;
    }
    document.addEventListener('click', handleClickOutside);
});

watch(viewMode, (newValue) => {
    localStorage.setItem('inventoryViewMode', newValue);
    if (newValue === 'grid') {
        clearSelection();
    }
});

const navigationItems: NavigationItem[] = [
    { label: 'Información general', href: '/finanzas/inventario' },
    {
        label: 'Productos',
        items: [
            { label: 'Productos', href: '/finanzas/inventario/productos' },
            { label: 'Variantes', href: '#' },
        ],
    },
    { label: 'Reportes', href: '#' },
    {
        label: 'Configuración',
        sections: [
            {
                title: 'Gestión del almacén',
                items: [{ label: 'Almacenes', href: '#' }],
            },
            {
                title: 'Productos',
                items: [
                    { label: 'Categorías', href: '#' },
                    { label: 'Atributos', href: '#' },
                ],
            },
        ],
    },
];

const headers = [
    { key: 'name', label: 'Nombre' },
    { key: 'sku', label: 'SKU' },
    { key: 'category', label: 'Categoría' },
    { key: 'price', label: 'Precio' },
    { key: 'stock', label: 'Stock' },
    { key: 'updated_at', label: 'Última actualización' },
];

const getStock = (product: any) => {
    return (
        product.variants?.reduce(
            (acc: number, v: any) => acc + (Number(v.stock) || 0),
            0
        ) || 0
    );
};

const getSku = (product: any) => {
    return product.variants?.[0]?.sku || '';
};

const handleRowClick = (product: Product) => {
    router.visit(`/finanzas/inventario/productos/${product.id}/editar`);
};

const selectAllTotal = () => {
    selectAllAcrossPages.value = true;
};

const clearSelection = () => {
    selectedProducts.value = [];
    selectAllAcrossPages.value = false;
    showActionsDropdown.value = false;
};

const totalItems = computed(() => props.products.meta?.total || 0);
const isAllSelected = computed(() => selectAllAcrossPages.value);

const selectionMessage = computed(() => {
    if (selectAllAcrossPages.value) {
        return `Todos los ${totalItems.value} productos seleccionados`;
    }
    const count = selectedProducts.value.length;
    return `${count} ${count === 1 ? 'seleccionado' : 'seleccionados'}`;
});

const showActionsDropdown = ref(false);
const actionsDropdownRef = ref<HTMLElement | null>(null);

const handleClickOutside = (event: MouseEvent) => {
    if (
        actionsDropdownRef.value &&
        !actionsDropdownRef.value.contains(event.target as Node)
    ) {
        showActionsDropdown.value = false;
    }
};

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const deleteSelected = () => {
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.post(
        '/finanzas/inventario/productos/mass-destroy',
        {
            ids: selectedProducts.value.map((p) => p.id),
        },
        {
            onSuccess: () => {
                selectedProducts.value = [];
                showDeleteModal.value = false;
                showActionsDropdown.value = false;
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
                notify('Error al eliminar los productos', 'error');
            },
        }
    );
};
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <div class="space-y-6">
            <div class="flex flex-wrap justify-between items-center gap-4 px-7 mt-2">
                <div class="flex items-center gap-4">
                    <Button @click="router.visit('/finanzas/inventario/productos/crear')" class="-mr-2">
                        <Plus class="w-4 h-4 mr-2" />
                        Nuevo
                    </Button>
                    <h1 class="text-sm font-medium text-teal-600">
                        Productos
                    </h1>
                </div>

                <div v-if="
                    (selectedProducts.length > 0 || isAllSelected) &&
                    viewMode === 'list'
                " class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex items-center gap-4 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100 sm:w-auto justify-between sm:justify-start">
                        <span class="text-teal-500 font-medium">{{
                            selectionMessage
                            }}</span>

                        <button v-if="!isAllSelected && totalItems > selectedProducts.length" @click="selectAllTotal"
                            class="text-blue-500 hover:text-blue-600 font-medium flex items-center text-sm">
                            Seleccionar todos los {{ totalItems }}
                        </button>

                        <button @click="clearSelection" class="text-gray-400 hover:text-gray-600">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="relative" ref="actionsDropdownRef">
                        <Button variant="secondary" size="sm" @click="showActionsDropdown = !showActionsDropdown">
                            <Settings class="w-4 h-4 mr-2" />
                            Acciones
                        </Button>

                        <div v-if="showActionsDropdown"
                            class="absolute top-full left-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50 py-1">
                            <button @click="deleteSelected"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <Trash2 class="w-4 h-4" />
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-white rounded-lg border border-gray-200 p-1">
                        <button @click="viewMode = 'grid'" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'grid'
                            ? 'bg-gray-100 text-gray-700'
                            : 'text-gray-500 hover:text-gray-700'
                            ">
                            <LayoutGrid class="w-5 h-5" />
                        </button>
                        <button @click="viewMode = 'list'" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'list'
                            ? 'bg-gray-100 text-gray-700'
                            : 'text-gray-500 hover:text-gray-700'
                            ">
                            <List class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="viewMode === 'list'" class="bg-white rounded-lg overflow-hidden">
                <div class="bg-gray-300 h-[0.5px]"></div>

                <Table :headers="headers" :items="products.data" selectable v-model="selectedProducts"
                    :global-select="isAllSelected" @row-click="handleRowClick"
                    @header-select="selectAllAcrossPages = $event">
                    <template #cell-image="{ item }">
                        <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden">
                            <img v-if="item.image" :src="item.image" :alt="item.name"
                                class="h-full w-full object-cover" />
                            <ImageIcon v-else class="h-5 w-5 text-gray-400" />
                        </div>
                    </template>

                    <template #cell-name="{ value }">
                        <span class="font-medium text-gray-900">{{ value }}</span>
                    </template>

                    <template #cell-sku="{ item }">
                        <span class="text-gray-600">{{ getSku(item) }}</span>
                    </template>

                    <template #cell-category="{ item }">
                        <span class="text-gray-600">{{
                            item.category?.name || 'Sin categoría'
                            }}</span>
                    </template>

                    <template #cell-stock="{ item }">
                        <span class="text-gray-600">{{ getStock(item) }}</span>
                    </template>

                    <template #cell-updated_at="{ item }">
                        <span class="text-gray-600 text-sm">
                            {{ new Date(item.updated_at).toLocaleString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false
                            }).replace(',', '') }}
                        </span>
                    </template>
                </Table>
            </div>

            <div v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-7 bg-gray-300 h-[0.5px]">
                <CardData v-for="product in products.data" :key="product.id" :product="product"
                    @click="handleRowClick(product)" class="cursor-pointer mt-4" />
            </div>

            <div v-if="products.meta && products.meta.last_page > 1"
                class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-lg">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando
                            <span class="font-medium">{{ products.meta.from }}</span>
                            a
                            <span class="font-medium">{{ products.meta.to }}</span>
                            de
                            <span class="font-medium">{{ products.meta.total }}</span>
                            resultados
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <Button variant="secondary" :disabled="!products.links.prev"
                                @click="router.visit(products.links.prev)"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md rounded-r-none border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10">
                                <span class="sr-only">Anterior</span>
                                <ChevronLeft class="h-5 w-5" />
                            </Button>

                            <template v-for="(link, i) in products.meta.links" :key="i">
                                <Button v-if="
                                    link.url &&
                                    !link.label.includes('Previous') &&
                                    !link.label.includes('Next')
                                " @click="router.visit(link.url)" :variant="link.active ? 'primary' : 'secondary'"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium focus:z-10"
                                    :class="[
                                        link.active
                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'rounded-none',
                                    ]">
                                    <span v-html="link.label"></span>
                                </Button>
                            </template>

                            <Button variant="secondary" :disabled="!products.links.next"
                                @click="router.visit(products.links.next)"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md rounded-l-none border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10">
                                <span class="sr-only">Siguiente</span>
                                <ChevronRight class="h-5 w-5" />
                            </Button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="showDeleteModal" title="Eliminar productos"
            message="¿Estás seguro de que deseas eliminar los productos seleccionados? Esta acción no se puede deshacer."
            confirm-text="Eliminar" cancel-text="Cancelar" variant="danger" :loading="isDeleting"
            @close="showDeleteModal = false" @confirm="confirmDelete" />
    </ModuleLayout>
</template>
