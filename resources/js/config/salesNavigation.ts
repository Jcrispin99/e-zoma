import { NavigationItem } from '@/components/layouts/ModuleLayout.vue';
import ventasIconRaw from '@/assets/images/iconos-modulos/ventas-koodi.png';

export const salesIcon = ventasIconRaw;

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
