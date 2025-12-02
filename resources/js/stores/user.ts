import { defineStore } from 'pinia';
import { ref, computed, type Ref, type ComputedRef } from 'vue';
import type { User } from '../types';
import { api } from '../lib/api';

interface UserState {
  user: Ref<User | null>;
  isAuthenticated: ComputedRef<boolean>;
  loading: Ref<boolean>;
  error: Ref<string | null>;
}

export const useUserStore = defineStore('user', () => {
  // State
  const user = ref<User | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const isAuthenticated = computed(() => !!user.value);
  const isAdmin = computed(() => user.value?.role === 'admin');
  const isManager = computed(() => user.value?.role === 'manager');

  // Actions
  const fetchUser = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get<User>('/user');
      user.value = response.data.data;
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch user';
      user.value = null;
    } finally {
      loading.value = false;
    }
  };

  const updateUser = async (data: Partial<User>): Promise<boolean> => {
    if (!user.value) return false;

    loading.value = true;
    error.value = null;

    try {
      const response = await api.put<User>(`/users/${user.value.id}`, data);
      user.value = response.data.data;
      return true;
    } catch (err: any) {
      error.value = err.message || 'Failed to update user';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const logout = (): void => {
    user.value = null;
  };

  return {
    // State
    user,
    loading,
    error,
    // Getters
    isAuthenticated,
    isAdmin,
    isManager,
    // Actions
    fetchUser,
    updateUser,
    logout,
  };
});
