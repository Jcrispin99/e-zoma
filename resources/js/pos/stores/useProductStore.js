import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getCache, setCache } from '../composables/useCache';
import { useSessionStore } from './useSessionStore';

export const useProductStore = defineStore('products', () => {
  const products = ref([]);
  const categories = ref([]);
  const isLoading = ref(false);
  const error = ref(null);
  const sessionStore = useSessionStore();

  function getXsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
  }

  async function fetchProducts(searchTerm = '', categoryId = null) {
    isLoading.value = true;
    error.value = null;

    // Si estamos online, intentamos obtener los productos del servidor.
    // Si no, vamos directamente a la caché.
    if (sessionStore.online) {
      try {
        const params = new URLSearchParams();
        if (searchTerm) {
          params.append('search', searchTerm);
        }
        if (categoryId) {
          params.append('category_id', categoryId);
        }

        // Asegurar cookie CSRF para peticiones stateful
        if (!getXsrfToken()) {
          await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
        }

        const token = getXsrfToken();

        const response = await fetch(`/api/product-pos?${params.toString()}`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
          },
        });

        if (!response.ok) {
          throw new Error('Error al obtener los productos.');
        }

        const fetchedProducts = await response.json();
        products.value = fetchedProducts;
        // Guardar en caché para uso offline
        setCache('pos:products', fetchedProducts);
      } catch (e) {
        error.value = e.message;
        console.error(e);
        sessionStore.setOnline(false);
        // Si falla la petición, usamos la caché como fallback
        loadProductsFromCache(searchTerm, categoryId);
      } finally {
        isLoading.value = false;
      }
    } else {
      // Modo offline: cargar directamente desde la caché
      loadProductsFromCache(searchTerm, categoryId);
      isLoading.value = false;
    }
  }

  function loadProductsFromCache(searchTerm = '', categoryId = null) {
    let cachedProducts = getCache('pos:products', []);
    if (categoryId) {
      cachedProducts = cachedProducts.filter(
        (p) => p.category_id === categoryId
      );
    }
    if (searchTerm) {
      const lowerCaseSearchTerm = searchTerm.toLowerCase();
      cachedProducts = cachedProducts.filter(
        (p) =>
          p.name.toLowerCase().includes(lowerCaseSearchTerm) ||
          p.sku.toLowerCase().includes(lowerCaseSearchTerm)
      );
    }
    products.value = cachedProducts;
  }

  async function fetchCategories() {
    try {
      const response = await fetch('/api/categories');
      const data = await response.json();
      categories.value = [{ id: null, name: 'Todos' }, ...data];
      setCache('pos:categories', categories.value);
    } catch (e) {
      console.error(e);
      categories.value = getCache('pos:categories', [
        { id: null, name: 'Todos' },
      ]);
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
