<script setup lang="ts">
import { type Component } from 'vue';

defineProps<{
    title: string;
    value: string | number;
    icon?: Component;
    iconClass?: string;
    tooltip?: string;
}>();
</script>

<template>
    <div
        class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 relative group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-gray-500 text-sm font-medium">{{ title }}</span>
            <div v-if="icon" class="p-2 rounded-lg bg-gray-50 group-hover:bg-gray-100 transition-colors">
                <component :is="icon" class="w-5 h-5" :class="iconClass" />
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-800 tracking-tight" :title="tooltip || String(value)">
            {{ typeof value === 'number' ? value.toLocaleString() : value }}
        </div>
    </div>
</template>
