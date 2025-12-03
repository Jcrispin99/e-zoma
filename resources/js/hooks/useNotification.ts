import { toast } from 'vue-sonner';
import { ref, computed, type Ref, type ComputedRef } from 'vue';

interface NotificationOptions {
  duration?: number;
  closable?: boolean;
  description?: string;
}

interface UseNotificationReturn {
  notify: (
    message: string,
    type?: 'success' | 'error' | 'warning' | 'info',
    options?: NotificationOptions
  ) => void;
  success: (message: string, options?: NotificationOptions) => void;
  error: (message: string, options?: NotificationOptions) => void;
  warning: (message: string, options?: NotificationOptions) => void;
  info: (message: string, options?: NotificationOptions) => void;
}

export function useNotification(): UseNotificationReturn {
  const notify = (
    message: string,
    type: 'success' | 'error' | 'warning' | 'info' = 'info',
    options: NotificationOptions = {}
  ) => {
    const toastOptions = {
      duration: options.duration,
      description: options.description,
    };

    switch (type) {
      case 'success':
        toast.success(message, toastOptions);
        break;
      case 'error':
        toast.error(message, toastOptions);
        break;
      case 'warning':
        toast.warning(message, toastOptions);
        break;
      case 'info':
        toast.info(message, toastOptions);
        break;
      default:
        toast(message, toastOptions);
    }
  };

  const success = (message: string, options?: NotificationOptions) => {
    notify(message, 'success', options);
  };

  const error = (message: string, options?: NotificationOptions) => {
    notify(message, 'error', options);
  };

  const warning = (message: string, options?: NotificationOptions) => {
    notify(message, 'warning', options);
  };

  const info = (message: string, options?: NotificationOptions) => {
    notify(message, 'info', options);
  };

  return {
    notify,
    success,
    error,
    warning,
    info,
  };
}

interface UseTableOptions<T> {
  data: T[];
  perPage?: number;
}

interface UseTableReturn<T> {
  currentPage: Ref<number>;
  perPage: Ref<number>;
  searchQuery: Ref<string>;
  sortKey: Ref<string>;
  sortOrder: Ref<'asc' | 'desc'>;
  paginatedData: ComputedRef<T[]>;
  totalPages: ComputedRef<number>;
  goToPage: (page: number) => void;
  nextPage: () => void;
  prevPage: () => void;
  sort: (key: string) => void;
}

export function useTable<T extends Record<string, any>>(
  options: UseTableOptions<T>
): UseTableReturn<T> {
  const currentPage = ref(1);
  const perPage = ref(options.perPage || 10);
  const searchQuery = ref('');
  const sortKey = ref('');
  const sortOrder = ref<'asc' | 'desc'>('asc');

  const filteredData = computed(() => {
    let data = [...options.data];

    if (searchQuery.value) {
      data = data.filter((item) =>
        Object.values(item).some((value) =>
          String(value).toLowerCase().includes(searchQuery.value.toLowerCase())
        )
      );
    }

    if (sortKey.value) {
      data.sort((a, b) => {
        const aVal = a[sortKey.value];
        const bVal = b[sortKey.value];

        if (sortOrder.value === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    }

    return data;
  });

  const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredData.value.slice(start, end);
  });

  const totalPages = computed(() => {
    return Math.ceil(filteredData.value.length / perPage.value);
  });

  const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
    }
  };

  const nextPage = () => {
    goToPage(currentPage.value + 1);
  };

  const prevPage = () => {
    goToPage(currentPage.value - 1);
  };

  const sort = (key: string) => {
    if (sortKey.value === key) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortKey.value = key;
      sortOrder.value = 'asc';
    }
  };

  return {
    currentPage,
    perPage,
    searchQuery,
    sortKey,
    sortOrder,
    paginatedData,
    totalPages,
    goToPage,
    nextPage,
    prevPage,
    sort,
  };
}
