<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  modelValue?: string;
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  rows?: number;
}>();

const emit = defineEmits(['update:modelValue']);

const value = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <div class="w-full">
    <textarea
      v-model="value"
      :rows="rows || 3"
      :placeholder="placeholder"
      :disabled="disabled"
      class="w-full border-0 border-b border-gray-300 focus:border-teal-500 focus:ring-0 px-0 py-2 bg-transparent transition-colors placeholder-gray-300 text-gray-900 resize-none"
      :class="{ 'border-red-500 focus:border-red-500': error }"
    ></textarea>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>
