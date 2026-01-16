export interface Customer {
  identity_id: number;
  document_number: string;
  name: string;
  address: string;
  email: string;
  phone: string;
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