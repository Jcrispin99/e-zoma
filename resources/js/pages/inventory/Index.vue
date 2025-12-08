<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { Package, Layers, Home, Activity, Tag, Box } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DashboardStats, CategoryDistribution, WarehouseStockDistribution } from '@/types/inventory';
import type { Product } from '@/types/product';
import StatCard from '@/components/ui/dashboard/StatCard.vue';
import ChartCard from '@/components/ui/dashboard/ChartCard.vue';

const props = defineProps<{
  stats: DashboardStats;
  categoryDistribution: CategoryDistribution[];
  warehouseStock: WarehouseStockDistribution[];
  recentProducts: Product[];
}>();

const navigationItems = inventoryNavigation;

const entitySeries = computed(() => [{
  name: 'Total',
  data: [
    props.stats?.products_count || 0,
    props.stats?.variants_count || 0,
    props.stats?.categories_count || 0,
    props.stats?.attributes_count || 0,
    props.stats?.warehouses_count || 0
  ]
}]);
const entityCategories = ['Productos', 'Variantes', 'Categorías', 'Atributos', 'Almacenes'];
const entityColors = ['#0d9488'];

const categorySeries = computed(() => props.categoryDistribution.map(c => c.value));
const categoryLabels = computed(() => props.categoryDistribution.map(c => c.name));
const categoryColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'];

const warehouseSeries = computed(() => [{
  name: 'Stock',
  data: props.warehouseStock.map(w => Number(w.total_stock))
}]);
const warehouseCategories = computed(() => props.warehouseStock.map(w => w.name));
const warehouseColors = ['#f97316'];

const getProductStock = (product: Product) => {
  return product.variants?.reduce((sum, v) => sum + Number(v.stock), 0) || 0;
};
</script>

<template>
  <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
    <div class="space-y-6 mx-9">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Información General</h1>
          <p class="text-gray-500 mt-1">Resumen general y métricas del inventario</p>
        </div>
      </div>

      <div v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <StatCard title="Productos" :value="stats.products_count" :icon="Package" icon-class="text-teal-600" />
        <StatCard title="Variantes" :value="stats.variants_count" :icon="Layers" icon-class="text-indigo-600" />
        <StatCard title="Categorías" :value="stats.categories_count" :icon="Box" icon-class="text-blue-600" />
        <StatCard title="Atributos" :value="stats.attributes_count" :icon="Tag" icon-class="text-purple-600" />
        <StatCard title="Almacenes" :value="stats.warehouses_count" :icon="Home" icon-class="text-orange-600" />
        <StatCard title="Stock Total" :value="stats.total_stock" :icon="Activity" icon-class="text-green-600" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <ChartCard title="Composición del Sistema" type="bar" :series="entitySeries" :categories="entityCategories"
          :colors="entityColors" />
        <ChartCard title="Top Categorías (por productos)" type="donut" :series="categorySeries" :labels="categoryLabels"
          :colors="categoryColors" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <ChartCard title="Stock por Almacén" type="bar" :series="warehouseSeries" :categories="warehouseCategories"
          :colors="warehouseColors" :horizontal="true" />

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Últimos Productos</h3>
            <button @click="router.visit('/finanzas/inventario/productos')"
              class="text-sm text-teal-600 hover:text-teal-700 font-medium">Ver todos</button>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-500">
                <tr>
                  <th class="px-6 py-3 font-medium">Nombre</th>
                  <th class="px-6 py-3 font-medium text-right">Stock</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="product in recentProducts" :key="product.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-3 font-medium text-gray-900 truncate max-w-[200px]">{{ product.name }}</td>
                  <td class="px-6 py-3 text-gray-500 text-right">{{ getProductStock(product) }}</td>
                </tr>
                <tr v-if="!recentProducts || recentProducts.length === 0">
                  <td colspan="2" class="px-6 py-8 text-center text-gray-500">Sin datos recientes.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
