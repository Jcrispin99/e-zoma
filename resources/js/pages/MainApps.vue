<script setup lang="ts">
import { ref, watch } from 'vue';
import MenuCard from '@/components/ui/MenuCard.vue';
import draggable from 'vuedraggable';
import posIcon from '@/assets/images/iconos-modulos/pos-koodi.png';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import ventasIcon from '@/assets/images/iconos-modulos/ventas-koodi.png';
import comprasIcon from '@/assets/images/iconos-modulos/compras-koodi.png';
import usuariosIcon from '@/assets/images/iconos-modulos/usuarios-koodi.png';
import configuracionIcon from '@/assets/images/iconos-modulos/configuracion-koodi.png';
import promocionesIcon from '@/assets/images/iconos-modulos/promociones-koodi.png';
import transferenciasIcon from '@/assets/images/iconos-modulos/transferencias-koodi.png';

const defaultMenuItems = [
  {
    id: 2,
    title: 'Inventario',
    description: 'Gestión de stock y almacenes',
    route: '/finanzas/inventario',
    image: inventarioIcon,
  },
  {
    id: 5,
    title: 'Compras',
    description: 'Gestión de compras y proveedores',
    route: '/operaciones/compras',
    image: comprasIcon,
  },
  {
    id: 9,
    title: 'Ventas',
    description: 'Gestión de todas las ventas y sus facturas',
    route: '/operaciones/ventas',
    image: ventasIcon,
  },
  {
    id: 4,
    title: 'Movimientos',
    description: 'Gestión de movimientos de inventario',
    route: '/finanzas/movimientos',
    image: transferenciasIcon,
  },
  {
    id: 1,
    title: 'POS',
    description: 'Punto de venta para mostrador',
    route: '/operaciones/pos',
    image: posIcon,
  },
  {
    id: 11,
    title: 'Promociones',
    description: 'Gestión de ofertas y descuentos',
    route: '/ventas/promociones',
    image: promocionesIcon,
  },
  {
    id: 7,
    title: 'Configuración',
    description: 'Configuración de la empresa, diarios, etc.',
    route: '/operaciones/configuración',
    image: configuracionIcon,
  },
  {
    id: 6,
    title: 'Usuarios',
    description: 'Gestión de usuarios y permisos',
    route: '/operaciones/usuarios',
    image: usuariosIcon,
  },
];

const savedOrder = JSON.parse(localStorage.getItem('menuItems') || 'null');

const menuItems = ref(
  savedOrder
    ? savedOrder.map((savedItem: any) => {
        const defaultItem = defaultMenuItems.find(
          (item) => item.id === savedItem.id
        );
        return defaultItem
          ? {
              ...defaultItem,
              ...savedItem,
              description: defaultItem.description,
              image: defaultItem.image,
            }
          : savedItem;
      })
    : defaultMenuItems
);

watch(
  menuItems,
  (newItems) => {
    localStorage.setItem('menuItems', JSON.stringify(newItems));
  },
  { deep: true }
);
</script>

<template>
  <div
    class="pt-12 md:pt-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-full flex flex-col justify-center items-center"
  >
    <draggable
      v-model="menuItems"
      item-key="id"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 w-full max-w-7xl mx-auto"
      ghost-class="opacity-50"
    >
      <template #item="{ element }">
        <MenuCard
          :title="element.title"
          :description="element.description"
          :route="element.route"
          :image="element.image"
        />
      </template>
    </draggable>
  </div>
</template>
