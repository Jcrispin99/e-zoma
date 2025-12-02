<script setup lang="ts">
defineProps<{
  title: string;
  value: string | number;
  icon: string;
  iconBgColor: string;
  trend?: {
    direction: 'up' | 'down';
    value: string;
  };
}>();
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <div class="flex items-center justify-between mb-4">
      <div class="text-gray-500 text-sm font-medium">{{ title }}</div>
      <div :class="['p-2 rounded-lg', iconBgColor]">
        <i :class="['fas', `fa-${icon}`, 'text-white']"></i>
      </div>
    </div>
    <div class="flex items-end justify-between">
      <div class="text-2xl font-bold text-gray-800">{{ value }}</div>
      <div
        v-if="trend"
        :class="[
          'text-sm font-medium flex items-center',
          trend.direction === 'up' ? 'text-emerald-500' : 'text-red-500',
        ]"
      >
        <span>{{ trend.value }}</span>
        <span class="ml-1">{{ trend.direction === 'up' ? '↑' : '↓' }}</span>
      </div>
    </div>
  </div>
</template>
