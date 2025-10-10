<script setup>
import { ref } from "vue";

const emit = defineEmits(["add-to-cart"]);

const addProductToCart = (product) => {
    emit("add-to-cart", product);
};

// Categorías
const categories = ref([
    { id: "all", name: "Todos", active: true },
    { id: "bebidas", name: "Bebidas", active: false },
    { id: "snacks", name: "Snacks", active: false },
    { id: "dulces", name: "Dulces", active: false },
    { id: "lacteos", name: "Lácteos", active: false },
    { id: "panaderia", name: "Panadería", active: false },
    { id: "ropa", name: "Ropa", active: false },
]);

// Productos de ejemplo
const mockProducts = ref([
    {
        id: 1,
        name: "CASACA NANI - CROSS (M)",
        price: 69.0,
        stock: 15,
        category: "ropa",
        imageUrl: null,
    },
    {
        id: 2,
        name: "CASACA NANI - CROSS (S)",
        price: 69.0,
        stock: 8,
        category: "ropa",
        imageUrl: null,
    },
    {
        id: 3,
        name: "Coca-Cola 600ml",
        price: 12.5,
        stock: 25,
        category: "bebidas",
        imageUrl: null,
    },
    {
        id: 4,
        name: "Sabritas Originales 45g",
        price: 15.0,
        stock: 30,
        category: "snacks",
        imageUrl: null,
    },
    {
        id: 5,
        name: "Gansito Marinela",
        price: 18.0,
        stock: 20,
        category: "dulces",
        imageUrl: null,
    },
    {
        id: 6,
        name: "Agua Ciel 1L",
        price: 10.0,
        stock: 40,
        category: "bebidas",
        imageUrl: null,
    },
    {
        id: 7,
        name: "Chicles Clorets",
        price: 8.75,
        stock: 50,
        category: "dulces",
        imageUrl: null,
    },
    {
        id: 8,
        name: "Paleta Magnum",
        price: 35.0,
        stock: 3,
        category: "dulces",
        imageUrl: null,
    },
    {
        id: 9,
        name: "Café Americano",
        price: 25.0,
        stock: 18,
        category: "bebidas",
        imageUrl: null,
    },
    {
        id: 10,
        name: "Red Bull Energy",
        price: 45.0,
        stock: 10,
        category: "bebidas",
        imageUrl: null,
    },
    {
        id: 11,
        name: "Pan Integral Bimbo",
        price: 32.0,
        stock: 15,
        category: "panaderia",
        imageUrl: null,
    },
    {
        id: 12,
        name: "Leche Lala 1L",
        price: 22.5,
        stock: 0,
        category: "lacteos",
        imageUrl: null,
    },
]);

// Función para formatear precios
const formatCurrency = (value) => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
    }).format(value);
};
</script>

<template>
    <div class="h-full flex flex-col bg-gray-50">
        <!-- Search and Categories Bar -->
        <div
            class="bg-white border-b border-gray-200 flex items-center px-4 py-2"
        >
            <!-- Category Tabs -->
            <div class="flex-grow pl-4">
                <div class="flex space-x-1 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        :class="[
                            'px-4 py-3 text-sm font-medium rounded-lg whitespace-nowrap transition-all duration-200',
                            category.active
                                ? 'bg-purple-600 text-white shadow-sm'
                                : 'text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="w-72">
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
                        placeholder="Buscar productos (F2)..."
                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm transition-colors"
                    />
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4">
                <!-- Products Grid -->
                <div
                    v-if="mockProducts.length > 0"
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3"
                >
                    <button
                        v-for="product in mockProducts"
                        :key="product.id"
                        @click="addProductToCart(product)"
                        class="bg-white rounded-lg p-3 border border-gray-200 hover:border-purple-400 hover:shadow-md transition-all duration-200 text-left group focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                    >
                        <!-- Image -->
                        <div
                            class="aspect-square bg-gray-50 rounded-md mb-2 flex items-center justify-center overflow-hidden"
                        >
                            <img
                                v-if="product.imageUrl"
                                :src="product.imageUrl"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="text-gray-300">
                                <svg
                                    class="w-12 h-12"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"
                                    />
                                    <path
                                        d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="space-y-1">
                            <h3
                                class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-purple-600 transition-colors min-h-[2.5rem]"
                            >
                                {{ product.name }}
                            </h3>
                            <div class="flex items-center justify-between">
                                <p class="text-lg font-bold text-purple-600">
                                    {{ formatCurrency(product.price) }}
                                </p>
                                <span
                                    v-if="product.stock !== undefined"
                                    :class="[
                                        'text-xs px-2 py-0.5 rounded-full font-medium',
                                        product.stock > 10
                                            ? 'bg-green-100 text-green-700'
                                            : product.stock > 0
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    {{ product.stock }}
                                </span>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center h-96 text-gray-400"
                >
                    <div
                        class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4"
                    >
                        <svg
                            class="w-12 h-12"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 mb-1">
                        No hay productos disponibles
                    </p>
                    <p class="text-sm text-gray-500">
                        Agrega productos al inventario para comenzar
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
