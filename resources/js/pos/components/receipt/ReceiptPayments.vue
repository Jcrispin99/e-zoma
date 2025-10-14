<script setup>
const props = defineProps({
  payments: { type: Array, default: () => [] },
});
</script>

<template>
  <div class="px-6 py-3 text-sm">
    <div class="text-gray-600 mb-1">Pagos</div>
    <ul>
      <li v-for="(p, idx) in props.payments" :key="idx" class="flex justify-between">
        <span>{{ p.name || 'Método' }}</span>
        <span>{{ Number(p.amount || 0).toFixed(2) }}</span>
      </li>
    </ul>
  </div>
</template>