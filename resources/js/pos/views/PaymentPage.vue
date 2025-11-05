<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSessionStore } from '../stores/useSessionStore.js';
import { getCache, setCache } from '../composables/useCache.js';

const route = useRoute();
const router = useRouter();
const sessionStore = useSessionStore();
const paymentMethods = ref([]);
const paymentAmounts = ref({});

// Emitir evento al padre para limpiar carrito tras pagar
const emit = defineEmits(['clear-cart']);

// Datos del carrito persistidos antes de navegar
const cached = getCache('pos:checkout', {
  items: [],
  subtotal: 0,
  tax: 0,
  total: 0,
});
const orderLines = ref(cached.items || []);
const orderSubtotal = ref(Number(cached.subtotal || 0));
const orderTax = ref(Number(cached.tax || 0));
const orderTotal = ref(Number(cached.total || 0));
const loyaltyMeta = ref(cached.loyalty || null);

// Selección de tipo de documento
const docType = ref('boleta'); // "boleta" | "factura"

// Códigos de diarios configurados
const receiptJournalCode = computed(
  () => sessionStore.sequences?.receipt?.serie_code || null
);
const invoiceJournalCode = computed(
  () => sessionStore.sequences?.invoice?.serie_code || null
);
// Código del diario según el tipo seleccionado
const currentJournalCode = computed(() => {
  const key = docType.value === 'factura' ? 'invoice' : 'receipt';
  return sessionStore.sequences?.[key]?.serie_code || null;
});

// Cliente seleccionado (usar el seleccionado del store o el default)
const customer = computed(
  () => sessionStore.selectedCustomer || sessionStore.defaultCustomer || null
);

// Métodos de pago y montos
const paidTotal = computed(() => {
  const cents = Object.values(paymentAmounts.value).reduce(
    (sum, amt) => sum + Math.round(Number(amt || 0) * 100),
    0
  );
  return cents / 100;
});
const remaining = computed(() =>
  Math.max(0, Math.round((orderTotal.value - paidTotal.value) * 100) / 100)
);
const change = computed(() =>
  Math.max(0, Math.round((paidTotal.value - orderTotal.value) * 100) / 100)
);

// Etiqueta IGV con porcentaje
const igvLabel = computed(() => {
  const apply = !!sessionStore.config?.apply_tax;
  const rate = Number(sessionStore.config?.tax_rate ?? 0);
  return apply && rate > 0 ? `IGV (${(rate * 100).toFixed(0)}%)` : 'IGV';
});

function selectDoc(type) {
  docType.value = type;
}

function selectCustomer() {
  // Placeholder: usar cliente por defecto del config
  customer.value = sessionStore.defaultCustomer || customer.value;
}

function fillMethodExact(id) {
  const current = Number(paymentAmounts.value[id] || 0);
  const next = current + remaining.value;
  paymentAmounts.value[id] = Math.round(next * 100) / 100;
}

function roundMethodAmount(id) {
  const val = Number(paymentAmounts.value[id] || 0);
  paymentAmounts.value[id] =
    Math.round((Number.isFinite(val) ? val : 0) * 100) / 100;
}

function goBack() {
  router.push({ name: 'pos-session', params: { id: route.params.id } });
}

// Util para origen backend (Blade inyecta meta)
const backendOrigin =
  document.querySelector('meta[name="backend-origin"]')?.content ||
  window.location.origin;

async function fetchPaymentMethods() {
  try {
    // Asegurar cookie CSRF para peticiones stateful
    if (!sessionStore.getXsrfToken()) {
      await fetch(new URL('/sanctum/csrf-cookie', backendOrigin), {
        credentials: 'include',
      });
    }
    const token = sessionStore.getXsrfToken();
    const res = await fetch(new URL('/api/payment-methods', backendOrigin), {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        ...(token ? { 'X-XSRF-TOKEN': token } : {}),
      },
    });
    if (!res.ok) {
      const err = new Error(`HTTP ${res.status}`);
      err.status = res.status;
      throw err;
    }
    const data = await res.json();
    paymentMethods.value = Array.isArray(data) ? data : [];
    // Inicializar montos y método activo
    paymentAmounts.value = {};
    paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));
    // Cachear métodos de pago para modo offline
    setCache('pos:paymentMethods', paymentMethods.value);
  } catch (e) {
    console.error('Error al obtener métodos de pago:', e);
    // Si es 401, no marcar offline; mostrar vacío para que se vea el contenedor
    if (e && e.status === 401) {
      paymentMethods.value = [];
      paymentAmounts.value = {};
      return;
    }
    // Fallback offline: usar cache local si existe
    sessionStore.setOnline(false);
    const cached = getCache('pos:paymentMethods', []);
    paymentMethods.value = Array.isArray(cached) ? cached : [];
    paymentAmounts.value = {};
    paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));
  }
}

onMounted(() => {
  fetchPaymentMethods();
});

async function pay() {
  // Validación básica
  if (orderTotal.value <= 0 || orderLines.value.length === 0) {
    alert('No hay productos para pagar.');
    return;
  }
  if (paidTotal.value < orderTotal.value) {
    alert('El monto pagado es insuficiente.');
    return;
  }

  // Construir payload compatible con contrato del backend
  const payments = [];
  for (const [id, amount] of Object.entries(paymentAmounts.value)) {
    if (Number(amount) > 0) {
      payments.push({
        payment_method_id: Number(id),
        amount: Number(amount),
      });
    }
  }

  const lines = orderLines.value.map((item) => ({
    variant_id: item.id, // en el carrito usamos variant.id
    quantity: Number(item.quantity || 1),
    price: Number(item.price || 0),
    subtotal: Number(item.price || 0) * Number(item.quantity || 1),
  }));

  const payload = {
    customer_id: customer.value?.id || sessionStore.defaultCustomer?.id,
    voucher_type: docType.value === 'factura' ? 'invoice' : 'receipt',
    lines,
    payments,
    total_amount: orderTotal.value,
  };

  // Incluir datos de lealtad si están en cache
  if (loyaltyMeta.value) {
    payload.loyalty = {
      points_spent: Number(loyaltyMeta.value.points_spent || 0),
      discount_amount: Number(loyaltyMeta.value.discount_amount || 0),
      points_earned: Number(loyaltyMeta.value.points_earned || 0),
    };
  }

  try {
    const result = await sessionStore.sync([payload]);
    console.log('Sync OK', result);
    const wasOffline = !sessionStore.online || !(result?.synced?.length > 0);
    if (wasOffline) {
      // Tratar como modo offline si no hay registros sincronizados
      const counter = getCache('pos:offlineReceiptCounter', { next: 1 });
      const offlineCorrelative = String(counter.next).padStart(8, '0');
      const key = payload.voucher_type;
      const serieCode = sessionStore.sequences?.[key]?.serie_code || 'LOCAL';
      const receiptRef = `offline-${Date.now()}`;
      const receiptData = {
        type: payload.voucher_type,
        serie: serieCode,
        correlative: offlineCorrelative,
        date: new Date().toISOString(),
        customer: customer.value || sessionStore.defaultCustomer,
        items: orderLines.value.map((item) => ({
          name: item.name || item.product_name || `Var #${item.id}`,
          variant_id: item.id,
          quantity: Number(item.quantity || 1),
          price: Number(item.price || 0),
          subtotal: Number(item.price || 0) * Number(item.quantity || 1),
        })),
        payments: payments.map((p) => ({
          name:
            paymentMethods.value.find((m) => m.id === p.payment_method_id)
              ?.name || 'Método',
          amount: p.amount,
        })),
        subtotal: orderSubtotal.value,
        tax: orderTax.value,
        total: orderTotal.value,
        isOffline: true,
        loyalty: loyaltyMeta.value
          ? {
              points_spent: Number(loyaltyMeta.value.points_spent || 0),
              discount_amount: Number(loyaltyMeta.value.discount_amount || 0),
              points_earned: Number(loyaltyMeta.value.points_earned || 0),
            }
          : null,
      };
      setCache('pos:offlineReceiptCounter', {
        next: Number(counter.next || 1) + 1,
      });
      setCache(`pos:receipt:${receiptRef}`, receiptData);
      setCache('pos:receipt:last', receiptData);
      emit('clear-cart');
      router.push({
        name: 'pos-receipt',
        params: { id: route.params.id, ref: receiptRef },
      });
    } else {
      // Flujo online
      const info = result?.synced?.[0] || {};
      const receiptRef = info.sale_id || String(Date.now());
      const receiptData = {
        type: payload.voucher_type,
        serie:
          info.serie ||
          sessionStore.sequences?.[payload.voucher_type]?.serie_code ||
          '',
        correlative: info.correlative || null,
        date: new Date().toISOString(),
        customer: customer.value || sessionStore.defaultCustomer,
        items: orderLines.value.map((item) => ({
          name: item.name || item.product_name || `Var #${item.id}`,
          variant_id: item.id,
          quantity: Number(item.quantity || 1),
          price: Number(item.price || 0),
          subtotal: Number(item.price || 0) * Number(item.quantity || 1),
        })),
        payments: payments.map((p) => ({
          name:
            paymentMethods.value.find((m) => m.id === p.payment_method_id)
              ?.name || 'Método',
          amount: p.amount,
        })),
        subtotal: orderSubtotal.value,
        tax: orderTax.value,
        total: orderTotal.value,
        isOffline: false,
        loyalty: loyaltyMeta.value
          ? {
              points_spent: Number(loyaltyMeta.value.points_spent || 0),
              discount_amount: Number(loyaltyMeta.value.discount_amount || 0),
              points_earned: Number(loyaltyMeta.value.points_earned || 0),
            }
          : null,
      };
      setCache(`pos:receipt:${receiptRef}`, receiptData);
      setCache('pos:receipt:last', receiptData);
      emit('clear-cart');
      router.push({
        name: 'pos-receipt',
        params: { id: route.params.id, ref: receiptRef },
      });
    }
  } catch (e) {
    console.error('Error al sincronizar:', e);
    // Fallback OFFLINE: generar numeración local y permitir imprimir
    try {
      const counter = getCache('pos:offlineReceiptCounter', { next: 1 });
      const offlineCorrelative = String(counter.next).padStart(8, '0');
      const key = payload.voucher_type;
      const serieCode = sessionStore.sequences?.[key]?.serie_code || 'LOCAL';
      const receiptRef = `offline-${Date.now()}`;
      const receiptData = {
        type: payload.voucher_type,
        serie: serieCode,
        correlative: offlineCorrelative,
        date: new Date().toISOString(),
        customer: customer.value || sessionStore.defaultCustomer,
        items: orderLines.value.map((item) => ({
          name: item.name || item.product_name || `Var #${item.id}`,
          variant_id: item.id,
          quantity: Number(item.quantity || 1),
          price: Number(item.price || 0),
          subtotal: Number(item.price || 0) * Number(item.quantity || 1),
        })),
        payments: payments.map((p) => ({
          name:
            paymentMethods.value.find((m) => m.id === p.payment_method_id)
              ?.name || 'Método',
          amount: p.amount,
        })),
        subtotal: orderSubtotal.value,
        tax: orderTax.value,
        total: orderTotal.value,
        isOffline: true,
        loyalty: loyaltyMeta.value
          ? {
              points_spent: Number(loyaltyMeta.value.points_spent || 0),
              discount_amount: Number(loyaltyMeta.value.discount_amount || 0),
              points_earned: Number(loyaltyMeta.value.points_earned || 0),
            }
          : null,
      };
      setCache('pos:offlineReceiptCounter', {
        next: Number(counter.next || 1) + 1,
      });
      setCache(`pos:receipt:${receiptRef}`, receiptData);
      setCache('pos:receipt:last', receiptData);
      // Emitir limpieza de carrito antes de navegar
      emit('clear-cart');
      router.push({
        name: 'pos-receipt',
        params: { id: route.params.id, ref: receiptRef },
      });
    } catch (inner) {
      alert(
        `Error al registrar el pago: ${(e && e.message) || e}.` +
          ` También falló el modo offline: ${(inner && inner.message) || inner}`
      );
    }
  }
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Header simple -->
    <div class="flex items-center justify-between px-4 py-2 border-b bg-white">
      <div class="text-sm text-gray-600">Sesión #{{ route.params.id }}</div>
      <button class="text-blue-600 hover:underline text-sm" @click="goBack">
        ← Volver
      </button>
    </div>
    <!-- Barra superior nueva con resumen y acciones -->
    <div
      class="sticky top-0 z-10 bg-white border-t border-b py-2 px-4 flex items-center justify-between"
    >
      <div class="flex items-center gap-6">
        <div class="text-sm">
          <span class="text-gray-600">Total:</span>
          <span class="font-semibold">S/ {{ orderTotal.toFixed(2) }}</span>
        </div>
        <div class="text-sm">
          <span class="text-gray-600">Pagado:</span>
          <span class="font-semibold">S/ {{ paidTotal.toFixed(2) }}</span>
        </div>
        <div class="text-sm">
          <span class="text-gray-600">Restante:</span>
          <span
            class="font-semibold"
            :class="{
              'text-red-600': remaining > 0,
              'text-green-600': remaining === 0,
            }"
            >S/ {{ remaining.toFixed(2) }}</span
          >
        </div>
        <div v-if="change > 0" class="text-sm">
          <span class="text-gray-600">Vuelto:</span>
          <span class="font-semibold">S/ {{ change.toFixed(2) }}</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="px-4 py-2 rounded border" @click="goBack">
          Regresar
        </button>
        <button
          class="px-4 py-2 rounded bg-green-600 text-white disabled:opacity-50"
          :disabled="paidTotal < orderTotal"
          @click="pay"
        >
          Pagar
        </button>
      </div>
    </div>

    <div class="flex-1 grid grid-cols-2 gap-4 p-4 bg-gray-50">
      <!-- Izquierda: Tipo de documento y cliente -->
      <div class="bg-white border rounded-lg p-4 flex flex-col">
        <h3 class="font-semibold mb-3">Documento</h3>
        <div class="flex gap-2 mb-2">
          <button
            :class="[
              'px-3 py-2 rounded border',
              docType === 'boleta'
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-700',
            ]"
            @click="selectDoc('boleta')"
          >
            Boleta ({{ receiptJournalCode || 'No configurado' }})
          </button>
          <button
            :class="[
              'px-3 py-2 rounded border',
              docType === 'factura'
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-700',
            ]"
            @click="selectDoc('factura')"
          >
            Factura ({{ invoiceJournalCode || 'No configurado' }})
          </button>
        </div>
        <div class="text-xs text-gray-500 mb-4">
          Código del diario: {{ currentJournalCode || 'No configurado' }}
        </div>

        <h3 class="font-semibold mb-2">Cliente</h3>
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-600 truncate">
            {{
              customer?.name ||
              sessionStore.defaultCustomer?.name ||
              'Sin cliente seleccionado'
            }}
          </div>
          <button
            class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 border"
            @click="selectCustomer"
          >
            Seleccionar cliente
          </button>
        </div>

        <div class="mt-auto text-sm text-gray-500">
          <div class="flex justify-between">
            <span>Subtotal</span><span>{{ orderSubtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>{{ igvLabel }}</span
            ><span>{{ orderTax.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-semibold">
            <span>Total</span><span>{{ orderTotal.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Derecha: Métodos de pago y acción -->
      <div class="bg-white border rounded-lg p-4 flex flex-col">
        <h3 class="font-semibold mb-3">Método de pago</h3>
        <div class="space-y-2">
          <div
            v-if="paymentMethods.length === 0"
            class="p-3 text-sm text-gray-600 border rounded"
          >
            No hay métodos de pago disponibles.
            <span class="block text-xs text-gray-500 mt-1"
              >Verifica que estés autenticado y que existan métodos activos en
              Admin → Métodos de pago.</span
            >
          </div>
          <div
            v-for="m in paymentMethods"
            :key="m.id"
            class="flex items-center justify-between border rounded px-3 py-2"
          >
            <div class="min-w-0">
              <div class="text-sm font-medium truncate">{{ m.name }}</div>
              <div class="text-xs text-gray-500">Monto</div>
            </div>
            <div class="flex items-center gap-2">
              <input
                type="number"
                min="0"
                step="0.01"
                inputmode="decimal"
                class="w-28 border rounded px-2 py-1 text-right"
                v-model.number="paymentAmounts[m.id]"
                @blur="roundMethodAmount(m.id)"
              />
              <button
                class="px-2 py-1 rounded bg-gray-100 text-gray-700 border"
                @click="fillMethodExact(m.id)"
              >
                Restante
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
