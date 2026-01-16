import { NavigationItem } from '@/components/layouts/ModuleLayout.vue';
import inventarioIconRaw from '@/assets/images/iconos-modulos/inventario-koodi.png';

export const salesIcon = inventarioIconRaw;

export const salesNavigation: NavigationItem[] = [
  { label: 'Información general', href: '/finanzas/ventas' },
  { label: 'Clientes', href: '/finanzas/ventas/clientes' },
  {
    label: 'Ventas',
    sections: [
      {
        title: 'Órdenes',
        items: [
          { label: 'Órdenes de Venta', href: '/finanzas/ventas/ordenes' },
          { label: 'Ventas (Facturas)', href: '/finanzas/ventas/facturas' },
        ],
      },
    ],
  },
];
