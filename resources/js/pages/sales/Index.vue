<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { salesNavigation, salesIcon } from '@/config/salesNavigation';
import { ShoppingCart, FileText, Users, Package, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StatCard from '@/components/ui/dashboard/StatCard.vue';
import ChartCard from '@/components/ui/dashboard/ChartCard.vue';

const props = defineProps<{
    stats: {
        sales_count: number;
        orders_count: number;
        customers_count: number;
        products_count: number;
        total_sales: number;
    };
    recentSales: Array<{
        id: number;
        customer_name: string;
        total: number;
        date: string;
        status: string;
    }>;
    monthlySales: Array<{ month: string; total: number }>;
    topCustomers: Array<{ name: string; value: number }>;
}>();

const navigationItems = salesNavigation;

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value);
};

const statusTranslate = (status: string) => {
    switch (status) {
        case 'draft':
            return 'Borrador';
        case 'pending':
            return 'Pendiente';
        case 'posted':
            return 'Publicada';
        case 'cancelled':
            return 'Anulada';
        default:
            return 'Desconocido';
    }
};

const spendSeries = computed(() => [{
    name: 'Venta Mensual',
    data: props.monthlySales.map(s => Number(s.total))
}]);
const spendCategories = computed(() => props.monthlySales.map(s => s.month));
const spendColors = ['#0d9488'];

const customerSeries = computed(() => props.topCustomers.map(s => Number(s.value)));
const customerLabels = computed(() => props.topCustomers.map(s => s.name));
const customerColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
</script>

<template>
    <ModuleLayout title="Ventas" :icon="salesIcon" :navigation-items="navigationItems">
        <div class="space-y-6 mx-9">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Información General</h1>
                    <p class="text-gray-500 mt-1">Resumen general de ingresos y ventas</p>
                </div>
            </div>

            <div v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <StatCard title="Ventas Totales" :value="stats.sales_count" :icon="ShoppingCart"
                    icon-class="text-teal-600" />
                <StatCard title="Cotizaciones" :value="stats.orders_count" :icon="FileText"
                    icon-class="text-indigo-600" />
                <StatCard title="Clientes" :value="stats.customers_count" :icon="Users" icon-class="text-blue-600" />
                <StatCard title="Productos" :value="stats.products_count" :icon="Package"
                    icon-class="text-purple-600" />
                <StatCard title="Venta Total" :value="formatCurrency(stats.total_sales)" :icon="Wallet"
                    icon-class="text-orange-600" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ChartCard title="Tendencia de Venta Mensual" type="bar" :series="spendSeries"
                    :categories="spendCategories" :colors="spendColors" />
                <ChartCard title="Top Clientes (por venta)" type="donut" :series="customerSeries"
                    :labels="customerLabels" :colors="customerColors" />
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Últimas Ventas</h3>
                    <button @click="router.visit('/finanzas/ventas/facturas')"
                        class="text-sm text-teal-600 hover:text-teal-700 font-medium">Ver todas</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Cliente</th>
                                <th class="px-6 py-3 font-medium">Fecha</th>
                                <th class="px-6 py-3 font-medium">Estado</th>
                                <th class="px-6 py-3 font-medium text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sale in recentSales" :key="sale.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ sale.customer_name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ sale.date }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-700">
                                        {{ statusTranslate(sale.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-medium text-gray-900 text-right">{{
                                    formatCurrency(Number(sale.total)) }}
                                </td>
                            </tr>
                            <tr v-if="!recentSales || recentSales.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">Sin ventas recientes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
