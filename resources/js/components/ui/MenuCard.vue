<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{
  title: string;
  description?: string;
  icon?: string;
  image?: string;
  route: string;
}>();
</script>

<template>
  <Link :href="route" class="group block w-full max-w-sm mx-auto">
    <div
      class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 flex items-center justify-between group-hover:-translate-y-1"
    >
      <div class="flex items-center gap-4">
        <div
          class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center flex-shrink-0"
        >
          <img
            v-if="image"
            :src="image"
            :alt="title"
            class="w-13 h-13 object-contain"
          />
          <i
            v-else
            :class="[
              'fas',
              `fa-${icon}`,
              'text-gray-400 group-hover:text-blue-500 text-xl transition-colors',
            ]"
          ></i>
        </div>

        <div class="flex flex-col text-left">
          <span class="font-semibold text-gray-900 text-base">{{ title }}</span>
          <div>
            <span v-if="description" class="text-sm text-gray-500 block pt-1">{{
              description
            }}</span>
          </div>
        </div>
      </div>

      <ChevronRight
        class="w-5 h-5 text-gray-300 group-hover:text-gray-400 transition-colors"
      />
    </div>
  </Link>
</template>
