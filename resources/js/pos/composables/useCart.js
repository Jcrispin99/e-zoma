import { ref, computed } from "vue";
import { POS_CONFIG, ERROR_MESSAGES } from "../constants/index.js";

export function useCart() {
    const cartItems = ref([]);
    const error = ref(null);
    const isLoading = ref(false);
    const lastModifiedProductId = ref(null);

    // Computed properties
    const subtotal = computed(() => {
        return cartItems.value.reduce(
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

    const itemCount = computed(() => {
        return cartItems.value.reduce(
            (sum, item) => sum + (item.quantity || 0),
            0
        );
    });

    const isEmpty = computed(() => {
        return cartItems.value.length === 0;
    });

    // Validación de producto
    const validateProduct = (product) => {
        if (!product || typeof product !== "object") {
            throw new Error(ERROR_MESSAGES.INVALID_PRODUCT);
        }
        // Verificamos que el precio no sea nulo o indefinido, en lugar de verificar estrictamente el tipo.
        // Los precios de la API pueden venir como strings ("10.50") y `typeof` fallaría.
        if (!product.id || !product.name || product.price == null) {
            throw new Error(ERROR_MESSAGES.INVALID_PRODUCT);
        }
        return true;
    };

    // Validación de cantidad
    const validateQuantity = (quantity) => {
        const qty = parseInt(quantity);
        if (
            isNaN(qty) ||
            qty < POS_CONFIG.MIN_QUANTITY ||
            qty > POS_CONFIG.MAX_QUANTITY
        ) {
            throw new Error(ERROR_MESSAGES.INVALID_QUANTITY);
        }
        return qty;
    };

    // Agregar producto al carrito
    const addToCart = (product) => {
        try {
            error.value = null;
            isLoading.value = true;

            validateProduct(product);

            const existingItem = cartItems.value.find(
                (item) => item.id === product.id
            );

            if (existingItem) {
                const newQuantity = existingItem.quantity + 1;
                validateQuantity(newQuantity);
                existingItem.quantity = newQuantity;
            } else {
                cartItems.value.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price), // Aseguramos que el precio sea un número
                    quantity: 1,
                    details: product.details || product.description || "",
                });
            }

            // Marcar este producto como el último modificado para selección automática
            lastModifiedProductId.value = product.id;
        } catch (err) {
            error.value = err.message;
            console.error("Error adding to cart:", err);
        } finally {
            isLoading.value = false;
        }
    };

    // Actualizar cantidad
    const updateQuantity = (productId, newQuantity) => {
        try {
            error.value = null;
            const validQuantity = validateQuantity(newQuantity);

            const item = cartItems.value.find((item) => item.id === productId);
            if (!item) {
                throw new Error(ERROR_MESSAGES.PRODUCT_NOT_FOUND);
            }

            item.quantity = validQuantity;

            // Marcar este producto como el último modificado para mantener selección
            lastModifiedProductId.value = productId;
        } catch (err) {
            error.value = err.message;
            console.error("Error updating quantity:", err);
        }
    };

    // Remover producto
    const removeItem = (productId) => {
        try {
            error.value = null;
            const index = cartItems.value.findIndex(
                (item) => item.id === productId
            );

            if (index === -1) {
                throw new Error(ERROR_MESSAGES.PRODUCT_NOT_FOUND);
            }

            cartItems.value.splice(index, 1);
        } catch (err) {
            error.value = err.message;
            console.error("Error removing item:", err);
        }
    };

    // Limpiar carrito
    const clearCart = () => {
        cartItems.value = [];
        error.value = null;
    };

    // Limpiar error
    const clearError = () => {
        error.value = null;
    };

    return {
        // State
        cartItems,
        error,
        isLoading,
        lastModifiedProductId,

        // Computed
        subtotal,
        tax,
        total,
        itemCount,
        isEmpty,

        // Methods
        addToCart,
        updateQuantity,
        removeItem,
        clearCart,
        clearError,
    };
}
