<script setup lang="ts">
import { computed } from 'vue';
import { Check } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    checked?: boolean;
    modelValue?: boolean | any[];
    value?: any;
  }>(),
  {
    checked: undefined,
    modelValue: undefined,
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void;
  (e: 'change', value: any): void;
}>();

const isChecked = computed(() => {
  if (props.checked !== undefined) return props.checked;
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.includes(props.value);
  }
  return props.modelValue;
});

const handleChange = () => {
  if (props.checked !== undefined) {
    emit('change', !props.checked);
    return;
  }

  if (Array.isArray(props.modelValue)) {
    const newValue = [...props.modelValue];
    if (isChecked.value) {
      const index = newValue.indexOf(props.value);
      if (index > -1) newValue.splice(index, 1);
    } else {
      newValue.push(props.value);
    }
    emit('update:modelValue', newValue);
  } else {
    emit('update:modelValue', !props.modelValue);
  }
  emit('change', !isChecked.value);
};
</script>

<template>
  <button
    type="button"
    @click.stop="handleChange"
    class="w-4 h-4 rounded border flex items-center justify-center transition-colors duration-200"
    :class="[
      isChecked
        ? 'bg-[#112e43] border-[#112e43]'
        : 'bg-white border-gray-500 hover:border-[#112e43]',
    ]"
  >
    <Check v-if="isChecked" class="w-2.5 h-2.5 text-white" stroke-width="3" />
  </button>
</template>
