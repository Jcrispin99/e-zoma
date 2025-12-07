<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps<{
    pagination: {
        total: number;
        from: number;
        to: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    }
}>();
</script>

<template>
    <div class="flex items-center gap-3" v-if="pagination && pagination.total > 0">
        <span class="text-sm text-gray-600 font-medium">
            {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }}
        </span>
        <div class="flex items-center bg-white rounded-lg border border-gray-200 p-1">
            <button :disabled="!pagination.prev_page_url"
                @click="pagination.prev_page_url && router.visit(pagination.prev_page_url, { preserveState: true })"
                class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                :class="!pagination.prev_page_url ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                <ChevronLeft class="w-5 h-5" />
            </button>
            <div class="w-[1px] h-4 bg-gray-200 mx-0.5"></div>
            <button :disabled="!pagination.next_page_url"
                @click="pagination.next_page_url && router.visit(pagination.next_page_url, { preserveState: true })"
                class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                :class="!pagination.next_page_url ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                <ChevronRight class="w-5 h-5" />
            </button>
        </div>
    </div>
</template>
