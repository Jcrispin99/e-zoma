import type { AppNotificationType } from './models';
export interface StatCardProps {
  title: string;
  value: string | number;
  icon?: string;
  iconBgColor?: string;
  trend?: TrendData;
}

export interface TrendData {
  direction: 'up' | 'down';
  value: string;
}

// MenuCard Props
export interface MenuCardProps {
  title: string;
  icon: string;
  route: string;
}

// Button Props
export interface ButtonProps {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  disabled?: boolean;
  type?: 'button' | 'submit' | 'reset';
  fullWidth?: boolean;
}

export type ButtonVariant =
  | 'primary'
  | 'secondary'
  | 'danger'
  | 'success'
  | 'warning'
  | 'info'
  | 'ghost';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

// Modal Props
export interface ModalProps {
  show: boolean;
  title?: string;
  size?: ModalSize;
  closeOnBackdrop?: boolean;
  closeOnEscape?: boolean;
  showClose?: boolean;
}

export type ModalSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full';

// Table Props
export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  width?: string;
  align?: 'left' | 'center' | 'right';
  format?: (value: any, row: any) => string;
  render?: (value: any, row: any) => any;
}

export interface TableProps {
  columns: TableColumn[];
  data: any[];
  loading?: boolean;
  selectable?: boolean;
  striped?: boolean;
  hoverable?: boolean;
  bordered?: boolean;
  compact?: boolean;
}

export interface TableSelection {
  selected: any[];
  isSelected: (item: any) => boolean;
  select: (item: any) => void;
  selectAll: () => void;
  clearSelection: () => void;
}

// Form Props
export interface FormFieldProps {
  label?: string;
  name: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  hint?: string;
}

export interface SelectOption {
  label: string;
  value: string | number;
  disabled?: boolean;
}

// Badge Props
export interface BadgeProps {
  variant?: BadgeVariant;
  size?: BadgeSize;
  rounded?: boolean;
  dot?: boolean;
}

export type BadgeVariant =
  | 'primary'
  | 'secondary'
  | 'success'
  | 'warning'
  | 'error'
  | 'info';
export type BadgeSize = 'xs' | 'sm' | 'md' | 'lg';

// Alert Props
export interface AlertProps {
  type?: AppNotificationType;
  title?: string;
  message: string;
  dismissible?: boolean;
  icon?: boolean;
}

// Pagination Props
export interface PaginationProps {
  currentPage: number;
  totalPages: number;
  perPage?: number;
  total?: number;
  showInfo?: boolean;
}

// Breadcrumb
export interface BreadcrumbItem {
  label: string;
  route?: string;
  icon?: string;
}

// Card Props
export interface CardProps {
  title?: string;
  subtitle?: string;
  footer?: boolean;
  bordered?: boolean;
  shadow?: boolean;
  hoverable?: boolean;
  padding?: boolean;
}

// Tabs
export interface TabItem {
  id: string;
  label: string;
  icon?: string;
  disabled?: boolean;
  badge?: string | number;
}

// Dropdown
export interface DropdownItem {
  label: string;
  value?: any;
  icon?: string;
  disabled?: boolean;
  divider?: boolean;
  onClick?: () => void;
}

// Toast/Notification
export interface ToastOptions {
  duration?: number;
  position?: ToastPosition;
  closable?: boolean;
}

export type ToastPosition =
  | 'top-left'
  | 'top-center'
  | 'top-right'
  | 'bottom-left'
  | 'bottom-center'
  | 'bottom-right';
