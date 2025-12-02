import { PaymentMethod, ProductStatus, SaleStatus, User } from './models';

export interface ApiResponse<T = any> {
  success: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
}

// Respuesta paginada
export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
  links?: PaginationLinks;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface PaginationLinks {
  first?: string;
  last?: string;
  prev?: string;
  next?: string;
}

// Error de API
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
  code?: string;
}

// Auth
export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
  expires_in?: number;
}

// Filtros genéricos
export interface BaseFilter {
  search?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

// Filtros de productos
export interface ProductFilter extends BaseFilter {
  category_id?: number;
  status?: ProductStatus;
  min_price?: number;
  max_price?: number;
  min_stock?: number;
  max_stock?: number;
}

// Filtros de ventas
export interface SaleFilter extends BaseFilter {
  date_from?: string;
  date_to?: string;
  status?: SaleStatus;
  payment_method?: PaymentMethod;
  user_id?: number;
  customer_id?: number;
}
