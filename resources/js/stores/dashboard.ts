import { defineStore } from 'pinia';
import { ref, type Ref } from 'vue';
import type { DashboardStats } from '../types';
import { api } from '../lib/api';

export const useDashboardStore = defineStore('dashboard', () => {
  // State
  const stats = ref<DashboardStats>({
    ventasHoy: 'S/ 0',
    pedidosPendientes: 0,
    alertasStock: 0,
  });
  const loading = ref(false);
  const error = ref<string | null>(null);
  const lastUpdated = ref<Date | null>(null);

  // Actions
  const loadDashboardData = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get<DashboardStats>('/dashboard/stats');
      stats.value = response.data.data;
      lastUpdated.value = new Date();
    } catch (err: any) {
      error.value = err.message || 'Failed to load dashboard data';

      // Datos de ejemplo en caso de error
      stats.value = {
        ventasHoy: 'S/ 1,250',
        pedidosPendientes: 5,
        alertasStock: 2,
      };
    } finally {
      loading.value = false;
    }
  };

  const refreshStats = async (): Promise<void> => {
    await loadDashboardData();
  };

  const incrementPedidosPendientes = (): void => {
    stats.value.pedidosPendientes++;
  };

  const decrementPedidosPendientes = (): void => {
    if (stats.value.pedidosPendientes > 0) {
      stats.value.pedidosPendientes--;
    }
  };

  return {
    // State
    stats,
    loading,
    error,
    lastUpdated,
    // Actions
    loadDashboardData,
    refreshStats,
    incrementPedidosPendientes,
    decrementPedidosPendientes,
  };
});
