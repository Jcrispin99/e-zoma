<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getCache } from '../composables/useCache.js';
import { useSessionStore } from '../stores/useSessionStore.js';
import ReceiptHeader from '../components/receipt/ReceiptHeader.vue';
import ReceiptItems from '../components/receipt/ReceiptItems.vue';
import ReceiptTotals from '../components/receipt/ReceiptTotals.vue';
import ReceiptPayments from '../components/receipt/ReceiptPayments.vue';

const route = useRoute();
const router = useRouter();
const sessionStore = useSessionStore();

// Referencia de boleta en cache
const refKey = route.params.ref || 'last';
const receipt =
  getCache(`pos:receipt:${refKey}`, null) ||
  getCache('pos:receipt:last', null) ||
  {};

function goHome() {
  router.push({ name: 'pos-session', params: { id: route.params.id } });
}

onMounted(() => {
  // Auto imprimir al abrir
  setTimeout(() => window.print(), 300);
});
</script>

<template>
  <div class="receipt-print-root">
    <div class="receipt min-h-screen bg-white text-black">
      <ReceiptHeader
        :company="sessionStore.company"
        :session-id="route.params.id"
        :type="receipt.type"
        :serie="receipt.serie"
        :correlative="receipt.correlative"
        :date="receipt.date"
        :customer="receipt.customer"
        :is-offline="receipt.isOffline"
      />

      <ReceiptItems :items="receipt.items || []" />

      <ReceiptTotals
        :subtotal="Number(receipt.subtotal || receipt.total || 0)"
        :tax="Number(receipt.tax || 0)"
        :total="Number(receipt.total || 0)"
        :loyalty="receipt.loyalty || null"
      />
      <ReceiptPayments :payments="receipt.payments || []" />

      <!-- Acciones -->
      <div class="px-6 py-4 border-t flex gap-2 print:hidden">
        <button class="px-3 py-2 rounded bg-gray-100 border" @click="goHome">
          Nueva venta
        </button>
        <button
          class="px-3 py-2 rounded bg-blue-600 text-white"
          @click="() => window.print()"
        >
          Imprimir
        </button>
      </div>
    </div>
  </div>
</template>

<style>
/* Tamaño de página para impresora térmica (ajusta a 58mm si lo prefieres) */
@page {
  size: 80mm auto;
  margin: 0;
}

/* Ocultar todo excepto la raíz de la boleta al imprimir */
@media print {
  #app > *:not(.receipt-print-root) {
    display: none !important;
  }
  .receipt-print-root {
    display: block !important;
  }
}
</style>

<style scoped>
.receipt {
  width: 80mm;
  margin: 0 auto;
}
@media print {
  .receipt {
    width: 80mm;
  }
  .print\:hidden {
    display: none !important;
  }
}
</style>
