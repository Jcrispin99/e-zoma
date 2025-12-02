<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Home, LayoutDashboard, Settings, LogOut } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';

const openDashboard = () => {
  console.log('Abrir dashboard');
};

const openSettings = () => {
  console.log('Abrir configuración');
};

const logout = () => {
  console.log('Cerrar sesión');
};
</script>

<template>
  <aside
    class="w-16 bg-slate-800 flex flex-col items-center py-4 space-y-6 fixed left-0 top-0 h-screen z-50"
  >
    <Link
      href="/"
      class="w-10 h-10 rounded flex items-center justify-center text-white font-bold text-xl"
    >
      IKD
    </Link>

    <nav class="flex-1 flex flex-col items-center space-y-4">
      <Link
        href="/web"
        :class="[
          'w-10 h-10 flex items-center justify-center rounded-lg transition-colors group relative',
          $page.url.startsWith('/web')
            ? 'bg-teal-500 text-white'
            : 'text-gray-400 hover:text-white hover:bg-slate-700',
        ]"
      >
        <Home class="w-6 h-6" />
        <span
          class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-sm rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
        >
          Home
        </span>
      </Link>

      <Link
        @click="openDashboard"
        :class="[
          'w-10 h-10 flex items-center justify-center rounded-lg transition-colors group relative',
          $page.url === '/'
            ? 'bg-teal-500 text-white'
            : 'text-gray-400 hover:text-white hover:bg-slate-700',
        ]"
      >
        <LayoutDashboard class="w-6 h-6" />
        <span
          class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-sm rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
        >
          Dashboard
        </span>
      </Link>

      <Link
        @click="openSettings"
        class="w-10 h-10 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-slate-700 transition-colors group relative"
      >
        <Settings class="w-6 h-6" />
        <span
          class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-sm rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
        >
          Ajustes
        </span>
      </Link>
    </nav>

    <Button
      @click="logout"
      variant="ghost-dark"
      size="icon"
      class="w-10 h-10 group relative"
    >
      <LogOut class="w-6 h-6" />
      <span
        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-sm rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
      >
        Salir
      </span>
    </Button>
  </aside>
</template>
