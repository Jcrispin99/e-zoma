<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import ProductList from '../components/ProductList.vue';
import CartSidebar from '../components/CartSidebar.vue';
import AmountModal from '../components/modals/AmountModal.vue';
import { useCart } from '../composables/useCart.js';
import { useSessionStore } from '../stores/useSessionStore.js';
import { setCache } from '../composables/useCache.js';

const {
  cartItems,
  error: cartError,
  isLoading: cartLoading,
  lastModifiedProductId,
  addToCart,
  updateQuantity,
  removeItem,
  clearError,
  clearCart,
} = useCart();

const sessionStore = useSessionStore();
const backendOrigin =
  document.querySelector('meta[name="backend-origin"]')?.content ||
  window.location.origin;

const handleOnline = () => {
  sessionStore.setOnline(true);
  sessionStore.syncPending();
};
const handleOffline = () => {
  sessionStore.setOnline(false);
};
onMounted(async () => {
  sessionStore.setupSyncListeners();
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  window.addEventListener('keydown', handleGlobalKeydown);

  sessionStore.initFromUrl();
  try {
    await fetch(new URL('/sanctum/csrf-cookie', backendOrigin), {
      credentials: 'include',
    });
  } catch (e) {
    console.warn('No se pudo obtener CSRF cookie al inicio:', e);
  }
  sessionStore.bootstrap().then(() => {
    if (
      sessionStore.session &&
      sessionStore.session.status === 'open' &&
      Number(sessionStore.session.opening_balance || 0) === 0
    ) {
      showOpeningModal.value = true;
    }
    if (sessionStore.online) {
      sessionStore.syncPending();
    }
  });
});

const connectionStatus = computed(() =>
  sessionStore.online ? 'Conectado' : 'Desconectado'
);

const route = useRoute();
const isCheckout = computed(() => route.name === 'pos-checkout');
const isReceipt = computed(() => route.name === 'pos-receipt');

onUnmounted(() => {
  clearError();
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
  window.removeEventListener('keydown', handleGlobalKeydown);
});

function handleClearCart() {
  clearCart();
  setCache('pos:checkout', { items: [], subtotal: 0, tax: 0, total: 0 });
}

const showOpeningModal = ref(false);
const openingBalanceInput = ref('0');
const openingError = ref(null);

async function confirmOpeningBalance() {
  openingError.value = null;
  const value = Number(openingBalanceInput.value);
  if (isNaN(value) || value < 0) {
    openingError.value = 'Monto inválido';
    return;
  }
  try {
    await sessionStore.setOpeningBalance(value);
    showOpeningModal.value = false;
  } catch (e) {
    openingError.value = 'No se pudo guardar el monto';
  }
}

const showClosingModal = ref(false);
const closingBalanceInput = ref('0');
const closingError = ref(null);

async function confirmClosingBalance() {
  closingError.value = null;
  const value = Number(closingBalanceInput.value);
  if (isNaN(value) || value < 0) {
    closingError.value = 'Monto inválido';
    return;
  }
  try {
    await sessionStore.closeSession(value);
    showClosingModal.value = false;
    window.location.href = '/admin/posconfig';
  } catch (e) {
    closingError.value = 'No se pudo cerrar la sesión';
  }
}

const scannerBuffer = ref('');
let scannerTimer = null;
function isEditableActive() {
  const el = document.activeElement;
  return (
    !!el &&
    (el.tagName === 'INPUT' ||
      el.tagName === 'TEXTAREA' ||
      el.isContentEditable === true)
  );
}
async function finalizeScan() {
  const code = String(scannerBuffer.value || '').trim();
  scannerBuffer.value = '';
  if (!code) return;
  try {
    if (sessionStore.online) {
      const params = new URLSearchParams();
      params.append('search', code);
      const token = sessionStore.getXsrfToken();
      const res = await fetch(`/api/product-pos?${params.toString()}`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
      });
      if (!res.ok) return;
      const variants = await res.json();
      if (Array.isArray(variants) && variants.length > 0) {
        const exact = variants.find((v) => String(v.sku || '').trim() === code);
        addToCart(exact || variants[0]);
      }
    } else {
      try {
        const raw = localStorage.getItem('pos:products');
        const cached = raw ? JSON.parse(raw) : [];
        const found = cached.find((p) => String(p.sku || '').trim() === code);
        if (found) addToCart(found);
      } catch (_) {}
    }
  } catch (_) {}
}
function handleGlobalKeydown(e) {
  if (isReceipt.value || isCheckout.value) return;
  if (isEditableActive()) return;
  if (e.key === 'Enter' || e.key === 'Tab') {
    e.preventDefault();
    if (scannerTimer) {
      clearTimeout(scannerTimer);
      scannerTimer = null;
    }
    finalizeScan();
    return;
  }
  if (e.key && e.key.length === 1) {
    scannerBuffer.value += e.key;
    if (scannerTimer) clearTimeout(scannerTimer);
    scannerTimer = setTimeout(() => {
      scannerTimer = null;
      finalizeScan();
    }, 80);
  }
}
</script>

<template>
  <div class="h-screen flex flex-col bg-gray-50">
    <header
      v-if="!isReceipt"
      class="bg-white border-b border-gray-200 px-6 py-4"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-3">
            <div
              class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center"
            >
              <svg
                class="w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"
                />
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900">Sistema POS</h1>
              <p class="text-sm text-gray-500">Punto de Venta - E-Zoma</p>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <div
              class="w-2 h-2 rounded-full"
              :class="sessionStore.online ? 'bg-green-400' : 'bg-red-400'"
              aria-label="Estado de conexión"
            ></div>
            <span class="text-sm text-gray-600">{{ connectionStatus }}</span>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <button
            class="px-3 py-2 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
            @click="showClosingModal = true"
          >
            Cerrar sesión
          </button>
          <div class="flex items-center space-x-3">
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">
                {{ sessionStore.seller?.name || '—' }}
              </p>
              <p class="text-xs text-gray-500">
                {{ sessionStore.seller?.email || sessionStore.pos?.name || '' }}
              </p>
            </div>
            <div
              class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center"
            >
              <svg
                class="w-5 h-5 text-purple-600"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>

          <div class="w-px h-6 bg-gray-300"></div>

          <div class="flex items-center space-x-2">
            <button
              class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1 1 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>

    <div v-if="isReceipt" class="flex-1 overflow-auto">
      <router-view />
    </div>
    <div v-else class="flex flex-1 overflow-hidden">
      <div
        v-if="!isCheckout"
        class="w-[488px] border-l border-gray-200 bg-white flex-shrink-0"
      >
        <CartSidebar
          :cart-items="cartItems"
          :error="cartError"
          :loading="cartLoading"
          :last-modified-product-id="lastModifiedProductId"
          @update-quantity="updateQuantity"
          @remove-item="removeItem"
          @clear-error="clearError"
        />
      </div>

      <div class="flex-1 overflow-hidden">
        <router-view @add-to-cart="addToCart" @clear-cart="handleClearCart" />
      </div>
    </div>
  </div>

  <AmountModal
    :show="showOpeningModal"
    :value="openingBalanceInput"
    :error="openingError"
    title="Monto de apertura"
    description="Ingresa el efectivo inicial en caja."
    confirm-button-class="bg-purple-600 text-white hover:bg-purple-700"
    @update:value="(v) => (openingBalanceInput = v)"
    @close="showOpeningModal = false"
    @confirm="confirmOpeningBalance"
  />
  <AmountModal
    :show="showClosingModal"
    :value="closingBalanceInput"
    :error="closingError"
    title="Monto de cierre"
    description="Ingresa el efectivo al cierre de caja."
    confirm-button-class="bg-red-600 text-white hover:bg-red-700"
    @update:value="(v) => (closingBalanceInput = v)"
    @close="showClosingModal = false"
    @confirm="confirmClosingBalance"
  />
</template>
