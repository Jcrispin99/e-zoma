<script setup lang="ts">
import { type Component, computed } from 'vue';

const props = withDefaults(defineProps<{
    title: string;
    value?: string | number;
    icon?: Component;
    color?: 'blue' | 'green' | 'red' | 'indigo' | 'purple' | 'orange' | 'gray';
    iconClass?: string;
}>(), {
    color: 'indigo'
});

const colorClasses = computed(() => {
    const map: Record<string, string> = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-green-50 text-green-600',
        red: 'bg-red-50 text-red-600',
        indigo: 'bg-indigo-50 text-indigo-600',
        purple: 'bg-purple-50 text-purple-600',
        orange: 'bg-orange-50 text-orange-600',
        gray: 'bg-gray-50 text-gray-600',
    };
    return map[props.color] || map.indigo;
});
</script>

<template>
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
        <div class="flex items-center gap-4">
            <div v-if="icon" class="p-3 rounded-lg flex-shrink-0" :class="colorClasses">
                <component :is="icon" class="w-6 h-6" :class="iconClass" />
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ title }}</p>
                <div class="text-2xl font-bold text-gray-900 mt-0.5">
                    <slot name="value">
                        {{ typeof value === 'number' ? value.toLocaleString() : value }}
                    </slot>
                </div>
            </div>
        </div>
    </div>
</template>
