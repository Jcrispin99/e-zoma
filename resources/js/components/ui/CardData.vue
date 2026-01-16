<script setup lang="ts">
import { computed } from 'vue';
import { Shapes, Warehouse, Package, Truck, FileText } from 'lucide-vue-next';

defineOptions({
  name: 'CardData'
});

const props = withDefaults(defineProps<{
  item?: any;
  items?: any[];
  type?: 'product' | 'variant' | 'warehouse' | 'category' | 'supplier' | 'purchase_order' | 'order';
  emptyMessage?: string;
}>(), {
  type: 'product',
  emptyMessage: 'No hay datos disponibles'
});

const isGrid = computed(() => Array.isArray(props.items));

const isProduct = computed(() => props.type === 'product');
const isWarehouse = computed(() => props.type === 'warehouse');
const isCategory = computed(() => props.type === 'category');
const isSupplier = computed(() => props.type === 'supplier');
const isPurchaseOrder = computed(() => props.type === 'purchase_order');
const isOrder = computed(() => props.type === 'order');

const stock = computed(() => {
  if (isProduct.value && props.item) {
    return (
      props.item.variants?.reduce(
        (acc: number, v: any) => acc + (Number(v.stock) || 0),
        0
      ) || 0
    );
  }
  return props.item?.stock || 0;
});

const formattedPrice = computed(() => {
  return props.item ? Number(props.item.price).toFixed(2) : '0.00';
});

const displayName = computed(() => {
  if (!props.item) return '';
  if (isProduct.value || isWarehouse.value || isCategory.value || isSupplier.value) {
    return props.item.name;
  }
  if (isPurchaseOrder.value || isOrder.value) {
    return `${props.item.serie}-${props.item.correlative}`;
  }
  return props.item.product?.name + ' - ' + (props.item.attribute_values?.map((av: any) => av.value).join(', ') || 'Sin atributos');
});

const getStatusColor = (status: string) => {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-800';
    case 'confirmed': return 'bg-teal-100 text-teal-800';
    case 'approved': return 'bg-blue-100 text-blue-800';
    case 'posted': return 'bg-teal-100 text-teal-800';
    case 'sent': return 'bg-yellow-100 text-yellow-800';
    case 'received': return 'bg-green-100 text-green-800';
    case 'cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    'draft': 'Borrador',
    'confirmed': 'Confirmada',
    'approved': 'Aprobada',
    'posted': 'Publicada',
    'sent': 'Enviada',
    'received': 'Recibida',
    'cancelled': 'Cancelada'
  };
  return labels[status] || status;
};

const emit = defineEmits<{
  (e: 'click', item: any): void;
}>();

const handleClick = (item: any) => {
  emit('click', item);
};
</script>

<template>
  <div v-if="isGrid">
    <div class="bg-gray-300 h-[0.5px]"></div>

    <div v-if="items && items.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-7 mt-4">
      <CardData v-for="singleItem in items" :key="singleItem.id" :item="singleItem" :type="type"
        @click="handleClick(singleItem)" class="cursor-pointer" :class="$attrs.class" />
    </div>

    <div v-else class="text-center py-8 text-gray-500 text-sm bg-white">
      {{ emptyMessage }}
    </div>
  </div>

  <div v-else
    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-4 flex gap-4 relative group"
    @click="handleClick(item)">
    <div class="w-20 h-20 bg-gray-100 rounded-md flex-shrink-0 flex items-center justify-center">
      <img v-if="item.image" :src="item.image" :alt="displayName" class="w-full h-full object-cover rounded-md" />
      <div v-else class="text-gray-300">
        <Warehouse v-if="isWarehouse" class="w-8 h-8 text-gray-400" />
        <Shapes v-else-if="isCategory" class="w-8 h-8 text-gray-400" />
        <Truck v-else-if="isSupplier" class="w-8 h-8 text-gray-400" />
        <FileText v-else-if="isPurchaseOrder" class="w-8 h-8 text-gray-400" />
        <Package v-else class="w-8 h-8 text-gray-400" />
      </div>
    </div>

    <div class="flex-1 min-w-0">
      <div v-if="isPurchaseOrder" class="flex justify-between items-start mb-1">
        <h3 class="text-sm font-semibold text-gray-900 line-clamp-2" :title="displayName">
          {{ displayName }}
        </h3>
        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" :class="getStatusColor(item.status)">
          {{ getStatusLabel(item.status) }}
        </span>
      </div>
      <h3 v-else class="text-sm font-semibold text-gray-900 line-clamp-2 mb-1" :title="displayName">
        {{ displayName }}
      </h3>

      <p v-if="isProduct" class="text-xs font-medium text-gray-600 mb-2">
        {{ (item.variants?.length || 0) === 0 ? 1 : item.variants.length }} {{ ((item.variants?.length || 0)
          === 0 || item.variants.length === 1) ? 'Variante' : 'Variantes' }}
      </p>
      <p v-else-if="isWarehouse" class="text-xs font-medium text-gray-600 mb-2">
        {{ item.location || 'Sin ubicación' }}
      </p>
      <p v-else-if="isCategory" class="text-xs font-medium text-gray-600 mb-2">
        {{ item.description || 'Sin descripción' }}
      </p>
      <p v-else-if="isSupplier" class="text-xs font-medium text-gray-600 mb-2">
        {{ item.identity?.name || 'Doc' }}: {{ item.document_number }}
      </p>
      <p v-else-if="isPurchaseOrder" class="text-xs font-medium text-gray-600 mb-1">
        {{ new Date(item.created_at).toLocaleDateString() }}
      </p>
      <p v-else class="text-xs font-medium text-gray-600 mb-2">
        SKU: {{ item.sku }}
      </p>

      <div v-if="!isWarehouse && !isCategory && !isSupplier && !isPurchaseOrder" class="space-y-1">
        <p class="text-xs text-gray-500">
          Precio:
          <span class="font-medium text-gray-900">S/ {{ formattedPrice }}</span>
        </p>
        <p class="text-xs text-gray-500">
          {{ isProduct ? 'A la mano:' : 'Stock:' }}
          <span class="font-medium text-gray-900">{{ stock }} Unidades</span>
        </p>
      </div>

      <div v-if="isSupplier" class="space-y-1">
        <p class="text-xs text-gray-500 truncate" :title="item.email">
          <span class="font-medium text-gray-900">{{ item.email || 'Sin email' }}</span>
        </p>
        <p class="text-xs text-gray-500">
          <span class="font-medium text-gray-900">{{ item.phone || 'Sin teléfono' }}</span>
        </p>
      </div>

      <div v-if="isPurchaseOrder" class="space-y-1 mt-2">
        <p class="text-xs text-gray-500">
          <span class="font-medium text-gray-900">{{ item.supplier?.name || 'Sin proveedor' }}</span>
        </p>
        <div class="flex justify-between items-end mt-2">
          <span class="text-xs text-gray-500">Total</span>
          <span class="text-sm font-bold text-gray-900">S/ {{ Number(item.total || 0).toFixed(2) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
