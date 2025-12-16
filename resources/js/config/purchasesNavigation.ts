import { NavigationItem } from '@/components/layouts/ModuleLayout.vue';
import inventarioIconRaw from '@/assets/images/iconos-modulos/inventario-koodi.png';

export const purchasesIcon = inventarioIconRaw;

export const purchasesNavigation: NavigationItem[] = [
  { label: 'Información general', href: '/finanzas/compras' },
  { label: 'Proveedores', href: '/finanzas/compras/proveedores' },
  { label: 'Ordenes de Compra', href: '/finanzas/compras/ordenes' },
];
