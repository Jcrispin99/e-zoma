<script setup lang="ts">
import { computed } from 'vue';

interface Option {
  value: string | number;
  label: string;
}

const props = defineProps<{
  modelValue?: string | number;
  options: Option[];
  placeholder?: string;
  disabled?: boolean;
  error?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const value = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <div class="w-full">
    <select
      v-model="value"
      :disabled="disabled"
      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 text-sm"
      :class="{
        'border-red-500 focus:border-red-500 focus:ring-red-500': error,
        'text-gray-500': !value,
      }"
    >
      <option value="" disabled selected v-if="placeholder">
        {{ placeholder }}
      </option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        class="text-gray-900"
      >
        {{ option.label }}
      </option>
    </select>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>
