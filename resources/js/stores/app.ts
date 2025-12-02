import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { AppNotification, AppNotificationType } from '../types';

interface NotificationPayload {
  message: string;
  type: AppNotificationType;
  duration?: number;
  closable?: boolean;
}

export const useAppStore = defineStore('app', () => {
  // State
  const sidebarCollapsed = ref(false);
  const theme = ref<'light' | 'dark'>('light');
  const notifications = ref<AppNotification[]>([]);
  const loading = ref(false);

  // Actions
  const toggleSidebar = (): void => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
  };

  const setSidebarCollapsed = (value: boolean): void => {
    sidebarCollapsed.value = value;
  };

  const setTheme = (newTheme: 'light' | 'dark'): void => {
    theme.value = newTheme;
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
  };

  const toggleTheme = (): void => {
    setTheme(theme.value === 'light' ? 'dark' : 'light');
  };

  const addNotification = (payload: NotificationPayload): void => {
    const notification: AppNotification = {
      id: Date.now(),
      title: '',
      message: payload.message,
      type: payload.type,
      read: false,
      created_at: new Date().toISOString(),
    };
    notifications.value.unshift(notification);
    if (payload.duration && payload.duration > 0) {
      setTimeout(() => {
        removeNotification(notification.id);
      }, payload.duration);
    }
  };

  const removeNotification = (id: number): void => {
    const index = notifications.value.findIndex((n) => n.id === id);
    if (index !== -1) {
      notifications.value.splice(index, 1);
    }
  };

  const markNotificationAsRead = (id: number): void => {
    const notification = notifications.value.find((n) => n.id === id);
    if (notification) {
      notification.read = true;
    }
  };

  const markAllNotificationsAsRead = (): void => {
    notifications.value.forEach((n) => {
      n.read = true;
    });
  };

  const clearNotifications = (): void => {
    notifications.value = [];
  };

  const setLoading = (value: boolean): void => {
    loading.value = value;
  };

  // Initialize theme from localStorage
  const initTheme = (): void => {
    const savedTheme = localStorage.getItem('theme') as 'light' | 'dark' | null;
    if (savedTheme) {
      setTheme(savedTheme);
    }
  };

  return {
    // State
    sidebarCollapsed,
    theme,
    notifications,
    loading,
    // Actions
    toggleSidebar,
    setSidebarCollapsed,
    setTheme,
    toggleTheme,
    addNotification,
    removeNotification,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    clearNotifications,
    setLoading,
    initTheme,
  };
});
