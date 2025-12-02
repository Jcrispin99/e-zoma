import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from 'vue-router';
import MainLayout from '@/layouts/MainLayout.vue';
import type { UserRole } from '@/types';

// Lazy loading de páginas
const MiniDashboard = () => import('@/pages/MainApps.vue');

// Finanzas
const POS = () => import('@/pages/finanzas/POS.vue');
const Inventario = () => import('@/pages/finanzas/Inventario.vue');
const Formas = () => import('@/pages/finanzas/Formas.vue');
const Profilas = () => import('@/pages/finanzas/Profilas.vue');

// Operaciones
const Accounting = () => import('@/pages/operaciones/Accounting.vue');
const Employees = () => import('@/pages/operaciones/Employees.vue');
const Pendicios = () => import('@/pages/operaciones/Pendicios.vue');
const Crajos = () => import('@/pages/operaciones/Crajos.vue');

// Ventas
const Ventas = () => import('@/pages/ventas/Ventas.vue');
const Sales = () => import('@/pages/ventas/Sales.vue');
const Preciados = () => import('@/pages/ventas/Preciados.vue');

// Extender RouteRecordRaw para incluir meta types
declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
    section?: string;
    title?: string;
    roles?: UserRole[];
    icon?: string;
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: MiniDashboard,
        meta: {
          requiresAuth: true,
          title: 'Dashboard',
          icon: 'home',
        },
      },

      // ============================================
      // FINANZAS
      // ============================================
      {
        path: 'finanzas/pos',
        name: 'finanzas.pos',
        component: POS,
        meta: {
          requiresAuth: true,
          section: 'finanzas',
          title: 'POS - Punto de Venta',
          icon: 'calculator',
        },
      },
      {
        path: 'finanzas/inventario',
        name: 'finanzas.inventario',
        component: Inventario,
        meta: {
          requiresAuth: true,
          section: 'finanzas',
          title: 'Inventario',
          icon: 'box',
        },
      },
      {
        path: 'finanzas/formas',
        name: 'finanzas.formas',
        component: Formas,
        meta: {
          requiresAuth: true,
          section: 'finanzas',
          title: 'Formas de Pago',
          icon: 'credit-card',
        },
      },
      {
        path: 'finanzas/profilas',
        name: 'finanzas.profilas',
        component: Profilas,
        meta: {
          requiresAuth: true,
          section: 'finanzas',
          title: 'Profilas',
          icon: 'tag',
        },
      },

      // ============================================
      // OPERACIONES
      // ============================================
      {
        path: 'operaciones/accounting',
        name: 'operaciones.accounting',
        component: Accounting,
        meta: {
          requiresAuth: true,
          section: 'operaciones',
          title: 'Contabilidad',
          icon: 'document',
        },
      },
      {
        path: 'operaciones/employees',
        name: 'operaciones.employees',
        component: Employees,
        meta: {
          requiresAuth: true,
          section: 'operaciones',
          title: 'Empleados',
          icon: 'users',
        },
      },
      {
        path: 'operaciones/pendicios',
        name: 'operaciones.pendicios',
        component: Pendicios,
        meta: {
          requiresAuth: true,
          section: 'operaciones',
          title: 'Pendicios',
          icon: 'user-group',
        },
      },
      {
        path: 'operaciones/crajos',
        name: 'operaciones.crajos',
        component: Crajos,
        meta: {
          requiresAuth: true,
          section: 'operaciones',
          title: 'Caja',
          icon: 'cash',
        },
      },

      // ============================================
      // VENTAS
      // ============================================
      {
        path: 'ventas/ventas',
        name: 'ventas.ventas',
        component: Ventas,
        meta: {
          requiresAuth: true,
          section: 'ventas',
          title: 'Ventas',
          icon: 'shopping-bag',
        },
      },
      {
        path: 'ventas/sales',
        name: 'ventas.sales',
        component: Sales,
        meta: {
          requiresAuth: true,
          section: 'ventas',
          title: 'Reportes de Ventas',
          icon: 'chart',
        },
      },
      {
        path: 'ventas/preciados',
        name: 'ventas.preciados',
        component: Preciados,
        meta: {
          requiresAuth: true,
          section: 'ventas',
          title: 'Preciados',
          icon: 'bell',
        },
      },
    ],
  },

  // ============================================
  // RUTA 404 - Not Found
  // ============================================
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFound.vue'),
    meta: {
      title: 'Página no encontrada',
    },
  },
];

const router = createRouter({
  history: createWebHistory('/'),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }
    return { top: 0 };
  },
});

// ============================================
// Navigation Guards
// ============================================
router.beforeEach((to, from, next) => {
  // Verificar autenticación
  if (to.meta.requiresAuth) {
    // TODO: Implementar verificación de autenticación real
    // const userStore = useUserStore();
    // const isAuthenticated = userStore.isAuthenticated;

    const isAuthenticated = true; // Por ahora dejamos true

    if (!isAuthenticated) {
      // Redirigir a login si no está autenticado
      next({
        path: '/login',
        query: { redirect: to.fullPath },
      });
      return;
    }
  }

  // Verificar roles si es necesario
  if (to.meta.roles && to.meta.roles.length > 0) {
    // TODO: Implementar verificación de roles
    // const userStore = useUserStore();
    // const userRole = userStore.user?.role;
    // if (!userRole || !to.meta.roles.includes(userRole)) {
    //   next('/unauthorized');
    //   return;
    // }
  }

  // Actualizar título de la página
  if (to.meta.title) {
    document.title = `${to.meta.title} - Kdosh Store`;
  } else {
    document.title = 'Kdosh Store';
  }

  next();
});

// After navigation hook
router.afterEach((to, from) => {
  // Aquí puedes agregar analytics, logging, etc.
  console.log(`Navegando de ${from.path} a ${to.path}`);
});

// Error handler
router.onError((error) => {
  console.error('Router error:', error);
});

export default router;
