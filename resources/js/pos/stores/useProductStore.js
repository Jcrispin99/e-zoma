import { defineStore } from "pinia";
import { ref } from "vue";

export const useProductStore = defineStore("products", () => {
    const products = ref([]);
    const categories = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    function getXsrfToken() {
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    }

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

            // Asegurar cookie CSRF para peticiones stateful
            if (!getXsrfToken()) {
                await fetch(`/sanctum/csrf-cookie`, { credentials: "include" });
            }

            const token = getXsrfToken();

            const response = await fetch(
                `/api/product-pos?${params.toString()}`,
                {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                        ...(token ? { "X-XSRF-TOKEN": token } : {}),
                    },
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
