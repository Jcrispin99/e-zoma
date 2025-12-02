<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import BaseModal from './BaseModal.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxPoints: {
    type: Number,
    default: 0,
  },
  initialPoints: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['close', 'spend']);

const points = ref(props.initialPoints);
const errorMessage = ref('');
const pointsInput = ref(null);

const isValid = computed(() => {
  return !errorMessage.value && points.value > 0;
});

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      points.value = props.initialPoints;
      errorMessage.value = '';
      nextTick(() => {
        pointsInput.value?.focus();
      });
    }
  }
);

function validatePoints() {
  if (points.value > props.maxPoints) {
    errorMessage.value = `No puedes usar más de ${props.maxPoints} puntos.`;
  } else if (points.value < 0) {
    errorMessage.value = 'El número de puntos no puede ser negativo.';
  } else {
    errorMessage.value = '';
  }
}

function handleClose() {
  emit('close');
}

function handleSpend() {
  if (isValid.value) {
    emit('spend', points.value);
    handleClose();
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    title="Canjear Puntos de Lealtad"
    @close="handleClose"
  >
    <div class="p-4">
      <p class="mb-4">
        Tienes
        <span class="font-bold">{{ maxPoints }}</span>
        puntos disponibles.
      </p>
      <div class="flex flex-col">
        <label
          for="points-to-spend"
          class="mb-2 text-sm font-medium text-gray-700"
        >
          Puntos a utilizar
        </label>
        <input
          id="points-to-spend"
          ref="pointsInput"
          v-model.number="points"
          type="number"
          class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          :max="maxPoints"
          min="0"
          @input="validatePoints"
        />
        <p v-if="errorMessage" class="mt-2 text-sm text-red-600">
          {{ errorMessage }}
        </p>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end space-x-2">
        <button type="button" class="btn btn-secondary" @click="handleClose">
          Cancelar
        </button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="!isValid"
          @click="handleSpend"
        >
          Usar Puntos
        </button>
      </div>
    </template>
  </BaseModal>
</template>
