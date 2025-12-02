<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import {
  CircleHelp,
  Bell,
  User,
  LogOut,
  User as UserIcon,
  Menu,
  X,
} from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import { useAuth } from '@/hooks/useAuth';
import { Link } from '@inertiajs/vue3';

interface Notification {
  id: number;
  title: string;
  time: string;
}

const { logout } = useAuth();

const showNotifications = ref<boolean>(false);
const showUserMenu = ref<boolean>(false);
const showMobileMenu = ref<boolean>(false);
const hasNotifications = ref<boolean>(true);
const isMobile = ref<boolean>(false);

const notificationBtnRef = ref<HTMLElement | null>(null);
const userBtnRef = ref<HTMLElement | null>(null);
const mobileMenuBtnRef = ref<HTMLElement | null>(null);

const notifications = ref<Notification[]>([
  {
    id: 1,
    title: 'Stock bajo en producto XYZ',
    time: 'Hace 5 minutos',
  },
  {
    id: 2,
    title: 'Nueva venta registrada',
    time: 'Hace 15 minutos',
  },
]);

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value) {
    showUserMenu.value = false;
    showMobileMenu.value = false;
  }
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
  if (showUserMenu.value) {
    showNotifications.value = false;
    showMobileMenu.value = false;
  }
};

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value;
  if (showMobileMenu.value) {
    showNotifications.value = false;
    showUserMenu.value = false;
  }
};

const toggleHelpMenu = () => {
  console.log('Toggle help menu');
};

const handleLogout = async () => {
  await logout();
};

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768;
  if (!isMobile.value) {
    showMobileMenu.value = false;
  }
};

const handleClickOutside = (e: Event) => {
  const target = e.target as HTMLElement;

  if (
    notificationBtnRef.value?.contains(target) ||
    userBtnRef.value?.contains(target) ||
    mobileMenuBtnRef.value?.contains(target)
  ) {
    return;
  }

  if (!target.closest('.dropdown-menu')) {
    showNotifications.value = false;
    showUserMenu.value = false;
    if (!target.closest('.mobile-menu-content') && showMobileMenu.value) {
      showMobileMenu.value = false;
    }
  }
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
  window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <nav
    class="bg-[#112e43] shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 right-0 left-0 z-40"
  >
    <div class="flex items-center gap-4 md:gap-8">
      <div class="flex items-center">
        <slot name="brand">
          <span class="text-white font-bold text-2xl">Koodi</span>
        </slot>
      </div>

      <div v-if="!isMobile" class="hidden md:flex items-center">
        <slot name="navigation" />
      </div>
    </div>

    <div class="flex items-center gap-1 md:gap-2">
      <Button
        variant="ghost"
        size="icon"
        @click="toggleHelpMenu"
        title="Ayuda"
        class="text-white hover:text-white hover:bg-white/10"
      >
        <CircleHelp class="w-6 h-6" />
      </Button>

      <div class="relative" ref="notificationBtnRef">
        <Button
          variant="ghost"
          size="icon"
          @click="toggleNotifications"
          title="Notificaciones"
          class="relative text-white hover:text-white hover:bg-white/10"
        >
          <Bell class="w-6 h-6" />
          <span
            v-if="hasNotifications"
            class="absolute top-2.5 right-2.5 w-1.5 h-1.5 bg-red-500 rounded-full ring-2 ring-white"
          ></span>
        </Button>

        <Transition name="fade">
          <div
            v-if="showNotifications"
            class="dropdown-menu absolute top-full right-0 mt-3 w-[270px] bg-white rounded-lg shadow-lg border border-gray-300 overflow-hidden z-50"
          >
            <div class="p-4 border-b border-gray-300">
              <h3 class="font-semibold text-gray-900">Notificaciones</h3>
            </div>
            <div class="max-h-96 overflow-y-auto">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                class="p-4 hover:bg-gray-50 border-b border-gray-100 cursor-pointer transition-colors"
              >
                <p class="text-sm text-gray-900">{{ notification.title }}</p>
                <p class="text-xs text-gray-500 mt-1">
                  {{ notification.time }}
                </p>
              </div>
              <div
                v-if="notifications.length === 0"
                class="p-8 text-center text-gray-500"
              >
                No hay notificaciones
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <div class="relative" ref="userBtnRef">
        <Button
          variant="ghost"
          size="icon"
          @click="toggleUserMenu"
          title="Perfil de usuario"
          class="text-white hover:text-white hover:bg-white/10"
        >
          <User class="w-6 h-6" />
        </Button>

        <Transition name="fade">
          <div
            v-if="showUserMenu"
            class="dropdown-menu absolute top-full right-0 mt-3 w-48 bg-white rounded-lg shadow-lg border border-gray-300 overflow-hidden z-50"
          >
            <div class="py-1">
              <Link
                href="/profile"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
              >
                <UserIcon class="w-4 h-4 mr-2" />
                Mi Perfil
              </Link>
              <button
                @click="handleLogout"
                class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
              >
                <LogOut class="w-4 h-4 mr-2" />
                Salir
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <div class="md:hidden" ref="mobileMenuBtnRef">
        <Button
          variant="ghost"
          size="icon"
          @click="toggleMobileMenu"
          class="text-white hover:text-white hover:bg-white/10"
        >
          <Menu v-if="!showMobileMenu" class="w-6 h-6 pointer-events-none" />
          <X v-else class="w-6 h-6 pointer-events-none" />
        </Button>
      </div>
    </div>

    <Transition name="slide-fade">
      <div
        v-if="isMobile && showMobileMenu"
        class="mobile-menu-content absolute top-16 left-0 right-0 bg-[#112e43] border-t border-gray-700 shadow-xl p-4 z-30"
      >
        <div class="flex flex-col space-y-4">
          <slot name="navigation" />
        </div>
      </div>
    </Transition>
  </nav>
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

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
