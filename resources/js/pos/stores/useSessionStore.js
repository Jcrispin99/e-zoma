import { defineStore } from 'pinia';
import { getCache, setCache } from '../composables/useCache.js';

export const useSessionStore = defineStore('pos-session', {
  state: () => ({
    sessionId: null,
    session: null,
    config: null,
    sequences: null,
    defaultCustomer: null,
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
          `/api/pos-sessions/${this.sessionId}/bootstrap`,
          {
            method: 'GET',
            credentials: 'include',
            headers: { Accept: 'application/json' },
          }
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.session = data.session;
        this.config = data.config;
        this.sequences = data.sequences;
        this.defaultCustomer = data.default_customer;
        this.categories = data.categories || [];
        this.variants = data.variants || [];
        this.online = true;
        setCache('pos:categories', this.categories);
        setCache('pos:variants', this.variants);
      } catch (e) {
        this.error = e.message || 'Bootstrap failed';
        this.online = false;
        this.categories = getCache('pos:categories', []);
        this.variants = getCache('pos:variants', []);
      } finally {
        this.loading = false;
      }
    },
    async sync(orders) {
      if (!this.sessionId) throw new Error('No session');
      if (!this.getXsrfToken()) {
        await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
      }
      const token = this.getXsrfToken();
      const res = await fetch(`/api/pos-sessions/${this.sessionId}/sync`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({ orders }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      return data;
    },
    async closeSession(closingBalance) {
      if (!this.sessionId) throw new Error('No session');
      if (!this.getXsrfToken()) {
        await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
      }
      const token = this.getXsrfToken();
      const res = await fetch(`/api/pos-sessions/${this.sessionId}/close`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify({ closing_balance: closingBalance }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      return data;
    },
    async setOpeningBalance(amount) {
      if (!this.sessionId) throw new Error('No session');
      if (!this.getXsrfToken()) {
        await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
      }
      const token = this.getXsrfToken();
      const res = await fetch(
        `/api/pos-sessions/${this.sessionId}/opening-balance`,
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
  },
});
