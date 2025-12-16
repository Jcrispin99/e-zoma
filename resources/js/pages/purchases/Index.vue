<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { purchasesNavigation, purchasesIcon } from '@/config/purchasesNavigation';
</script>

<template>
  <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
    <div class="space-y-6 mx-9">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Panel de Compras</h1>
          <p class="text-gray-500 mt-1">Gestión de compras y proveedores</p>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">Bienvenido al módulo de compras. Seleccione una opción del menú para comenzar.</p>
      </div>
    </div>
  </ModuleLayout>
</template>
