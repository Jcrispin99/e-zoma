import { defineStore } from "pinia";
import { ref } from "vue";

export const useProductStore = defineStore("products", () => {
    const products = ref([]);
    const categories = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    async function fetchProducts(searchTerm = "", categoryId = null) {
        isLoading.value = true;
        error.value = null;
        products.value = [];

        try {
            const params = new URLSearchParams();
            if (searchTerm) {
                params.append("search", searchTerm);
            }
            if (categoryId) {
                params.append("category_id", categoryId);
            }

            const response = await fetch(
                `/api/product-pos?${params.toString()}`,
                {
                    method: "POST",
                }
            );

            if (!response.ok) {
                throw new Error("Error al obtener los productos.");
            }

            products.value = await response.json();
        } catch (e) {
            error.value = e.message;
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchCategories() {
        try {
            const response = await fetch("/api/categories");
            if (!response.ok) {
                throw new Error("Error al obtener las categorías.");
            }
            const data = await response.json();
            categories.value = [{ id: null, name: "Todos" }, ...data];
        } catch (e) {
            console.error(e);
        }
    }

    return {
        products,
        categories,
        isLoading,
        error,
        fetchProducts,
        fetchCategories,
    };
});
