<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="close"
  >
    <div class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
      <div class="flex items-start justify-between border-b pb-4">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ title }}
        </h3>
        <button
          type="button"
          class="-my-2 -mr-2 rounded-full p-2 text-gray-400 hover:bg-gray-200 hover:text-gray-600"
          @click="close"
        >
          <span class="sr-only">Cerrar modal</span>
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div class="mt-4">
        <slot />
      </div>
      <div v-if="hasFooterSlot" class="mt-6 border-t pt-4">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useSlots } from 'vue';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Modal',
  },
});

const emit = defineEmits(['close']);

const slots = useSlots();
const hasFooterSlot = !!slots.footer;

const close = () => {
  emit('close');
};
</script>