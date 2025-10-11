<script setup>
import { ref, onMounted, watch } from "vue";
import { useProductStore } from "../stores/useProductStore";
import { storeToRefs } from "pinia";
import { formatCurrency } from "../utils/currency";

const emit = defineEmits(["add-to-cart"]);
const productStore = useProductStore();
const { products, isLoading, error, categories } = storeToRefs(productStore);
const { fetchProducts, fetchCategories } = productStore;

const searchTerm = ref("");
const searchInput = ref(null);
const activeCategory = ref(null);

watch(searchTerm, (newTerm) => {
    // Usamos un debounce manual con setTimeout
    setTimeout(() => {
        fetchProducts(newTerm, activeCategory.value);
    }, 300); // Espera 300ms después de que el usuario deja de escribir
});

const addProductToCart = (product) => {
    emit("add-to-cart", product);
};

const filterByCategory = (categoryId) => {
    activeCategory.value = categoryId;
    fetchProducts(searchTerm.value, categoryId);
};

onMounted(() => {
    fetchProducts();
    fetchCategories();

    window.addEventListener("keydown", (event) => {
        if (event.key === "F2") {
            event.preventDefault();
            searchInput.value.focus();
        }
    });
});
</script>

<template>
    <div class="h-full flex flex-col bg-gray-50">
        <!-- Search and Categories Bar -->
        <div
            class="bg-white border-b border-gray-200 flex items-center px-4 py-2"
        >
            <!-- Category Tabs (Scrollable) -->
            <div class="flex-1 min-w-0 pl-4">
                <div class="flex space-x-1 overflow-x-auto">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="filterByCategory(category.id)"
                        :class="[
                            'px-4 py-3 text-sm font-medium rounded-lg whitespace-nowrap transition-all duration-200',
                            activeCategory === category.id
                                ? 'bg-purple-600 text-white shadow-sm'
                                : 'text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="w-72 flex-shrink-0">
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <input
                        type="text"
                        v-model="searchTerm"
                        ref="searchInput"
                        placeholder="Buscar productos (F2)..."
                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm transition-colors"
                    />
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4">
                <!-- Loading state -->
                <div v-if="isLoading" class="text-center py-10">
                    <p class="text-gray-500">Cargando productos...</p>
                </div>

                <!-- Error state -->
                <div v-else-if="error" class="text-center py-10">
                    <p class="text-red-500">{{ error }}</p>
                </div>

                <!-- Products Grid -->
                <div
                    v-else-if="products.length > 0"
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3"
                >
                    <button
                        v-for="variant in products"
                        :key="variant.id"
                        @click="addProductToCart(variant)"
                        class="relative group bg-white rounded-lg shadow-sm overflow-hidden transition-all duration-200 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="p-3 text-left">
                            <h3
                                class="text-sm font-semibold text-gray-800 truncate group-hover:text-purple-600"
                            >
                                {{ variant.name }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ variant.sku }}
                            </p>
                            <p class="text-base font-bold text-purple-700 mt-2">
                                {{ formatCurrency(variant.price) }}
                            </p>
                        </div>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-10">
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            vector-effect="non-scaling-stroke"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">
                        No se encontraron productos
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Intenta con otra búsqueda o agrega nuevos productos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Ocultar scrollbar en las categorías */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
