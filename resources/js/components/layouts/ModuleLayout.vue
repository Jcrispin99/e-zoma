<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import MainLayout from '@/layouts/MainLayout.vue';

export interface DropdownItem {
  label: string;
  href: string;
}

export interface DropdownSection {
  title?: string;
  items: DropdownItem[];
}

export interface NavigationItem {
  label: string;
  href?: string;
  items?: DropdownItem[];
  sections?: DropdownSection[];
}

defineProps<{
  title: string;
  icon: string;
  backUrl?: string;
  navigationItems: NavigationItem[];
}>();

const activeDropdown = ref<string | null>(null);
const dropdownRefs = ref<Record<string, HTMLElement | null>>({});

function toggleDropdown(label: string) {
  if (activeDropdown.value === label) {
    activeDropdown.value = null;
  } else {
    activeDropdown.value = label;
  }
}

const closeDropdowns = (e: MouseEvent) => {
  if (activeDropdown.value) {
    const activeRef = dropdownRefs.value[activeDropdown.value];
    if (activeRef && !activeRef.contains(e.target as Node)) {
      activeDropdown.value = null;
    }
  }
};

const setDropdownRef = (el: any, label: string) => {
  if (el) {
    dropdownRefs.value[label] = el;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdowns);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns);
});
</script>

<template>
  <MainLayout>
    <template #brand>
      <Link :href="backUrl || '/web'" class="flex items-center gap-2 group">
      <div class="relative w-10 h-10 flex items-center justify-center">
        <img :src="icon" :alt="title"
          class="w-10 h-10 object-contain absolute transition-all duration-300 ease-in-out group-hover:opacity-0 group-hover:-translate-x-4" />
        <ChevronLeft
          class="w-8 h-8 text-white absolute transition-all duration-300 ease-in-out opacity-0 translate-x-3 group-hover:opacity-100 group-hover:translate-x-0 group-hover:-ml-2" />
      </div>
      <span
        class="text-white font-bold text-lg transition-transform duration-300 ease-in-out group-hover:-translate-x-2">
        {{ title }}
      </span>
      </Link>
    </template>

    <template #navigation>
      <nav
        class="flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0 items-start md:items-center w-full md:w-auto">
        <template v-for="item in navigationItems" :key="item.label">
          <Link v-if="!item.items && !item.sections" :href="item.href || '#'"
            class="text-gray-300 hover:text-white text-sm font-medium transition-colors">
          {{ item.label }}
          </Link>

          <div v-else class="relative w-full md:w-auto" :ref="(el) => setDropdownRef(el, item.label)">
            <button @click="toggleDropdown(item.label)"
              class="flex items-center text-gray-300 hover:text-white text-sm font-medium transition-colors focus:outline-none w-full md:w-auto justify-between md:justify-start"
              :class="{ 'text-white': activeDropdown === item.label }">
              {{ item.label }}
              <ChevronRight class="ml-1 w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-90': activeDropdown === item.label }" />
            </button>

            <Transition name="fade">
              <div v-if="activeDropdown === item.label"
                class="relative md:absolute md:border md:border-gray-300 mt-2 md:mt-5.5 bg-white/5 md:bg-white rounded-md md:shadow-lg py-1 z-50 left-0 w-full md:w-48">
                <template v-if="item.items">
                  <Link v-for="subItem in item.items" :key="subItem.label" :href="subItem.href"
                    class="block px-4 py-2 text-sm text-gray-300 md:text-gray-700 hover:bg-white/10 md:hover:bg-gray-100">
                  {{ subItem.label }}
                  </Link>
                </template>

                <template v-if="item.sections">
                  <template v-for="(section, index) in item.sections" :key="index">
                    <div v-if="index > 0" class="border-t border-gray-600 md:border-gray-100 my-1"></div>

                    <div v-if="section.title"
                      class="px-4 py-1 text-xs text-gray-400 md:text-gray-500 font-semibold uppercase tracking-wider">
                      {{ section.title }}
                    </div>

                    <Link v-for="subItem in section.items" :key="subItem.label" :href="subItem.href"
                      class="block px-4 py-2 text-sm text-gray-300 md:text-gray-700 hover:bg-white/10 md:hover:bg-gray-100">
                    {{ subItem.label }}
                    </Link>
                  </template>
                </template>
              </div>
            </Transition>
          </div>
        </template>
      </nav>
    </template>

    <slot />
  </MainLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
