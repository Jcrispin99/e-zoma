<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import ProductList from '../components/ProductList.vue';
import CartSidebar from '../components/CartSidebar.vue';
import OpeningModal from '../components/modals/OpeningModal.vue';
import ClosingModal from '../components/modals/ClosingModal.vue';
import { useCart } from '../composables/useCart.js';
import { useSessionStore } from '../stores/useSessionStore.js';

// Usar el composable del carrito
const {
  cartItems,
  error: cartError,
  isLoading: cartLoading,
  lastModifiedProductId,
  addToCart,
  updateQuantity,
  removeItem,
  clearError,
} = useCart();

// Store de sesión
const sessionStore = useSessionStore();
onMounted(async () => {
  sessionStore.initFromUrl();
  // Prefetch de cookie CSRF para asegurar estado stateful
  try {
    await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
  } catch (e) {
    // No bloquear el flujo si falla; el bootstrap manejará el estado
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
  });
});

// Estado de conexión del sistema
const connectionStatus = computed(() =>
  sessionStore.online ? 'Conectado' : 'Desconectado'
);

// Ocultar el carrito en la ruta de checkout y toda la UI en recibo
const route = useRoute();
const isCheckout = computed(() => route.name === 'pos-checkout');
const isReceipt = computed(() => route.name === 'pos-receipt');

// Limpiar errores cuando el componente se desmonte
onUnmounted(() => {
  clearError();
});

// Modal de apertura
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
// Modal de cierre
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
</script>

<template>
  <div class="h-screen flex flex-col bg-gray-50">
    <!-- Modal Monto de Apertura -->
    <div
      v-if="showOpeningModal"
      class="fixed inset-0 z-50 flex items-center justify-center"
    >
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <h2 class="text-lg font-semibold mb-2">Monto de apertura</h2>
        <p class="text-sm text-gray-600 mb-4">
          Ingresa el efectivo inicial en caja.
        </p>
        <input
          v-model="openingBalanceInput"
          type="number"
          min="0"
          step="0.01"
          class="w-full border rounded px-3 py-2 mb-2"
        />
        <p v-if="openingError" class="text-sm text-red-600 mb-2">
          {{ openingError }}
        </p>
        <div class="flex justify-end space-x-2">
          <button
            class="px-3 py-2 rounded bg-gray-200 text-gray-700"
            @click="showOpeningModal = false"
          >
            Cancelar
          </button>
          <button
            class="px-3 py-2 rounded bg-purple-600 text-white hover:bg-purple-700"
            @click="confirmOpeningBalance"
          >
            Confirmar
          </button>
        </div>
      </div>
    </div>
    <!-- Header -->
    <header
      v-if="!isReceipt"
      class="bg-white border-b border-gray-200 px-6 py-4"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- Logo/Title -->
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

          <!-- Connection Status -->
          <div class="flex items-center space-x-2">
            <div
              class="w-2 h-2 rounded-full"
              :class="sessionStore.online ? 'bg-green-400' : 'bg-red-400'"
              aria-label="Estado de conexión"
            ></div>
            <span class="text-sm text-gray-600">{{ connectionStatus }}</span>
          </div>
        </div>

        <!-- User Profile and Settings -->
        <div class="flex items-center space-x-4">
          <!-- Cerrar sesión POS -->
          <button
            class="px-3 py-2 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
            @click="showClosingModal = true"
          >
            Cerrar sesión
          </button>
          <!-- User Profile -->
          <div class="flex items-center space-x-3">
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">Usuario Admin</p>
              <p class="text-xs text-gray-500">Administrador</p>
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
            <!-- Configuración -->
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
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
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

    <!-- Main Content -->
    <div v-if="isReceipt" class="flex-1 overflow-auto">
      <router-view />
    </div>
    <div v-else class="flex flex-1 overflow-hidden">
      <!-- Cart Sidebar - Derecha -->
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

      <!-- Products Section - Izquierda -->
      <div class="flex-1 overflow-hidden">
        <router-view @add-to-cart="addToCart" />
      </div>
    </div>
  </div>

  <!-- Modales -->
  <OpeningModal
    :show="showOpeningModal"
    :value="openingBalanceInput"
    :error="openingError"
    @update:value="(v) => (openingBalanceInput = v)"
    @close="showOpeningModal = false"
    @confirm="confirmOpeningBalance"
  />
  <ClosingModal
    :show="showClosingModal"
    :value="closingBalanceInput"
    :error="closingError"
    @update:value="(v) => (closingBalanceInput = v)"
    @close="showClosingModal = false"
    @confirm="confirmClosingBalance"
  />
</template>
