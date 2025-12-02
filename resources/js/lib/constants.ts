export const APP_NAME = 'Kdosh Store' as const;

export const ROUTES = {
  HOME: '/',
  DASHBOARD: '/dashboard',
  LOGIN: '/login',
  REGISTER: '/register',
  LOGOUT: '/logout',
} as const;

export const API_ENDPOINTS = {
  // Auth
  LOGIN: '/login',
  LOGOUT: '/logout',
  REGISTER: '/register',
  USER: '/user',

  // Dashboard
  DASHBOARD_STATS: '/dashboard/stats',

  // Products
  PRODUCTS: '/products',
  PRODUCT: (id: number) => `/products/${id}`,

  // Sales
  SALES: '/sales',
  SALE: (id: number) => `/sales/${id}`,

  // Users
  USERS: '/users',
  USER_BY_ID: (id: number) => `/users/${id}`,
} as const;

export const NOTIFICATION_TYPES = {
  SUCCESS: 'success',
  ERROR: 'error',
  WARNING: 'warning',
  INFO: 'info',
} as const;

export const USER_ROLES = {
  ADMIN: 'admin',
  MANAGER: 'manager',
  USER: 'user',
} as const;

export const SALE_STATUS = {
  PENDING: 'pending',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
} as const;

export const PAGINATION = {
  DEFAULT_PER_PAGE: 10,
  PER_PAGE_OPTIONS: [10, 25, 50, 100],
} as const;
