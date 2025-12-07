import { NavigationItem } from '@/components/layouts/ModuleLayout.vue';

export const inventoryNavigation: NavigationItem[] = [
  { label: 'Información general', href: '/finanzas/inventario' },
  {
    label: 'Productos',
    items: [
      { label: 'Productos', href: '/finanzas/inventario/productos' },
      { label: 'Variantes', href: '/finanzas/inventario/variantes' },
    ],
  },
  { label: 'Reportes', href: '#' },
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
          { label: 'Categorías', href: '#' },
          { label: 'Atributos', href: '#' },
        ],
      },
    ],
  },
];
