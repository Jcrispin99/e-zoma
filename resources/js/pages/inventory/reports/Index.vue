<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import { AlertTriangle, DollarSign, ClipboardList, Package, TrendingUp, Activity } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import StatCard from '@/components/ui/dashboard/StatCard.vue';

const props = defineProps<{
    stats: {
        total_valuation: number;
        low_stock_count: number;
        total_items: number;
        total_movements: number;
    }
}>();

const navigationItems = inventoryNavigation;

const reports = [
    {
        title: 'Stock Bajo',
        description: 'Productos con existencias por debajo del umbral mínimo. Acción requerida.',
        icon: AlertTriangle,
        color: 'bg-red-50 text-red-600',
        border: 'border-red-100 hover:border-red-300',
        route: '/finanzas/inventario/reportes/bajo-stock',
    },
    {
        title: 'Valorización',
        description: 'Análisis del valor total del inventario (Costo x Stock).',
        icon: DollarSign,
        color: 'bg-green-50 text-green-600',
        border: 'border-green-100 hover:border-green-300',
        route: '/finanzas/inventario/reportes/valorizacion',
    },
    {
        title: 'Movimientos',
        description: 'Kardex completo de entradas y salidas de inventario.',
        icon: ClipboardList,
        color: 'bg-blue-50 text-blue-600',
        border: 'border-blue-100 hover:border-blue-300',
        route: '/finanzas/inventario/reportes/transacciones',
    },
];
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <div class="space-y-8 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Panel de Reportes</h1>
                    <p class="text-gray-500 mt-1">Resumen general y herramientas de análisis</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard title="Total Items" :value="stats.total_items" :icon="Package" color="indigo" />

                    <StatCard title="Valorización" :value="stats.total_valuation" :icon="DollarSign" color="green">
                        <template #value>
                            {{ Number(stats.total_valuation).toLocaleString('es-PE', {
                                style: 'currency', currency:
                                    'PEN', maximumFractionDigits: 0
                            }) }}
                        </template>
                    </StatCard>

                    <StatCard title="Stock Bajo" :value="stats.low_stock_count" :icon="AlertTriangle" color="red" />

                    <StatCard title="Total Movimientos" :value="stats.total_movements" :icon="Activity" color="blue" />
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Reportes Disponibles</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="report in reports" :key="report.title" @click="router.visit(report.route)"
                        class="bg-white rounded-xl p-6 cursor-pointer transition-all duration-200 group border"
                        :class="[report.border, 'hover:shadow-lg hover:-translate-y-1']">

                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 rounded-xl transition-colors" :class="report.color">
                                <component :is="report.icon" class="w-8 h-8" />
                            </div>
                            <div class="bg-gray-50 group-hover:bg-gray-100 p-2 rounded-full transition-colors">
                                <TrendingUp class="w-4 h-4 text-gray-400 group-hover:text-gray-600" />
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ report.title }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ report.description }}</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="font-bold text-lg mb-2">💡 Tip de Inventario</h3>
                    <p class="text-gray-300 max-w-2xl">
                        Mantén tu inventario saludable revisando el reporte de
                        <span class="text-white font-medium underline decoration-red-500 underline-offset-2">Stock
                            Bajo</span>
                        semanalmente. Un control proactivo reduce pérdidas y mejora el flujo de caja.
                    </p>
                </div>
                <div class="absolute right-0 top-0 h-full w-1/3 opacity-10 bg-white transform skew-x-12 translate-x-12">
                </div>
            </div>
        </div>
    </ModuleLayout>
</template>
