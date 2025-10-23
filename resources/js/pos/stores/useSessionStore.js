import { defineStore } from 'pinia';
import { getCache, setCache } from '../composables/useCache.js';

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
    defaultCustomer: null,
    selectedCustomer: null,
    categories: [],
    variants: [],
    online: true,
    loading: false,
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
        this.defaultCustomer = data.default_customer;
        this.selectedCustomer = data.default_customer;
        this.categories = data.categories || [];
        this.variants = data.variants || [];
        this.online = true;
        setCache('pos:categories', this.categories);
        setCache('pos:variants', this.variants);
      } catch (e) {
        this.error = e.message || 'Bootstrap failed';
        // 401 no es "offline"; mantener online hasta tener indicador de red real
        if (!(e && e.status === 401)) {
          this.online = false;
        }
        this.categories = getCache('pos:categories', []);
        this.variants = getCache('pos:variants', []);
      } finally {
        this.loading = false;
      }
    },
    async sync(orders) {
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
  },
});
