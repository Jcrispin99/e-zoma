import { ref, type Ref } from 'vue';
import { api } from '../lib/api';
import type { ApiResponse, ApiError } from '../types';

interface UseApiOptions<T> {
  onSuccess?: (data: T) => void;
  onError?: (error: ApiError) => void;
  immediate?: boolean;
}

interface UseApiReturn<T> {
  data: Ref<T | null>;
  loading: Ref<boolean>;
  error: Ref<ApiError | null>;
  execute: () => Promise<T | null>;
}

export function useApi<T = any>(
  apiCall: () => Promise<ApiResponse<T>>,
  options: UseApiOptions<T> = {}
): UseApiReturn<T> {
  const data = ref<T | null>(null) as Ref<T | null>;
  const loading = ref(false);
  const error = ref<ApiError | null>(null);

  const execute = async (): Promise<T | null> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await apiCall();
      data.value = response.data;

      if (options.onSuccess) {
        options.onSuccess(response.data);
      }

      return response.data;
    } catch (err: any) {
      const apiError: ApiError = {
        message:
          err.response?.data?.message || err.message || 'An error occurred',
        errors: err.response?.data?.errors,
        status: err.response?.status,
      };

      error.value = apiError;

      if (options.onError) {
        options.onError(apiError);
      }

      return null;
    } finally {
      loading.value = false;
    }
  };

  if (options.immediate) {
    execute();
  }

  return {
    data,
    loading,
    error,
    execute,
  };
}
