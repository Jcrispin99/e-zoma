import { Variant, AttributeValue, Product } from './product';

export interface Customer {
  id: number;
  identity_id: number;
  document_number: string;
  name: string;
  address: string;
  email: string;
  phone: string;
}

export interface Identity {
  id: number;
  name: string;
  code?: string;
}

export interface Tax {
  id: number;
  name: string;
  rate_percent: string | number;
  is_price_inclusive: boolean | number;
  is_default: boolean | number;
  invoice_label?: string;
}

export interface Journal {
  id: number;
  name: string;
  code: string;
  serie?: string;
}

export interface SaleItem extends Variant {
  pivot?: {
    quantity: number;
    price: number;
    tax_rate: number;
    subtotal: number;
  };
  product?: Product;
  attribute_values?: AttributeValue[];
}

export interface VariantOption extends Variant {
  full_name?: string;
}

export interface FormItem {
  id: number;
  name: string;
  quantity: number | string;
  price: number | string;
  tax_id: number | string;
  tax_rate: number;
  tax_inclusive: boolean;
  subtotal: number;
}

export interface Sale {
  id: number;
  customer_id: number;
  identity_id: number;
  document_number: string;
  name: string;
  address: string;
  email: string;
  phone: string;
  journal_id?: number;
  serie?: string;
  correlative?: string;
  date?: string;
  total?: number;
  observation?: string;
  status?: string;
  payment_status?: string;
  variants?: SaleItem[];
  quote_id?: number;
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
