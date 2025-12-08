import { Warehouse } from './warehouse';

export interface Inventory {
  id: number;
  inventoryable_type: string;
  inventoryable_id: number;
  warehouse_id: number;
  warehouse?: Warehouse;
  quantity_in: number;
  quantity_out: number;
  quantity_balance: number;
  detail: string;
  created_at: string;
  updated_at: string;
}

export interface InventoryFilters {
  warehouse_id: number | string | null;
  fecha_inicial: string | null;
  fecha_final: string | null;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PaginatedInventory {
  data: Inventory[];
  links: PaginationLink[];
  current_page: number;
  last_page: number;
  total: number;
  from: number;
  to: number;
  prev_page_url: string | null;
  next_page_url: string | null;
  path?: string;
  per_page?: number;
}

export interface DashboardStats {
  products_count: number;
  variants_count: number;
  warehouses_count: number;
  categories_count: number;
  attributes_count: number;
  total_stock: number;
}

export interface CategoryDistribution {
  name: string;
  value: number;
}

export interface WarehouseStockDistribution {
  name: string;
  total_stock: number;
}
