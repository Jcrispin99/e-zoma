<script setup>
import { computed, ref, shallowRef, onUnmounted, watch } from "vue";
import { useKeypad } from "../composables/useKeypad.js";
import { formatCurrency } from "../utils/currency.js";
import { POS_CONFIG } from "../constants/index.js";

// Props del carrito
const props = defineProps({
    cartItems: {
        type: Array,
        default: () => [],
        validator: (items) => Array.isArray(items),
    },
    error: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    lastModifiedProductId: {
        type: [String, Number],
        default: null,
    },
});

// Emits para comunicar cambios al componente padre
const emit = defineEmits(["update-quantity", "remove-item", "clear-error"]);

// Estado del producto seleccionado (usando shallowRef para mejor performance)
const selectedProductIndex = shallowRef(0);

// Usar el composable del teclado
const {
    currentNumber,
    displayNumber,
    hasValue,
    numericValue,
    addNumber,
    clear: clearKeypad,
    cleanup: cleanupKeypad,
} = useKeypad();

// Producto seleccionado actualmente
const selectedProduct = computed(() => {
    if (
        props.cartItems.length === 0 ||
        selectedProductIndex.value >= props.cartItems.length
    ) {
        return null;
    }
    return props.cartItems[selectedProductIndex.value];
});

// Cálculos optimizados con dependencias específicas
const subtotal = computed(() => {
    return props.cartItems.reduce(
        (sum, item) => sum + (item.price || 0) * (item.quantity || 0),
        0
    );
});

const tax = computed(() => {
    return subtotal.value * POS_CONFIG.TAX_RATE;
});

const total = computed(() => {
    return subtotal.value + tax.value;
});

// Función para seleccionar un producto
const selectProduct = (index) => {
    if (index >= 0 && index < props.cartItems.length) {
        selectedProductIndex.value = index;
        clearKeypad(); // Limpiar el teclado al cambiar de producto
    }
};

// Función para manejar clicks en el teclado numérico con validación
const handleNumberClick = (number) => {
    if (!selectedProduct.value) {
        console.warn("No product selected");
        return;
    }

    addNumber(number, (quantity) => {
        if (quantity > 0) {
            emit("update-quantity", selectedProduct.value.id, quantity);
        }
    });
};

// Función para manejar el botón C (reset/eliminar) con mejor lógica
const handleClearAction = () => {
    if (!selectedProduct.value) {
        console.warn("No product selected for clear action");
        return;
    }

    try {
        if (selectedProduct.value.quantity > 0) {
            // Primer click: resetear cantidad a 0
            emit("update-quantity", selectedProduct.value.id, 0);
        } else {
            // Segundo click o si ya está en 0: eliminar del carrito
            emit("remove-item", selectedProduct.value.id);
        }
        clearKeypad();
    } catch (error) {
        console.error("Error in clear action:", error);
    }
};

// Limpiar error cuando se hace click en cualquier parte
const handleClearError = () => {
    if (props.error) {
        emit("clear-error");
    }
};

// Resetear selección cuando el carrito cambia
watch(
    () => props.cartItems.length,
    (newLength) => {
        if (selectedProductIndex.value >= newLength) {
            selectedProductIndex.value = Math.max(0, newLength - 1);
        }
    }
);

// Seleccionar automáticamente el producto que fue modificado/agregado
watch(
    () => props.lastModifiedProductId,
    (newProductId) => {
        if (newProductId && props.cartItems.length > 0) {
            const productIndex = props.cartItems.findIndex(
                (item) => item.id === newProductId
            );
            if (productIndex !== -1) {
                selectedProductIndex.value = productIndex;
                clearKeypad(); // Limpiar el teclado para el nuevo producto seleccionado
            }
        }
    }
);

// Cleanup al desmontar
onUnmounted(() => {
    cleanupKeypad();
});
</script>

<template>
    <div class="h-full flex flex-col bg-gray-100">
        <!-- Lista de productos del carrito -->
        <div class="flex-1 bg-white overflow-y-auto" @click="handleClearError">
            <!-- Error display -->
            <div
                v-if="error"
                class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg mx-4 mt-4"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-red-600">{{ error }}</p>
                    <button
                        @click.stop="handleClearError"
                        class="text-red-400 hover:text-red-600"
                        aria-label="Cerrar error"
                    >
                        ×
                    </button>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="text-center text-gray-500 py-4">
                <div
                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"
                ></div>
                Procesando...
            </div>

            <!-- Empty cart -->
            <div
                v-else-if="cartItems.length === 0"
                class="flex flex-col justify-center items-center h-32"
            >
                <div class="text-4xl mb-2">🛒</div>
                <p class="text-gray-500 text-sm">Carrito vacío</p>
                <p class="text-gray-400 text-xs mt-1">
                    Agrega productos para comenzar
                </p>
            </div>

            <!-- Cart items -->
            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="(item, index) in cartItems"
                    :key="item.id"
                    @click="selectProduct(index)"
                    @keydown.enter="selectProduct(index)"
                    @keydown.space.prevent="selectProduct(index)"
                    :class="[
                        'p-4 cursor-pointer transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500',
                        selectedProductIndex === index
                            ? 'bg-blue-50 border-l-4 border-blue-500'
                            : 'hover:bg-gray-50',
                    ]"
                    tabindex="0"
                    :aria-label="`Producto ${item.name}, cantidad ${
                        item.quantity
                    }, precio ${formatCurrency(
                        (item.price || 0) * (item.quantity || 0)
                    )}`"
                    role="button"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <!-- Nombre del producto - Detalles -->
                            <h3
                                class="font-semibold text-gray-900 text-sm mb-1"
                            >
                                {{ item.name }} -
                                {{ item.details || "Sin detalles" }}
                            </h3>
                            <!-- Cantidad y precio unitario -->
                            <p class="text-xs text-gray-600">
                                {{ item.quantity || 0 }}.00 Unidades x S/
                                {{ (item.price || 0).toFixed(2) }} / Unidades
                            </p>
                        </div>
                        <!-- Precio total -->
                        <div class="text-right ml-4">
                            <p class="font-semibold text-gray-900">
                                S/
                                {{
                                    (
                                        (item.price || 0) * (item.quantity || 0)
                                    ).toFixed(2)
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Section -->
        <div class="bg-white p-4 border-t border-gray-300">
            <div class="flex justify-end">
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900">
                        Total: {{ formatCurrency(total) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Keypad Section -->
        <div class="bg-white border-t border-gray-300">
            <!-- Display del número actual -->
            <div
                v-if="currentNumber"
                class="bg-gray-100 p-2 text-center border-b border-gray-300"
                role="textbox"
                aria-readonly="true"
                aria-label="Número actual ingresado"
            >
                <span class="text-lg font-mono">{{
                    displayNumber || "0"
                }}</span>
                <span v-if="selectedProduct" class="text-sm text-gray-600 ml-2">
                    ({{ selectedProduct.name }})
                </span>
            </div>

            <div class="flex">
                <!-- Left Side Buttons -->
                <div class="w-40 bg-purple-800 flex flex-col">
                    <!-- Selecionar cliente -->
                    <button
                        class="h-16 w-full flex items-center justify-center text-white bg-gray-800 border-b border-gray-600"
                    >
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-6 h-6 bg-white rounded-full flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-gray-800"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">VARIOS</span>
                        </div>
                    </button>

                    <!-- Pago Button -->
                    <button
                        class="flex-1 flex items-center justify-center text-white bg-purple-800"
                    >
                        <div class="flex flex-col items-center space-y-1">
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                            <span class="text-sm font-medium text-black"
                                >Pago</span
                            >
                        </div>
                    </button>
                </div>

                <!-- Numeric Keypad -->
                <div class="flex-1">
                    <div
                        class="grid grid-cols-4 h-full"
                        role="grid"
                        aria-label="Teclado numérico"
                    >
                        <!-- Row 1: 1, 2, 3, Cant. -->
                        <button
                            @click="handleNumberClick(1)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 1"
                            type="button"
                        >
                            1
                        </button>
                        <button
                            @click="handleNumberClick(2)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 2"
                            type="button"
                        >
                            2
                        </button>
                        <button
                            @click="handleNumberClick(3)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 3"
                            type="button"
                        >
                            3
                        </button>
                        <button
                            class="h-16 border border-gray-300 bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            aria-label="Porcentaje de descuento (no disponible)"
                            type="button"
                        >
                            % de<br />desc.
                        </button>

                        <!-- Row 2: 4, 5, 6, % de desc. -->
                        <button
                            @click="handleNumberClick(4)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 4"
                            type="button"
                        >
                            4
                        </button>
                        <button
                            @click="handleNumberClick(5)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 5"
                            type="button"
                        >
                            5
                        </button>
                        <button
                            @click="handleNumberClick(6)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 6"
                            type="button"
                        >
                            6
                        </button>
                        <button
                            class="h-16 border border-gray-300 bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            aria-label="Porcentaje de descuento (no disponible)"
                            type="button"
                        >
                            % de<br />desc.
                        </button>

                        <!-- Row 3: 7, 8, 9, Precio -->
                        <button
                            @click="handleNumberClick(7)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 7"
                            type="button"
                        >
                            7
                        </button>
                        <button
                            @click="handleNumberClick(8)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 8"
                            type="button"
                        >
                            8
                        </button>
                        <button
                            @click="handleNumberClick(9)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 9"
                            type="button"
                        >
                            9
                        </button>
                        <button
                            class="h-16 border border-gray-300 bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            aria-label="Precio (no disponible)"
                            type="button"
                        >
                            Precio
                        </button>

                        <!-- Row 4: +/-, 0, ., Clear -->
                        <button
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            aria-label="Cambiar signo (no disponible)"
                            type="button"
                        >
                            +/-
                        </button>
                        <button
                            @click="handleNumberClick(0)"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Número 0"
                            type="button"
                        >
                            0
                        </button>
                        <button
                            class="h-16 border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            aria-label="Punto decimal (no disponible)"
                            type="button"
                        >
                            .
                        </button>
                        <button
                            @click="handleClearAction"
                            :disabled="loading || !selectedProduct"
                            class="h-16 border border-gray-300 bg-red-100 hover:bg-red-200 flex items-center justify-center font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Limpiar o eliminar producto"
                            type="button"
                        >
                            C
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
