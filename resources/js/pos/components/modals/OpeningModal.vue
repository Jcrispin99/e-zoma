<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  value: { type: String, default: "0" },
  error: { type: String, default: null },
});
const emit = defineEmits(["close", "confirm", "update:value"]);
</script>

<template>
  <div v-if="props.show" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" @click="emit('close')"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-6">
      <h2 class="text-lg font-semibold mb-2">Monto de apertura</h2>
      <p class="text-sm text-gray-600 mb-4">Ingresa el efectivo inicial en caja.</p>
      <input
        :value="props.value"
        @input="emit('update:value', $event.target.value)"
        type="number"
        min="0"
        step="0.01"
        class="w-full border rounded px-3 py-2 mb-2"
      />
      <p v-if="props.error" class="text-sm text-red-600 mb-2">{{ props.error }}</p>
      <div class="flex justify-end space-x-2">
        <button class="px-3 py-2 rounded bg-gray-200 text-gray-700" @click="emit('close')">Cancelar</button>
        <button class="px-3 py-2 rounded bg-purple-600 text-white hover:bg-purple-700" @click="emit('confirm')">Confirmar</button>
      </div>
    </div>
  </div>
</template>