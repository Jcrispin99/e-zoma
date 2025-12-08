import { NavigationItem } from '@/components/layouts/ModuleLayout.vue';
import inventarioIconRaw from '@/assets/images/iconos-modulos/inventario-koodi.png';

export const inventoryIcon = inventarioIconRaw;

export const inventoryNavigation: NavigationItem[] = [
  { label: 'Información general', href: '/finanzas/inventario' },
  {
    label: 'Productos',
    items: [
      { label: 'Productos', href: '/finanzas/inventario/productos' },
      { label: 'Variantes', href: '/finanzas/inventario/variantes' },
    ],
  },
  { label: 'Reportes', href: '/finanzas/inventario/reportes' },
  {
    label: 'Configuración',
    sections: [
      {
        title: 'Gestión del almacén',
        items: [{ label: 'Almacenes', href: '/finanzas/inventario/almacenes' }],
      },
      {
        title: 'Productos',
        items: [
          { label: 'Categorías', href: '/finanzas/inventario/categorias' },
          { label: 'Atributos', href: '/finanzas/inventario/atributos' },
        ],
      },
    ],
  },
];
