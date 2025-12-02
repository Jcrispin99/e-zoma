<script setup lang="ts">
import { computed } from 'vue';
import Checkbox from './Checkbox.vue';

interface Header {
  key: string;
  label: string;
  class?: string;
}

interface Props {
  headers: Header[];
  items: any[];
  emptyMessage?: string;
  selectable?: boolean;
  modelValue?: any[];
  globalSelect?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'No hay datos disponibles',
  selectable: false,
  modelValue: () => [],
  globalSelect: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: any[]): void;
  (e: 'row-click', item: any): void;
  (e: 'header-select', value: boolean): void;
}>();

const allSelected = computed({
  get: () => {
    if (props.globalSelect) return true;
    return (
      props.items.length > 0 &&
      props.items.every((item) => props.modelValue.includes(item))
    );
  },
  set: (checked) => {
    if (props.globalSelect && !checked) {
      emit('header-select', false);
      emit('update:modelValue', []);
      return;
    }

    if (checked) {
      const newSelection = [...props.modelValue];
      props.items.forEach((item) => {
        if (!newSelection.includes(item)) {
          newSelection.push(item);
        }
      });
      emit('update:modelValue', newSelection);
    } else {
      const newSelection = props.modelValue.filter(
        (selectedItem) => !props.items.includes(selectedItem)
      );
      emit('update:modelValue', newSelection);
    }
  },
});

const isSelected = (item: any) => {
  return props.globalSelect || props.modelValue.includes(item);
};

const toggleSelection = (item: any) => {
  if (props.globalSelect) {
    emit('header-select', false);
    const newSelection = props.items.filter((i) => i !== item);
    emit('update:modelValue', newSelection);
    return;
  }

  const selected = [...props.modelValue];
  const index = selected.indexOf(item);

  if (index === -1) {
    selected.push(item);
  } else {
    selected.splice(index, 1);
  }

  emit('update:modelValue', selected);
};

const handleRowClick = (item: any) => {
  emit('row-click', item);
};
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y">
      <thead class="bg-gray-200">
        <tr>
          <th v-if="selectable" scope="col" class="px-6 py-3 text-left w-10">
            <Checkbox v-model="allSelected" />
          </th>
          <th
            v-for="header in headers"
            :key="header.key"
            scope="col"
            :class="[
              'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
              header.class,
            ]"
          >
            {{ header.label }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr
          v-for="(item, index) in items"
          :key="index"
          class="hover:bg-gray-100 transition-colors cursor-pointer"
          @click="handleRowClick(item)"
        >
          <td
            v-if="selectable"
            class="px-6 py-4 whitespace-nowrap w-10"
            @click.stop
          >
            <Checkbox
              :checked="isSelected(item)"
              @change="toggleSelection(item)"
            />
          </td>
          <td
            v-for="header in headers"
            :key="header.key"
            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
          >
            <slot
              :name="`cell-${header.key}`"
              :item="item"
              :value="item[header.key]"
            >
              {{ item[header.key] }}
            </slot>
          </td>
        </tr>
        <tr v-if="items.length === 0">
          <td
            :colspan="headers.length + (selectable ? 1 : 0)"
            class="px-6 py-8 text-center text-gray-500 text-sm"
          >
            {{ emptyMessage }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
