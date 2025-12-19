import { Variant } from './product';

export interface Supplier {
  id: number;
  name: string;
  identity_id?: number | string;
  document_type?: string;
  document_number: string;
  email?: string;
  phone?: string;
  address?: string;
  is_active?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Tax {
  id: number;
  name: string;
  rate_percent: string | number;
  is_price_inclusive: boolean | number;
  invoice_label?: string;
}

export interface Journal {
  id: number;
  name: string;
  serie?: string;
  code?: string;
}

export interface PurchaseOrderItemPivot {
  purchase_order_id: number;
  variant_id: number;
  quantity: number;
  price: number;
  tax_rate: number;
  subtotal: number;
  created_at?: string;
  updated_at?: string;
}

export interface PurchaseOrderItem extends Variant {
  pivot?: PurchaseOrderItemPivot;
  tax_id?: number | string;
  full_name?: string;
  attribute_values?: any[];
}

export interface VariantOption extends Variant {
  full_name?: string;
  attribute_values?: any[];
}

export interface PurchaseOrder {
  id: number;
  supplier_id: number;
  supplier?: Supplier;
  journal_id?: number | null;
  serie?: string | null;
  correlative?: string | null;
  date: string;
  observation?: string | null;
  total: number;
  status: string;
  variants?: PurchaseOrderItem[];
  items?: any[];
  created_at: string;
  updated_at: string;
}

export interface PaginatedData<T> {
  data: T[];
  total: number;
  per_page: number;
  current_page: number;
  from: number;
  to: number;
  prev_page_url: string | null;
  next_page_url: string | null;
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
}

export interface Identity {
  id: number;
  name: string;
  code?: string;
}

export interface Purchase {
  id: number;
  supplier_id: number;
  supplier?: Supplier;
  journal_id?: number | null;
  serie?: string | null;
  correlative?: string | null;
  date: string;
  total: number;
  status: string;
  purchase_order_id?: number | null;
  purchase_order?: PurchaseOrder;
  variants?: PurchaseOrderItem[];
  observation?: string | null;
  created_at?: string;
  updated_at?: string;
}
