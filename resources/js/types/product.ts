export interface Category {
  id: number;
  name: string;
  description?: string;
  slug?: string;
  full_name?: string;
  parent_id?: number | null;
  parent?: Category;
  created_at: string;
  updated_at: string;
}

export interface AttributeValue {
  id: number;
  attribute_id: number;
  value: string;
  created_at: string;
  updated_at: string;
}

export interface Attribute {
  id: number;
  name: string;
  attribute_values?: AttributeValue[];
  attribute_values_count?: number;
  created_at: string;
  updated_at: string;
}

export interface Image {
  id: number;
  path: string;
  size?: number;
  imageable_id: number;
  imageable_type: string;
  url?: string;
  created_at: string;
  updated_at: string;
}

export interface Variant {
  id: number;
  product_id: number;
  sku: string;
  barcode: string;
  price: number;
  stock: number;
  is_principal?: boolean;
  attribute_values?: AttributeValue[];
  attributes?: Record<string, string>;
  name?: string;
  product?: Product;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  name: string;
  description?: string;
  price: number;
  category_id: number;
  category?: Category;
  sku?: string;
  barcode?: string;
  image?: string;
  images?: Image[];
  variants?: Variant[];
  created_at: string;
  updated_at: string;
}

export interface ProductsData {
  data: Product[];
  current_page: number;
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

export interface AttributeLine {
  attribute_id: string | number;
  values: string[];
}

export interface FormVariant {
  name: string;
  sku: string;
  price: string | number;
  barcode: string;
  stock: string | number;
  attributes: Record<string, string>;
}

export interface AdditionalImage {
  id?: number;
  url: string;
  file?: File;
}
