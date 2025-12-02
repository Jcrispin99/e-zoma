<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  product: any;
}>();

const stock = computed(() => {
  return (
    props.product.variants?.reduce(
      (acc: number, v: any) => acc + (Number(v.stock) || 0),
      0
    ) || 0
  );
});

const formattedPrice = computed(() => {
  return Number(props.product.price).toFixed(2);
});
</script>

<template>
  <div
    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-4 flex gap-4 relative group">
    <div class="w-20 h-20 bg-gray-100 rounded-md flex-shrink-0 flex items-center justify-center">
      <img v-if="product.image" :src="product.image" :alt="product.name"
        class="w-full h-full object-cover rounded-md" />
      <div v-else class="text-gray-300">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </div>

    <div class="flex-1 min-w-0">
      <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 mb-1" :title="product.name">
        {{ product.name }}
      </h3>

      <p class="text-xs font-medium text-gray-600 mb-2">
        {{ (product.variants?.length || 0) === 0 ? 1 : product.variants.length }} {{ ((product.variants?.length || 0)
          === 0 || product.variants.length === 1) ? 'Variante' : 'Variantes' }}
      </p>

      <div class="space-y-1">
        <p class="text-xs text-gray-500">
          Precio:
          <span class="font-medium text-gray-900">S/ {{ formattedPrice }}</span>
        </p>
        <p class="text-xs text-gray-500">
          A la mano:
          <span class="font-medium text-gray-900">{{ stock }} Unidades</span>
        </p>
      </div>
    </div>
  </div>
</template>
