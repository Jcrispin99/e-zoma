<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  item: any;
  type?: 'product' | 'variant';
}>(), {
  type: 'product'
});

const isProduct = computed(() => props.type === 'product');

const stock = computed(() => {
  if (isProduct.value) {
    return (
      props.item.variants?.reduce(
        (acc: number, v: any) => acc + (Number(v.stock) || 0),
        0
      ) || 0
    );
  }
  return props.item.stock || 0;
});

const formattedPrice = computed(() => {
  return Number(props.item.price).toFixed(2);
});

const displayName = computed(() => {
  if (isProduct.value) {
    return props.item.name;
  }
  return props.item.product?.name + ' - ' + (props.item.attribute_values?.map((av: any) => av.value).join(', ') || 'Sin atributos');
});
</script>

<template>
  <div
    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-4 flex gap-4 relative group">
    <div class="w-20 h-20 bg-gray-100 rounded-md flex-shrink-0 flex items-center justify-center">
      <img v-if="item.image" :src="item.image" :alt="displayName" class="w-full h-full object-cover rounded-md" />
      <div v-else class="text-gray-300">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </div>

    <div class="flex-1 min-w-0">
      <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 mb-1" :title="displayName">
        {{ displayName }}
      </h3>

      <p v-if="isProduct" class="text-xs font-medium text-gray-600 mb-2">
        {{ (item.variants?.length || 0) === 0 ? 1 : item.variants.length }} {{ ((item.variants?.length || 0)
          === 0 || item.variants.length === 1) ? 'Variante' : 'Variantes' }}
      </p>
      <p v-else class="text-xs font-medium text-gray-600 mb-2">
        SKU: {{ item.sku }}
      </p>

      <div class="space-y-1">
        <p class="text-xs text-gray-500">
          Precio:
          <span class="font-medium text-gray-900">S/ {{ formattedPrice }}</span>
        </p>
        <p class="text-xs text-gray-500">
          {{ isProduct ? 'A la mano:' : 'Stock:' }}
          <span class="font-medium text-gray-900">{{ stock }} Unidades</span>
        </p>
      </div>
    </div>
  </div>
</template>
