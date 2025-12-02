<script setup lang="ts">
import { computed } from 'vue';
import Spinner from './Spinner.vue';

interface Props {
  variant?:
  | 'primary'
  | 'secondary'
  | 'danger'
  | 'success'
  | 'warning'
  | 'info'
  | 'ghost'
  | 'ghost-dark'
  | 'danger-ghost';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  disabled?: boolean;
  loading?: boolean;
  type?: 'button' | 'submit' | 'reset';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
});

const classes = computed(() => {
  const base =
    'inline-flex items-center justify-center rounded-lg font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 disabled:opacity-50';

  const variants = {
    primary: 'bg-teal-500 text-white hover:bg-teal-600 disabled:hover:bg-teal-500 focus:ring-teal-500',
    secondary:
      'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    warning:
      'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
    info: 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-500',
    ghost:
      'text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:ring-gray-500',
    'ghost-dark':
      'text-gray-400 hover:text-white hover:bg-slate-700 focus:ring-slate-500',
    'danger-ghost': 'text-red-600 hover:bg-red-50 focus:ring-red-500',
  };

  const sizes = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
    icon: 'p-2',
  };

  return [base, variants[props.variant], sizes[props.size]].join(' ');
});
</script>

<template>
  <button :type="type" :class="classes" :disabled="disabled || loading">
    <Spinner v-if="loading" class="-ml-1 mr-2 h-4 w-4" />
    <slot />
  </button>
</template>
