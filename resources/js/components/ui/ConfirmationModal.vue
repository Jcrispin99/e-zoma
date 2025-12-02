<script setup lang="ts">
import Modal from './Modal.vue';
import Button from './Button.vue';
import { AlertTriangle } from 'lucide-vue-next';

interface Props {
  show: boolean;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'warning' | 'info';
  loading?: boolean;
}

withDefaults(defineProps<Props>(), {
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  variant: 'danger',
  loading: false,
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <Modal :show="show" maxWidth="sm" @close="$emit('close')">
    <div class="bg-white">
      <div class="sm:flex sm:items-start">
        <div
          class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
          :class="{
            'bg-red-100': variant === 'danger',
            'bg-yellow-100': variant === 'warning',
            'bg-blue-100': variant === 'info',
          }"
        >
          <AlertTriangle
            class="h-6 w-6"
            :class="{
              'text-red-600': variant === 'danger',
              'text-yellow-600': variant === 'warning',
              'text-blue-600': variant === 'info',
            }"
          />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ title }}
          </h3>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              {{ message }}
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 pt-3 flex justify-end gap-2">
      <Button
        :variant="variant === 'info' ? 'primary' : variant"
        :loading="loading"
        @click="$emit('confirm')"
      >
        {{ confirmText }}
      </Button>
      <Button variant="secondary" @click="$emit('close')" :disabled="loading">
        {{ cancelText }}
      </Button>
    </div>
  </Modal>
</template>
