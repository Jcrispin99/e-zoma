<script setup>
import { computed } from 'vue';
const props = defineProps({
  payments: { type: Array, default: () => [] },
  policies: { type: String, default: '' },
});
const paidTotal = computed(() =>
  (props.payments || []).reduce((s, p) => s + Number(p.amount || 0), 0)
);
</script>

<template>
  <div class="px-6 py-3 text-sm">
    <div class="text-gray-600 mb-1">Pagos</div>
    <ul class="space-y-1">
      <li
        v-for="(p, idx) in props.payments"
        :key="idx"
        class="flex justify-between"
      >
        <span class="truncate">{{ p.name || 'Método' }}</span>
        <span>{{ Number(p.amount || 0).toFixed(2) }}</span>
      </li>
    </ul>
    <div class="mt-2 flex justify-between border-t pt-2 font-medium">
      <span>Total pagado</span>
      <span>{{ paidTotal.toFixed(2) }}</span>
    </div>
    <!-- Políticas debajo de Total pagado -->
    <div
      v-if="props.policies"
      class="mt-2 text-xs text-gray-700 whitespace-pre-line"
    >
      <span class="font-semibold text-gray-800 block mb-1">{{
        props.policies
      }}</span>
    </div>
  </div>
</template>
