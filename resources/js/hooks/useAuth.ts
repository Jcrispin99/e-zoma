import { computed, type ComputedRef } from 'vue';
import type { ApiResponse } from '../types/api';
import { useUserStore } from '../stores/user';
import { router } from '@inertiajs/vue3';
import type { User, LoginCredentials, RegisterData } from '../types';
import axios from 'axios';

interface UseAuthReturn {
  user: ComputedRef<User | null>;
  isAuthenticated: ComputedRef<boolean>;
  login: (
    credentials: LoginCredentials
  ) => Promise<{ success: boolean; error?: string }>;
  logout: () => Promise<void>;
  register: (
    data: RegisterData
  ) => Promise<{ success: boolean; error?: string }>;
}

export function useAuth(): UseAuthReturn {
  const userStore = useUserStore();

  const user = computed(() => userStore.user);
  const isAuthenticated = computed(() => userStore.isAuthenticated);

  const login = async (credentials: LoginCredentials) => {
    try {
      const response = await axios.post<
        ApiResponse<{ user: User; token: string }>
      >('/api/login', credentials);

      if (response.data.data) {
        localStorage.setItem('token', response.data.data.token);
        await userStore.fetchUser();
        router.visit('/');
        return { success: true };
      }

      return { success: false, error: 'Invalid response' };
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Login failed',
      };
    }
  };

  const logout = async () => {
    try {
      await axios.post('/api/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('token');
      userStore.logout();
      router.visit('/login');
    }
  };

  const register = async (data: RegisterData) => {
    try {
      const response = await axios.post<
        ApiResponse<{ user: User; token: string }>
      >('/api/register', data);

      if (response.data.data) {
        localStorage.setItem('token', response.data.data.token);
        await userStore.fetchUser();
        router.visit('/');
        return { success: true };
      }

      return { success: false, error: 'Invalid response' };
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Registration failed',
      };
    }
  };

  return {
    user,
    isAuthenticated,
    login,
    logout,
    register,
  };
}
