<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';

const navigationItems = inventoryNavigation;
</script>

<template>
  <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
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
