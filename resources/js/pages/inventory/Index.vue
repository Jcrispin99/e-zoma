<script setup lang="ts">
import ModuleLayout, {
  NavigationItem,
} from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';

const navigationItems: NavigationItem[] = [
  { label: 'Información general', href: '/finanzas/inventario' },
  {
    label: 'Productos',
    items: [
      { label: 'Productos', href: '/finanzas/inventario/productos' },
      { label: 'Variantes', href: '#' },
    ],
  },
  { label: 'Reportes', href: '#' },
  {
    label: 'Configuración',
    sections: [
      {
        title: 'Gestión del almacén',
        items: [{ label: 'Almacenes', href: '#' }],
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
</script>

<template>
  <ModuleLayout
    title="Inventario"
    :icon="inventarioIcon"
    :navigation-items="navigationItems"
  >
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            Panel de Inventario
          </h1>
          <p class="text-gray-600">
            Bienvenido al módulo de gestión de inventario.
          </p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
