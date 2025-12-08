<script setup lang="ts">
import {
    Plus,
    LayoutGrid,
    List,
    X,
    Settings,
    Trash2,
    Search,
    QrCode,
} from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { watch } from 'vue';

interface PaginationData {
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Props {
    title: string;
    newRoute: string;
    newLabel?: string;
    searchTerm: string;
    viewMode: 'grid' | 'list';
    pagination?: PaginationData;
    selectedCount?: number;
    totalCount?: number;
    isAllSelected?: boolean;
    selectionMessage?: string;
    showQr?: boolean;
    hideViewToggle?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    newLabel: 'Nuevo',
    selectedCount: 0,
    totalCount: 0,
    isAllSelected: false,
    selectionMessage: '',
    showQr: false,
    hideViewToggle: false,
});

const emit = defineEmits<{
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:viewMode', value: 'grid' | 'list'): void;
    (e: 'select-all-total'): void;
    (e: 'clear-selection'): void;
    (e: 'delete-selected'): void;
    (e: 'generate-qr'): void;
}>();

import { ref, onMounted, onUnmounted } from 'vue';

const showActionsDropdown = ref(false);
const actionsDropdownRef = ref<HTMLElement | null>(null);

watch(() => [props.selectedCount, props.isAllSelected], () => {
    showActionsDropdown.value = false;
});

const handleClickOutside = (event: MouseEvent) => {
    if (
        actionsDropdownRef.value &&
        !actionsDropdownRef.value.contains(event.target as Node)
    ) {
        showActionsDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:flex-wrap justify-between items-center gap-3 px-4 sm:px-7 mt-2">
        <div class="flex items-center justify-between w-full sm:w-auto gap-4">
            <div class="flex items-center gap-4">
                <Button @click="router.visit(newRoute)" class="-mr-2">
                    <Plus class="w-4 h-4 mr-2" />
                    {{ newLabel }}
                </Button>
                <h1 class="text-sm font-medium text-teal-600 truncate max-w-[150px] sm:max-w-none">
                    {{ title }}
                </h1>
            </div>

            <div v-if="!hideViewToggle" class="flex sm:hidden items-center bg-gray-100 rounded-lg p-1">
                <button @click="emit('update:viewMode', 'grid')" class="p-1.5 rounded-md transition-all shadow-sm"
                    :class="viewMode === 'grid'
                        ? 'bg-white text-gray-900'
                        : 'text-gray-500 hover:text-gray-900'
                        ">
                    <LayoutGrid class="w-5 h-5" />
                </button>
                <button @click="emit('update:viewMode', 'list')" class="p-1.5 rounded-md transition-all shadow-sm"
                    :class="viewMode === 'list'
                        ? 'bg-white text-gray-900'
                        : 'text-gray-500 hover:text-gray-900'
                        ">
                    <List class="w-5 h-5" />
                </button>
            </div>
        </div>

        <div v-if="!(selectedCount > 0 || isAllSelected) || viewMode === 'grid'"
            class="w-full sm:flex-1 sm:max-w-md sm:mx-8 order-last sm:order-none">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input :value="searchTerm" @input="emit('update:searchTerm', ($event.target as HTMLInputElement).value)"
                    type="text" placeholder="Buscar" :class="{
                        'text-gray-500': !searchTerm,
                        'text-gray-900': searchTerm,
                    }"
                    class="w-full pl-10 pr-4 py-1.5 text-sm border-[0.5px] border-gray-300 rounded-lg shadow-sm focus:ring-[0.5px] focus:ring-teal-500 focus:border-teal-500" />
            </div>
        </div>

        <div v-if="(selectedCount > 0 || isAllSelected) && viewMode === 'list'"
            class="w-full sm:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2 order-last sm:order-none">
            <div
                class="flex-1 flex items-center gap-2 sm:gap-4 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100 justify-between">
                <span class="text-teal-500 font-medium text-sm whitespace-nowrap">{{ selectionMessage }}</span>

                <div class="flex items-center gap-2">
                    <button v-if="!isAllSelected && totalCount > selectedCount" @click="emit('select-all-total')"
                        class="text-blue-500 bg-blue-200 py-1 px-10 rounded hover:text-blue-600 font-medium flex items-center text-xs whitespace-nowrap">
                        Todos ({{ totalCount }})
                    </button>

                    <button @click="emit('clear-selection')" class="text-gray-400 hover:text-gray-600 p-1">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div class="relative" ref="actionsDropdownRef">
                <Button variant="secondary" size="sm" @click="showActionsDropdown = !showActionsDropdown"
                    class="w-full sm:w-auto justify-center">
                    <Settings class="w-4 h-4 mr-2" />
                    Acciones
                </Button>

                <div v-if="showActionsDropdown"
                    class="absolute top-full right-0 sm:right-0 left-0 sm:left-auto mt-2 w-full sm:w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50 py-1">
                    <button v-if="showQr" @click="emit('generate-qr'); showActionsDropdown = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <QrCode class="w-4 h-4" />
                        Generar QR
                    </button>
                    <button @click="emit('delete-selected'); showActionsDropdown = false"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                        <Trash2 class="w-4 h-4" />
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center sm:justify-end gap-4 w-full sm:w-auto">
            <Pagination v-if="pagination" :pagination="pagination" />

            <div v-if="!hideViewToggle" class="hidden sm:flex items-center bg-gray-100 rounded-lg p-1">
                <button @click="emit('update:viewMode', 'grid')" class="p-1.5 rounded-md transition-all shadow-sm"
                    :class="viewMode === 'grid'
                        ? 'bg-white text-gray-900'
                        : 'text-gray-500 hover:text-gray-900'
                        ">
                    <LayoutGrid class="w-5 h-5" />
                </button>
                <button @click="emit('update:viewMode', 'list')" class="p-1.5 rounded-md transition-all shadow-sm"
                    :class="viewMode === 'list'
                        ? 'bg-white text-gray-900'
                        : 'text-gray-500 hover:text-gray-900'
                        ">
                    <List class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
