<script setup>
const props = defineProps({
  subtotal: { type: Number, default: 0 },
  tax: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
  loyalty: { type: Object, default: null },
});
</script>

<template>
  <div class="px-6 py-3 text-sm">
    <div class="flex justify-end">
      <div class="w-64">
        <div class="flex justify-between">
          <span>Subtotal</span>
          <span>{{ Number(props.subtotal || 0).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>IGV</span>
          <span>{{ Number(props.tax || 0).toFixed(2) }}</span>
        </div>
        <!-- Descuento por puntos (si existe) -->
        <div
          v-if="props.loyalty && Number(props.loyalty.discount_amount || 0) > 0"
          class="flex justify-between text-red-600"
        >
          <span>Descuento puntos</span>
          <span
            >-{{ Number(props.loyalty.discount_amount || 0).toFixed(2) }}</span
          >
        </div>
        <div class="flex justify-between font-semibold">
          <span>Total</span>
          <span>{{ Number(props.total || 0).toFixed(2) }}</span>
        </div>
        <!-- Puntos ganados (si existe) -->
        <div
          v-if="props.loyalty && Number(props.loyalty.points_earned || 0) > 0"
          class="flex justify-between"
        >
          <span>Puntos ganados</span>
          <span>{{ Number(props.loyalty.points_earned || 0) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
