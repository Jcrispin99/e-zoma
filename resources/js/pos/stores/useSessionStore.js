import { defineStore } from 'pinia';
import { getCache, setCache } from '../composables/useCache.js';
import { watch } from 'vue';

// Util para origen backend (Blade inyecta meta)
const backendOrigin =
  typeof document !== 'undefined'
    ? document.querySelector('meta[name="backend-origin"]')?.content ||
      window.location.origin
    : '';

export const useSessionStore = defineStore('pos-session', {
  state: () => ({
    sessionId: null,
    session: null,
    config: null,
    sequences: null,
    company: null,
    pos: null,
    seller: null,
    defaultCustomer: null,
    selectedCustomer: null,
    categories: [],
    variants: [],
    online: true,
    loading: false,
    pendingSyncs: getCache('pos:pendingSyncs', []),
    error: null,
  }),
  actions: {
    getXsrfToken() {
      const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
      return match ? decodeURIComponent(match[1]) : null;
    },
    initFromUrl() {
      const match = window.location.pathname.match(/\/pos\/(\d+)/);
      this.sessionId = match ? Number(match[1]) : null;
    },
    async bootstrap() {
      if (!this.sessionId) return;
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(
          new URL(
            `/api/pos-sessions/${this.sessionId}/bootstrap`,
            backendOrigin
          ),
          {
            method: 'GET',
            credentials: 'include',
            headers: { Accept: 'application/json' },
          }
        );
        if (!res.ok) {
          const err = new Error(`HTTP ${res.status}`);
          err.status = res.status;
          throw err;
        }
        const data = await res.json();
        this.session = data.session;
        this.config = data.config;
        this.sequences = data.sequences;
        this.company = data.company || null;
        this.pos = data.pos || null;
        this.seller = data.seller || null;
        this.defaultCustomer = data.default_customer;
        this.selectedCustomer = data.default_customer;
        this.categories = data.categories || [];
        this.variants = data.variants || [];
        this.online = true;
        setCache('pos:categories', this.categories);
        setCache('pos:variants', this.variants);
        setCache('pos:company', this.company);
        setCache('pos:pos', this.pos);
        setCache('pos:seller', this.seller);
      } catch (e) {
        this.error = e.message || 'Bootstrap failed';
        // 401 no es "offline"; mantener online hasta tener indicador de red real
        if (!(e && e.status === 401)) {
          this.online = false;
        }
        this.categories = getCache('pos:categories', []);
        this.variants = getCache('pos:variants', []);
        this.company = getCache('pos:company', null);
        this.pos = getCache('pos:pos', null);
        this.seller = getCache('pos:seller', null);
      } finally {
        this.loading = false;
      }
    },
    async sync(orders) {
      if (!this.online) {
        console.log('Modo offline: venta guardada en cola.');
        this.pendingSyncs.push(...orders);
        // Persistir inmediatamente por robustez (además del watch)
        try {
          setCache('pos:pendingSyncs', this.pendingSyncs);
        } catch (_) {}
        // Simulamos una respuesta exitosa para el flujo offline
        return { synced: [] };
      }

      try {
        if (!this.sessionId) throw new Error('No session');
        if (!this.getXsrfToken()) {
          await fetch(new URL(`/sanctum/csrf-cookie`, backendOrigin), {
            credentials: 'include',
          });
        }
        const token = this.getXsrfToken();
        const res = await fetch(
          new URL(`/api/pos-sessions/${this.sessionId}/sync`, backendOrigin),
          {
            method: 'POST',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              ...(token ? { 'X-XSRF-TOKEN': token } : {}),
            },
            body: JSON.stringify({ orders }),
          }
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        return data;
      } catch (e) {
        this.setOnline(false);
        console.error('Error al sincronizar, guardando en cola:', e);
        this.pendingSyncs.push(...orders);
        // Persistir inmediatamente por robustez (además del watch)
        try {
          setCache('pos:pendingSyncs', this.pendingSyncs);
        } catch (_) {}
        // Lanzamos el error para que el fallback offline en PaymentPage se active
        throw e;
      }
    },
    async closeSession(closingBalance) {
      if (!this.sessionId) throw new Error('No session');
      if (!this.getXsrfToken()) {
        await fetch(new URL(`/sanctum/csrf-cookie`, backendOrigin), {
          credentials: 'include',
        });
      }
      const token = this.getXsrfToken();
      const res = await fetch(
        new URL(`/api/pos-sessions/${this.sessionId}/close`, backendOrigin),
        {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
          },
          body: JSON.stringify({ closing_balance: closingBalance }),
        }
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      return data;
    },
    async setOpeningBalance(amount) {
      if (!this.sessionId) throw new Error('No session');
      if (!this.getXsrfToken()) {
        await fetch(new URL(`/sanctum/csrf-cookie`, backendOrigin), {
          credentials: 'include',
        });
      }
      const token = this.getXsrfToken();
      const res = await fetch(
        new URL(
          `/api/pos-sessions/${this.sessionId}/opening-balance`,
          backendOrigin
        ),
        {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
          },
          body: JSON.stringify({ opening_balance: amount }),
        }
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      this.session = {
        ...(this.session || {}),
        opening_balance: data.opening_balance,
      };
      return data;
    },
    setOnline(status) {
      this.online = !!status;
    },
    setSelectedCustomer(customer) {
      this.selectedCustomer = customer || null;
    },
    async syncPending() {
      if (this.pendingSyncs.length === 0 || !this.online) {
        return;
      }

      const pending = [...this.pendingSyncs];
      this.pendingSyncs = []; // Limpiar la cola optimistamente

      try {
        await this.sync(pending);
        console.log('Sincronización de ventas pendientes completada.');
      } catch (e) {
        console.error('Fallo al sincronizar ventas pendientes, se reencolarán.');
        // Si falla, las devolvemos al inicio de la cola para reintentar
        this.pendingSyncs.unshift(...pending);
      }
    },
    // Esta acción debe ser llamada una vez en tu componente principal (PosApp.vue)
    setupSyncListeners() {
      // Persistir la cola en localStorage cada vez que cambie
      watch(
        () => this.pendingSyncs,
        (newPendingSyncs) => {
          setCache('pos:pendingSyncs', newPendingSyncs);
        },
        { deep: true }
      );

      // Intentar sincronizar cuando la conexión se recupere
      watch(() => this.online, (isOnline) => {
        if (isOnline && this.pendingSyncs.length > 0) {
          console.log('Conexión recuperada, intentando sincronizar ventas pendientes...');
          this.syncPending();
        }
      });
    }
  },
});
