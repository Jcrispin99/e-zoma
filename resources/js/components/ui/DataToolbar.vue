<script setup lang="ts">
import {
    Plus,
    LayoutGrid,
    List,
    X,
    ChevronLeft,
    ChevronRight,
    Settings,
    Trash2,
    Search,
} from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import { router } from '@inertiajs/vue3';

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
}

withDefaults(defineProps<Props>(), {
    newLabel: 'Nuevo',
    selectedCount: 0,
    totalCount: 0,
    isAllSelected: false,
    selectionMessage: '',
});

const emit = defineEmits<{
    (e: 'update:searchTerm', value: string): void;
    (e: 'update:viewMode', value: 'grid' | 'list'): void;
    (e: 'select-all-total'): void;
    (e: 'clear-selection'): void;
    (e: 'delete-selected'): void;
}>();

import { ref, onMounted, onUnmounted } from 'vue';

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

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="flex flex-wrap justify-between items-center gap-4 px-4 sm:px-7 mt-2">
        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-start">
            <div class="flex items-center gap-4">
                <Button @click="router.visit(newRoute)" class="-mr-2">
                    <Plus class="w-4 h-4 mr-2" />
                    {{ newLabel }}
                </Button>
                <h1 class="text-sm font-medium text-teal-600">
                    {{ title }}
                </h1>
            </div>

            <div class="flex sm:hidden items-center bg-white rounded-lg border border-gray-200 p-1">
                <button @click="emit('update:viewMode', 'grid')" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'grid'
                    ? 'bg-gray-100 text-gray-700'
                    : 'text-gray-500 hover:text-gray-700'
                    ">
                    <LayoutGrid class="w-5 h-5" />
                </button>
                <button @click="emit('update:viewMode', 'list')" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'list'
                    ? 'bg-gray-100 text-gray-700'
                    : 'text-gray-500 hover:text-gray-700'
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
                    type="text" placeholder="Buscar..." :class="{
                        'text-gray-500': !searchTerm,
                        'text-gray-900': searchTerm,
                    }"
                    class="w-full pl-10 pr-4 py-2 text-sm border-[0.5px] border-gray-300 rounded-lg shadow-sm focus:ring-[0.5px] focus:ring-teal-500 focus:border-teal-500" />
            </div>
        </div>

        <div v-if="(selectedCount > 0 || isAllSelected) && viewMode === 'list'"
            class="w-full sm:w-auto flex flex-wrap items-center gap-2 order-last sm:order-none">
            <div
                class="flex-1 sm:flex-none flex items-center gap-4 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100 justify-between sm:justify-start">
                <span class="text-teal-500 font-medium text-sm sm:text-base">{{ selectionMessage }}</span>

                <div class="flex items-center gap-2">
                    <button v-if="!isAllSelected && totalCount > selectedCount" @click="emit('select-all-total')"
                        class="text-blue-500 bg-blue-200 py-1 px-2 rounded hover:text-blue-600 font-medium flex items-center text-xs sm:text-sm whitespace-nowrap">
                        Todos los {{ totalCount }}
                    </button>

                    <button @click="emit('clear-selection')" class="text-gray-400 hover:text-gray-600">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div class="relative" ref="actionsDropdownRef">
                <Button variant="secondary" size="sm" @click="showActionsDropdown = !showActionsDropdown">
                    <Settings class="w-4 h-4 mr-2" />
                    Acciones
                </Button>

                <div v-if="showActionsDropdown"
                    class="absolute top-full right-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50 py-1">
                    <button @click="emit('delete-selected'); showActionsDropdown = false"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                        <Trash2 class="w-4 h-4" />
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
            <div class="flex items-center gap-3" v-if="pagination && pagination.total > 0">
                <span class="text-sm text-gray-600 font-medium">
                    {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }}
                </span>
                <div class="flex items-center bg-white rounded-lg border border-gray-200 p-1">
                    <button :disabled="!pagination.prev_page_url"
                        @click="pagination.prev_page_url && router.visit(pagination.prev_page_url)"
                        class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                        :class="!pagination.prev_page_url ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <div class="w-[1px] h-4 bg-gray-200 mx-0.5"></div>
                    <button :disabled="!pagination.next_page_url"
                        @click="pagination.next_page_url && router.visit(pagination.next_page_url)"
                        class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                        :class="!pagination.next_page_url ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="hidden sm:flex items-center bg-white rounded-lg border border-gray-200 p-1">
                <button @click="emit('update:viewMode', 'grid')" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'grid'
                    ? 'bg-gray-100 text-gray-700'
                    : 'text-gray-500 hover:text-gray-700'
                    ">
                    <LayoutGrid class="w-5 h-5" />
                </button>
                <button @click="emit('update:viewMode', 'list')" class="p-1.5 rounded-md transition-colors" :class="viewMode === 'list'
                    ? 'bg-gray-100 text-gray-700'
                    : 'text-gray-500 hover:text-gray-700'
                    ">
                    <List class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
