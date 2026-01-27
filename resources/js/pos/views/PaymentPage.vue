<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import CustomerSelectModal from '../components/modals/CustomerSelectModal.vue';
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

const canInvoice = computed(() => {
  if (!invoiceJournalCode.value) return false;
  const doc = String(customer.value?.document_number || '').trim();
  return /^\d{11}$/.test(doc);
});

// Modal de cliente compartido
const showCustomerModal = ref(false);
function openCustomerModal() {
  showCustomerModal.value = true;
}
function handleCustomerSelected(c) {
  sessionStore.setSelectedCustomer(c);
  showCustomerModal.value = false;
}

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

// Estado de procesamiento
const isProcessing = ref(false);

// Método de pago activo (para flujo rápido)
const activePaymentMethod = ref(null);

// Montos rápidos para efectivo
const quickAmounts = computed(() => {
  const total = orderTotal.value;
  const amounts = [];

  // Redondear al múltiplo de 10 más cercano hacia arriba
  const roundedUp = Math.ceil(total / 10) * 10;

  if (roundedUp > total) amounts.push(roundedUp);
  if (roundedUp + 10 > total) amounts.push(roundedUp + 10);
  if (roundedUp + 20 > total) amounts.push(roundedUp + 20);
  if (roundedUp + 50 > total) amounts.push(roundedUp + 50);

  return amounts
    .filter((amt, idx, arr) => arr.indexOf(amt) === idx)
    .slice(0, 4);
});

function selectDoc(type) {
  if (type === 'factura' && !canInvoice.value) return;
  docType.value = type;
}

// Deprecated: ahora se usa openCustomerModal al hacer clic en el nombre
function selectCustomer() {}

function setActivePaymentMethod(method) {
  activePaymentMethod.value = method;
  // Auto-focus en el input cuando se selecciona un método
  setTimeout(() => {
    const input = document.querySelector(`#payment-input-${method.id}`);
    if (input) input.focus();
  }, 100);
}

function setQuickAmount(amount) {
  if (!activePaymentMethod.value) return;
  paymentAmounts.value[activePaymentMethod.value.id] = amount;
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
    // Inicializar montos
    paymentAmounts.value = {};
    paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));

    // Seleccionar automáticamente el primer método (típicamente efectivo)
    if (paymentMethods.value.length > 0) {
      activePaymentMethod.value = paymentMethods.value[0];
    }

    // Cachear métodos de pago para modo offline
    setCache('pos:paymentMethods', paymentMethods.value);
  } catch (e) {
    console.error('Error al obtener métodos de pago:', e);
    if (e && e.status === 401) {
      paymentMethods.value = [];
      paymentAmounts.value = {};
      return;
    }
    sessionStore.setOnline(false);
    const cached = getCache('pos:paymentMethods', []);
    paymentMethods.value = Array.isArray(cached) ? cached : [];
    paymentAmounts.value = {};
    paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));
    if (paymentMethods.value.length > 0) {
      activePaymentMethod.value = paymentMethods.value[0];
    }
  }
}

onMounted(() => {
  fetchPaymentMethods();
});

watch(
  () => canInvoice.value,
  (ok) => {
    if (!ok && docType.value === 'factura') {
      docType.value = 'boleta';
    }
  },
  { immediate: true }
);

// Atajos de teclado
function handleKeydown(e) {
  // Enter para pagar si está completo el monto
  if (
    e.key === 'Enter' &&
    paidTotal.value >= orderTotal.value &&
    !isProcessing.value
  ) {
    e.preventDefault();
    pay();
  }

  // Escape para regresar
  if (e.key === 'Escape' && !isProcessing.value) {
    e.preventDefault();
    goBack();
  }

  // F1-F4 para métodos de pago rápidos
  if (e.key.startsWith('F') && !e.ctrlKey && !e.altKey) {
    const num = parseInt(e.key.substring(1));
    if (num >= 1 && num <= paymentMethods.value.length) {
      e.preventDefault();
      setActivePaymentMethod(paymentMethods.value[num - 1]);
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

watch(
  () => route.name,
  (newName) => {
    if (newName !== 'pos-checkout') {
      window.removeEventListener('keydown', handleKeydown);
    }
  }
);

async function pay() {
  if (isProcessing.value) return;

  // Validación básica
  if (orderTotal.value <= 0 || orderLines.value.length === 0) {
    alert('No hay productos para pagar.');
    return;
  }
  if (paidTotal.value < orderTotal.value) {
    alert('El monto pagado es insuficiente.');
    return;
  }

  isProcessing.value = true;

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
    variant_id: item.id,
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
      emit('clear-cart');
      router.push({
        name: 'pos-receipt',
        params: { id: route.params.id, ref: receiptRef },
      });
    } catch (inner) {
      isProcessing.value = false;
      alert(
        `Error al registrar el pago: ${(e && e.message) || e}.` +
          ` También falló el modo offline: ${(inner && inner.message) || inner}`
      );
    }
  }
}
</script>

<template>
  <div class="h-full flex flex-col bg-gray-50">
    <!-- Header con total destacado -->
    <div class="bg-white border-b shadow-sm">
      <div class="px-6 py-4">
        <div class="flex items-center justify-between">
          <!-- Total a pagar (más prominente) -->
          <div>
            <div class="text-sm text-gray-600 mb-1">Total a pagar</div>
            <div class="text-4xl font-bold text-gray-900">
              S/ {{ orderTotal.toFixed(2) }}
            </div>
            <div class="flex gap-4 mt-2 text-sm text-gray-600">
              <span>Subtotal: S/ {{ orderSubtotal.toFixed(2) }}</span>
              <span>{{ igvLabel }}: S/ {{ orderTax.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Resumen de pago -->
          <div class="text-right">
            <div class="flex items-baseline gap-2 mb-1">
              <span class="text-sm text-gray-600">Pagado:</span>
              <span class="text-2xl font-semibold text-blue-600">
                S/ {{ paidTotal.toFixed(2) }}
              </span>
            </div>
            <div v-if="remaining > 0" class="flex items-baseline gap-2">
              <span class="text-sm text-gray-600">Falta:</span>
              <span class="text-xl font-semibold text-red-600">
                S/ {{ remaining.toFixed(2) }}
              </span>
            </div>
            <div v-if="change > 0" class="flex items-baseline gap-2">
              <span class="text-sm text-gray-600">Vuelto:</span>
              <span class="text-xl font-semibold text-green-600">
                S/ {{ change.toFixed(2) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenido principal -->
    <div class="flex-1 overflow-auto">
      <div class="max-w-6xl mx-auto p-6 space-y-6">
        <!-- Sección de documento y cliente (compacta) -->
        <div class="bg-white rounded-lg shadow-sm border p-4">
          <div class="grid grid-cols-2 gap-6">
            <!-- Tipo de documento -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de documento
              </label>
              <div class="flex gap-2">
                <button
                  :class="[
                    'flex-1 px-4 py-3 rounded-lg border-2 font-medium transition-all',
                    docType === 'boleta'
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400',
                  ]"
                  @click="selectDoc('boleta')"
                >
                  <div class="font-semibold">Boleta</div>
                  <div class="text-xs opacity-80 mt-1">
                    {{ receiptJournalCode || 'No config.' }}
                  </div>
                </button>
                <button
                  :class="[
                    'flex-1 px-4 py-3 rounded-lg border-2 font-medium transition-all',
                    !canInvoice ? 'opacity-50 cursor-not-allowed' : '',
                    docType === 'factura'
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400',
                  ]"
                  :disabled="!canInvoice"
                  @click="selectDoc('factura')"
                >
                  <div class="font-semibold">Factura</div>
                  <div class="text-xs opacity-80 mt-1">
                    {{ invoiceJournalCode || 'No config.' }}
                  </div>
                </button>
              </div>
            </div>

            <!-- Cliente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Cliente
              </label>
              <div class="flex items-center gap-2">
                <button
                  class="flex-1 px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border text-left cursor-pointer"
                  @click="openCustomerModal"
                  aria-label="Seleccionar cliente"
                >
                  <div class="font-medium text-gray-900 truncate">
                    {{
                      customer?.name ||
                      sessionStore.defaultCustomer?.name ||
                      'Sin cliente'
                    }}
                  </div>
                  <div
                    v-if="customer?.document_number"
                    class="text-xs text-gray-500 mt-1"
                  >
                    {{ customer.document_number }}
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Métodos de pago (enfoque principal) -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Método de pago
          </h2>

          <div
            v-if="paymentMethods.length === 0"
            class="text-center py-8 text-gray-500"
          >
            No hay métodos de pago disponibles.
            <div class="text-sm mt-2">
              Verifica tu autenticación y configuración.
            </div>
          </div>

          <!-- Layout: lista a la izquierda y panel a la derecha -->
          <div v-else class="grid grid-cols-12 gap-6">
            <!-- Lista de métodos (izquierda, más angosta) -->
            <div class="col-span-4">
              <div class="space-y-2">
                <button
                  v-for="(method, index) in paymentMethods"
                  :key="method.id"
                  :class="[
                    'w-full p-3 rounded-lg border-2 text-left transition-all flex items-center justify-between',
                    activePaymentMethod?.id === method.id
                      ? 'border-blue-600 bg-blue-50 shadow-md'
                      : 'border-gray-300 bg-white hover:border-gray-400',
                  ]"
                  @click="setActivePaymentMethod(method)"
                >
                  <div>
                    <div class="font-semibold text-gray-900">
                      {{ method.name }}
                    </div>
                    <div
                      v-if="paymentAmounts[method.id] > 0"
                      class="text-sm font-medium text-blue-600"
                    >
                      S/ {{ Number(paymentAmounts[method.id]).toFixed(2) }}
                    </div>
                    <div v-else class="text-xs text-gray-500">
                      Presiona para usar
                    </div>
                  </div>
                  <span class="text-xs text-gray-500">F{{ index + 1 }}</span>
                </button>
              </div>
            </div>

            <!-- Panel de entrada del método activo (derecha, más ancho) -->
            <div class="col-span-8">
              <div
                v-if="activePaymentMethod"
                class="bg-gray-50 rounded-lg p-6 border-2 border-blue-200"
              >
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Monto recibido - {{ activePaymentMethod.name }}
                  </label>
                  <div class="flex gap-2">
                    <div class="flex-1">
                      <input
                        :id="`payment-input-${activePaymentMethod.id}`"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        class="w-full text-3xl font-bold px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none"
                        v-model.number="paymentAmounts[activePaymentMethod.id]"
                        @blur="roundMethodAmount(activePaymentMethod.id)"
                        placeholder="0.00"
                      />
                    </div>
                    <button
                      class="px-6 py-3 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-700 border border-blue-300 font-medium transition-colors"
                      @click="fillMethodExact(activePaymentMethod.id)"
                    >
                      Exacto
                    </button>
                  </div>
                </div>

                <!-- Montos rápidos (solo efectivo) -->
                <div
                  v-if="
                    activePaymentMethod.name
                      .toLowerCase()
                      .includes('efectivo') && quickAmounts.length > 0
                  "
                >
                  <div class="text-sm font-medium text-gray-700 mb-2">
                    Montos rápidos
                  </div>
                  <div class="grid grid-cols-4 gap-2">
                    <button
                      v-for="amount in quickAmounts"
                      :key="amount"
                      class="px-4 py-3 bg-white hover:bg-gray-100 rounded-lg border font-semibold transition-colors"
                      @click="setQuickAmount(amount)"
                    >
                      S/ {{ amount.toFixed(0) }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer con acciones -->
    <div class="bg-white border-t shadow-lg">
      <div class="max-w-6xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between gap-4">
          <button
            class="px-6 py-3 rounded-lg border-2 border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
            @click="goBack"
            :disabled="isProcessing"
          >
            ← Regresar (Esc)
          </button>

          <div class="flex items-center gap-4">
            <div
              v-if="isProcessing"
              class="text-sm text-gray-600 flex items-center gap-2"
            >
              <svg
                class="animate-spin h-5 w-5 text-blue-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Procesando...
            </div>
            <button
              :class="[
                'px-8 py-4 rounded-lg font-bold text-lg transition-all shadow-lg',
                paidTotal >= orderTotal && !isProcessing
                  ? 'bg-green-600 hover:bg-green-700 text-white'
                  : 'bg-gray-300 text-gray-500 cursor-not-allowed',
              ]"
              :disabled="paidTotal < orderTotal || isProcessing"
              @click="pay"
            >
              {{ isProcessing ? 'Procesando...' : 'Completar Pago (Enter)' }}
            </button>
          </div>
        </div>
      </div>
      <!-- Modal de selección de cliente compartido -->
      <CustomerSelectModal
        :show="showCustomerModal"
        @close="showCustomerModal = false"
        @select="handleCustomerSelected"
      />
    </div>
  </div>
</template>

<style scoped>
/* Animación suave para los inputs */
input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}
input[type='number'] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
