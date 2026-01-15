<script setup lang="ts">
import { ref, watch, useSlots } from 'vue';
import Button from '@/components/ui/Button.vue';
import { CloudUpload, RotateCcw } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    subtitle?: string;
    tabs?: { id: string; label: string }[];
    loading?: boolean;
    activeTab?: string;
    disabled?: boolean;
    backRoute?: string;
    hideDefaultActions?: boolean;
    breadcrumbs?: { label: string; route?: string }[];
}>();

const emit = defineEmits(['submit', 'cancel', 'update:activeTab']);

const currentTab = ref(
    props.activeTab || (props.tabs && props.tabs[0]?.id) || ''
);

const defaultBackRoute = '/finanzas/inventario/productos';

watch(
    () => props.activeTab,
    (val) => {
        if (val) currentTab.value = val;
    }
);

const selectTab = (id: string) => {
    currentTab.value = id;
    emit('update:activeTab', id);
};

const slots = useSlots();
</script>

<template>
    <div class="max-w-9xl mx-auto bg-white">
        <div class="flex flex-col items-start px-7 py-1 gap-2 border-b border-gray-200 -mt-1">
            <div class="flex items-center justify-between w-full">
                <div class="flex flex-col gap-1 mb-2">
                    <h1 class="text-sm font-medium text-teal-600 flex items-center gap-1">
                        <template v-if="breadcrumbs && breadcrumbs.length">
                            <template v-for="(crumb, index) in breadcrumbs" :key="index">
                                <span v-if="index > 0">/</span>
                                <span v-if="crumb.route" @click="router.visit(crumb.route)"
                                    class="cursor-pointer hover:underline">
                                    {{ crumb.label }}
                                </span>
                                <span v-else>
                                    {{ crumb.label }}
                                </span>
                            </template>
                        </template>
                        <template v-else>
                            <span @click="router.visit(backRoute || defaultBackRoute)"
                                class="cursor-pointer hover:underline">{{ title }}</span> <span v-if="subtitle">/ {{
                                    subtitle
                                }}
                            </span>
                        </template>
                    </h1>
                    <div class="flex items-center gap-2">
                        <template v-if="!hideDefaultActions">
                            <Button @click="$emit('submit')" :disabled="loading || disabled" title="Guardar">
                                <CloudUpload class="w-4 h-4 mr-2" />
                                Guardar
                            </Button>
                            <Button @click.prevent="$emit('cancel')" variant="secondary" title="Descartar"
                                :disabled="disabled">
                                <RotateCcw class="w-4 h-4 mr-2" />
                                Descartar
                            </Button>
                        </template>
                        <slot name="actions" />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <slot name="header-actions" />
                </div>
            </div>
        </div>

        <div class="px-8 py-6">
            <div class="border border-gray-200 rounded-lg p-6">
                <div v-if="slots['top-left'] || slots['top-right']" class="flex gap-8 items-start mb-6">
                    <div class="flex-1 w-full">
                        <slot name="top-left" />
                    </div>

                    <div v-if="slots['top-right']" class="w-32">
                        <div
                            class="w-32 h-32 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center relative group cursor-pointer hover:bg-gray-100 transition-colors">
                            <slot name="top-right" />
                        </div>
                    </div>
                </div>

                <div v-if="tabs && tabs.length" class="border-b border-gray-200 mb-6">
                    <nav class="flex -mb-px space-x-8 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                        <button v-for="tab in tabs" :key="tab.id" @click="selectTab(tab.id)" :class="[
                            currentTab === tab.id
                                ? 'border-teal-500 text-teal-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        ]">
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div v-if="tabs && tabs.length">
                    <div v-for="tab in tabs" :key="tab.id" v-show="currentTab === tab.id">
                        <slot :name="tab.id" />
                    </div>
                </div>

                <slot v-else />
            </div>
        </div>
    </div>
</template>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
    overflow-y: scroll;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
