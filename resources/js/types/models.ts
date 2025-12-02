export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  phone?: string;
  status: UserStatus;
  created_at: string;
  updated_at: string;
}

export type UserRole = 'admin' | 'manager' | 'user' | 'cashier';
export type UserStatus = 'active' | 'inactive' | 'suspended';

// Product
export interface Product {
  id: number;
  code: string;
  name: string;
  description?: string;
  price: number;
  cost?: number;
  stock: number;
  min_stock: number;
  category_id: number;
  category?: Category;
  image?: string;
  images?: string[];
  barcode?: string;
  sku?: string;
  status: ProductStatus;
  created_at: string;
  updated_at: string;
}

export type ProductStatus = 'active' | 'inactive' | 'out_of_stock';

// Category
export interface Category {
  id: number;
  name: string;
  description?: string;
  parent_id?: number;
  created_at: string;
  updated_at: string;
}

// Sale
export interface Sale {
  id: number;
  code: string;
  user_id: number;
  user?: User;
  customer_id?: number;
  customer?: Customer;
  total: number;
  subtotal: number;
  tax: number;
  discount: number;
  payment_method: PaymentMethod;
  status: SaleStatus;
  items: SaleItem[];
  notes?: string;
  created_at: string;
  updated_at: string;
}

export type SaleStatus = 'pending' | 'completed' | 'cancelled' | 'refunded';
export type PaymentMethod = 'cash' | 'card' | 'transfer' | 'mixed';

// Sale Item
export interface SaleItem {
  id: number;
  sale_id: number;
  product_id: number;
  product?: Product;
  quantity: number;
  price: number;
  discount: number;
  subtotal: number;
  total: number;
}

// Customer
export interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  document_type?: DocumentType;
  document_number?: string;
  created_at: string;
  updated_at: string;
}

export type DocumentType = 'dni' | 'ruc' | 'passport' | 'other';

// Dashboard Stats
export interface DashboardStats {
  ventasHoy: string;
  pedidosPendientes: number;
  alertasStock: number;
}

// App Notification (renombrado para evitar conflicto con DOM Notification)
export interface AppNotification {
  id: number;
  title: string;
  message: string;
  type: AppNotificationType;
  read: boolean;
  created_at: string;
}

export type AppNotificationType = 'info' | 'success' | 'warning' | 'error';

// Inventory
export interface InventoryMovement {
  id: number;
  product_id: number;
  product?: Product;
  type: MovementType;
  quantity: number;
  previous_stock: number;
  new_stock: number;
  user_id: number;
  user?: User;
  notes?: string;
  created_at: string;
}

export type MovementType = 'in' | 'out' | 'adjustment' | 'return';

// Employee
export interface Employee {
  id: number;
  user_id?: number;
  user?: User;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  address?: string;
  position: string;
  department?: string;
  salary?: number;
  hire_date: string;
  status: EmployeeStatus;
  created_at: string;
  updated_at: string;
}

export type EmployeeStatus = 'active' | 'inactive' | 'on_leave' | 'terminated';

// Report
export interface Report {
  id: number;
  name: string;
  type: ReportType;
  date_from: string;
  date_to: string;
  data: any;
  generated_by: number;
  created_at: string;
}

export type ReportType = 'sales' | 'inventory' | 'financial' | 'custom';
