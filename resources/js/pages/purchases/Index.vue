<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { purchasesNavigation, purchasesIcon } from '@/config/purchasesNavigation';
import { ShoppingCart, FileText, Users, Package, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StatCard from '@/components/ui/dashboard/StatCard.vue';
import ChartCard from '@/components/ui/dashboard/ChartCard.vue';

const props = defineProps<{
  stats: {
    purchases_count: number;
    orders_count: number;
    suppliers_count: number;
    products_count: number;
    total_spent: number;
  };
  recentPurchases: Array<{
    id: number;
    supplier_name: string;
    total: number;
    date: string;
    status: string;
  }>;
  monthlySpend: Array<{ month: string; total: number }>;
  topSuppliers: Array<{ name: string; value: number }>;
}>();

const navigationItems = purchasesNavigation;

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value);
};

const spendSeries = computed(() => [{
  name: 'Gasto Mensual',
  data: props.monthlySpend.map(s => Number(s.total))
}]);
const spendCategories = computed(() => props.monthlySpend.map(s => s.month));
const spendColors = ['#0d9488'];

const supplierSeries = computed(() => props.topSuppliers.map(s => Number(s.value)));
const supplierLabels = computed(() => props.topSuppliers.map(s => s.name));
const supplierColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

</script>

<template>
  <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="navigationItems">
    <div class="space-y-6 mx-9">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Panel de Compras</h1>
          <p class="text-gray-500 mt-1">Resumen general de gastos y adquisiciones</p>
        </div>
      </div>

      <div v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <StatCard title="Compras Totales" :value="stats.purchases_count" :icon="ShoppingCart"
          icon-class="text-teal-600" />
        <StatCard title="Órdenes de Compra" :value="stats.orders_count" :icon="FileText" icon-class="text-indigo-600" />
        <StatCard title="Proveedores" :value="stats.suppliers_count" :icon="Users" icon-class="text-blue-600" />
        <StatCard title="Productos" :value="stats.products_count" :icon="Package" icon-class="text-purple-600" />
        <StatCard title="Gasto Total" :value="formatCurrency(stats.total_spent)" :icon="Wallet"
          icon-class="text-orange-600" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <ChartCard title="Tendencia de Gasto Mensual" type="bar" :series="spendSeries" :categories="spendCategories"
          :colors="spendColors" />
        <ChartCard title="Top Proveedores (por gasto)" type="donut" :series="supplierSeries" :labels="supplierLabels"
          :colors="supplierColors" />
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-800">Últimas Compras</h3>
          <button @click="router.visit('/finanzas/compras/facturas')"
            class="text-sm text-teal-600 hover:text-teal-700 font-medium">Ver todas</button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500">
              <tr>
                <th class="px-6 py-3 font-medium">Proveedor</th>
                <th class="px-6 py-3 font-medium">Fecha</th>
                <th class="px-6 py-3 font-medium">Estado</th>
                <th class="px-6 py-3 font-medium text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="purchase in recentPurchases" :key="purchase.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 font-medium text-gray-900">{{ purchase.supplier_name }}</td>
                <td class="px-6 py-3 text-gray-500">{{ purchase.date }}</td>
                <td class="px-6 py-3">
                  <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    {{ purchase.status }}
                  </span>
                </td>
                <td class="px-6 py-3 font-medium text-gray-900 text-right">{{ formatCurrency(Number(purchase.total)) }}
                </td>
              </tr>
              <tr v-if="!recentPurchases || recentPurchases.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">Sin compras recientes.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
