import { defineStore } from 'pinia';
import { getCache, setCache } from '../composables/useCache.js';

// Util para origen backend (Blade inyecta meta)
const backendOrigin =
  typeof document !== 'undefined'
    ? document.querySelector('meta[name="backend-origin"]')?.content ||
      window.location.origin
    : '';

export const useLoyaltyStore = defineStore('pos-loyalty', {
  state: () => ({
    loading: false,
    error: null,
    // Configuración del programa de puntos
    config: {
      program_id: null,
      active_for_pos: false,
      earn_per_sol: 0, // puntos por sol gastado (entero)
      soles_per_point: 0, // S/ de descuento por punto gastado
      max_discount_amount: null, // tope opcional S/
      can_redeem: false, // si existe recompensa para gastar puntos
    },
    // Cuenta del cliente
    account: {
      customer_id: null,
      points_balance: 0,
      status: 'inactive',
    },
  }),
  getters: {
    hasPoints(state) {
      return Number(state.account.points_balance || 0) > 0;
    },
    canRedeem(state) {
      return !!state.config.active_for_pos && !!state.config.can_redeem && Number(state.account.points_balance || 0) > 0 && Number(state.config.soles_per_point || 0) > 0;
    },
  },
  actions: {
    getXsrfToken() {
      const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
      return match ? decodeURIComponent(match[1]) : null;
    },
    async fetchConfig() {
      this.loading = true;
      this.error = null;
      try {
        // Asegurar cookie CSRF para peticiones stateful
        if (!this.getXsrfToken()) {
          await fetch(new URL('/sanctum/csrf-cookie', backendOrigin), {
            credentials: 'include',
          });
        }
        const token = this.getXsrfToken();
        const res = await fetch(new URL('/api/loyalty/config', backendOrigin), {
          method: 'GET',
          credentials: 'include',
          headers: {
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
          },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.config = {
          program_id: data?.program_id || null,
          active_for_pos: !!data?.active_for_pos,
          earn_per_sol: Number(data?.earn_per_sol || 0),
          soles_per_point: Number(data?.soles_per_point || 0),
          max_discount_amount: data?.max_discount_amount ?? null,
          can_redeem: !!data?.can_redeem,
        };
        setCache('pos:loyalty:config', this.config);
      } catch (e) {
        // Fallback offline: usar cache local si existe
        this.error = e?.message || 'Error al cargar config de lealtad';
        const cached = getCache('pos:loyalty:config', null);
        if (cached) this.config = cached;
      } finally {
        this.loading = false;
      }
    },
    async fetchAccount(customerId) {
      if (!customerId) {
        this.account = { customer_id: null, points_balance: 0, status: 'inactive' };
        return;
      }
      this.loading = true;
      this.error = null;
      try {
        if (!this.getXsrfToken()) {
          await fetch(new URL('/sanctum/csrf-cookie', backendOrigin), {
            credentials: 'include',
          });
        }
        const token = this.getXsrfToken();
        const res = await fetch(
          new URL(`/api/loyalty/account/${customerId}`, backendOrigin),
          {
            method: 'GET',
            credentials: 'include',
            headers: {
              Accept: 'application/json',
              ...(token ? { 'X-XSRF-TOKEN': token } : {}),
            },
          }
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.account = {
          customer_id: customerId,
          points_balance: Number(data?.points_balance || 0),
          status: data?.status || 'active',
        };
        setCache(`pos:loyalty:account:${customerId}`, this.account);
      } catch (e) {
        // Fallback offline
        this.error = e?.message || 'Error al cargar cuenta de lealtad';
        const cached = getCache(`pos:loyalty:account:${customerId}`, null);
        if (cached) this.account = cached;
      } finally {
        this.loading = false;
      }
    },
    // Calcula descuento por puntos a gastar, limitado por tope y total
    calculateDiscount(pointsToSpend, orderTotal) {
      if (!this.config.active_for_pos) return 0;
      const pts = Math.max(0, Math.floor(Number(pointsToSpend || 0)));
      const rate = Number(this.config.soles_per_point || 0);
      if (!rate || pts <= 0) return 0;
      let discount = pts * rate;
      const max = this.config.max_discount_amount;
      if (Number.isFinite(max) && max > 0) {
        discount = Math.min(discount, max);
      }
      discount = Math.min(discount, Number(orderTotal || 0));
      // Redondear a centavos
      return Math.round(discount * 100) / 100;
    },
    // Calcula puntos a acumular basado en total (después del descuento) y la tasa
    calculateEarnedPoints(netTotal) {
      if (!this.config.active_for_pos) return 0;
      const rate = Number(this.config.earn_per_sol || 0);
      if (!rate || netTotal <= 0) return 0;
      // Puntos decimales (2 decimales) para acumular con precisión
      const raw = Number(netTotal || 0) * rate;
      return Math.max(0, Math.round(raw * 100) / 100);
    },
  },
});
