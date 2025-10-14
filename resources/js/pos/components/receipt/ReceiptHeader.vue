<script setup>
const props = defineProps({
  company: { type: Object, default: () => ({ name: 'E-Zoma' }) },
  sessionId: { type: [String, Number], required: true },
  type: { type: String, default: 'receipt' },
  serie: { type: String, default: '' },
  correlative: { type: [String, Number], default: '' },
  date: { type: String, default: '' },
  customer: { type: Object, default: () => ({ name: 'Consumidor Final' }) },
  isOffline: { type: Boolean, default: false },
});
</script>

<template>
  <div class="px-6 py-4 border-b">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-lg font-semibold">{{ props.company?.name }}</div>
        <div class="text-xs text-gray-600">Sesión POS #{{ props.sessionId }}</div>
      </div>
      <div class="text-right">
        <div class="text-sm">{{ props.type === 'invoice' ? 'Factura' : 'Boleta' }}</div>
        <div class="text-xl font-bold">Serie: {{ props.serie || '—' }}</div>
        <div class="text-sm">Nº: {{ props.correlative || (props.isOffline ? 'Provisional' : '—') }}</div>
      </div>
    </div>
    <div class="mt-3 text-sm flex justify-between">
      <div>
        <div class="text-gray-600">Cliente</div>
        <div class="font-medium">{{ props.customer?.name }}</div>
      </div>
      <div class="text-right">
        <div class="text-gray-600">Fecha</div>
        <div class="font-medium">{{ new Date(props.date || Date.now()).toLocaleString() }}</div>
      </div>
    </div>
    <div v-if="props.isOffline" class="mt-2 text-xs text-orange-600">
      Modo offline: documento provisional. Se asignará numeración oficial al sincronizar.
    </div>
  </div>
</template>