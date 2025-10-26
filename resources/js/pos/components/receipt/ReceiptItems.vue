<script setup>
const props = defineProps({
  items: { type: Array, default: () => [] },
});
</script>

<template>
  <div class="px-6 py-2">
    <table class="w-full text-xs">
      <thead>
        <tr class="border-b">
          <th class="text-left py-1">Producto</th>
          <th class="text-right py-1">Cant.</th>
          <th class="text-right py-1">Precio</th>
          <th class="text-right py-1">Importe</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(it, idx) in props.items" :key="idx" class="border-b">
          <td class="py-1 pr-2 truncate">
            {{ it.name || `Var #${it.variant_id || ''}` }}
          </td>
          <td class="py-1 text-right">
            {{ Number(it.quantity || 0).toFixed(2) }}
          </td>
          <td class="py-1 text-right">
            {{ Number(it.price || 0).toFixed(2) }}
          </td>
          <td class="py-1 text-right font-medium">
            {{ Number(it.subtotal || 0).toFixed(2) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
