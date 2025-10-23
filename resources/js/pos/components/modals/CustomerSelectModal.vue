<script setup>
import { ref, watch, reactive } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  // Opcional: IDs ya seleccionados para priorizarlos en el listado
  selectedIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'select']);

const activeTab = ref('search');

const search = ref('');
const customers = ref([]);
const loading = ref(false);
const error = ref(null);

const identities = ref([]);
const form = reactive({
  identity_id: '',
  document_number: '',
  name: '',
  address: '',
  email: '',
  phone: '',
});
const formErrors = ref({});
const saving = ref(false);

function getXsrfToken() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : null;
}

async function fetchCustomers() {
  loading.value = true;
  error.value = null;
  try {
    // Asegurar cookie CSRF para peticiones stateful
    let token = getXsrfToken();
    if (!token) {
      await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
      token = getXsrfToken();
    }

    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (props.selectedIds?.length)
      params.append('selected', props.selectedIds.join(','));

    const res = await fetch(`/api/customers?${params.toString()}`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(token ? { 'X-XSRF-TOKEN': token } : {}),
      },
      credentials: 'include',
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    customers.value = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
        ? data
        : [];
  } catch (e) {
    console.error(e);
    error.value = 'Error al cargar clientes';
  } finally {
    loading.value = false;
  }
}

async function fetchIdentities() {
  try {
    const res = await fetch('/api/identities', {
      headers: { Accept: 'application/json' },
      credentials: 'include',
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    identities.value = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
        ? data
        : [];
  } catch (e) {
    console.error(e);
  }
}

async function createCustomer() {
  formErrors.value = {};
  saving.value = true;
  try {
    let token = getXsrfToken();
    if (!token) {
      await fetch(`/sanctum/csrf-cookie`, { credentials: 'include' });
      token = getXsrfToken();
    }

    const res = await fetch('/api/customers/store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { 'X-XSRF-TOKEN': token } : {}),
      },
      credentials: 'include',
      body: JSON.stringify(form),
    });

    if (res.status === 422) {
      const data = await res.json();
      formErrors.value = data.errors || {};
      return;
    }
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const created = await res.json();
    emit('select', created);
    // Limpiar formulario y volver a búsqueda
    form.identity_id = '';
    form.document_number = '';
    form.name = '';
    form.address = '';
    form.email = '';
    form.phone = '';
    activeTab.value = 'search';
    await fetchCustomers();
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
}

function identityLabel(id) {
  const i = identities.value.find((x) => x.id === id);
  return i ? i.name : '';
}

let debounceTimer;
watch(search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchCustomers, 300);
});

watch(
  () => props.show,
  (v) => {
    if (v) {
      activeTab.value = 'search';
      fetchCustomers();
      fetchIdentities();
    }
  }
);
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
    <div
      class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm"
      @click.self="$emit('close')"
    ></div>

    <div
      class="relative bg-white w-full max-w-3xl mx-4 rounded-lg shadow-lg border"
    >
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">Seleccionar o crear cliente</h3>
        <button
          class="text-gray-500 hover:text-gray-700"
          @click="$emit('close')"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="px-4 pt-3">
        <div class="flex gap-2 border-b mb-3">
          <button
            :class="[
              'px-3 py-2',
              activeTab === 'search'
                ? 'border-b-2 border-blue-600 text-blue-600'
                : 'text-gray-600',
            ]"
            @click="activeTab = 'search'"
          >
            Buscar
          </button>
          <button
            :class="[
              'px-3 py-2',
              activeTab === 'new'
                ? 'border-b-2 border-blue-600 text-blue-600'
                : 'text-gray-600',
            ]"
            @click="activeTab = 'new'"
          >
            Crear cliente
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'search'" class="p-4 space-y-3">
        <div class="flex gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Buscar por nombre o documento..."
            class="flex-1 border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-500"
          />
          <button
            class="px-3 py-2 bg-gray-100 rounded border"
            @click="search = ''"
          >
            Limpiar
          </button>
        </div>

        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>

        <div v-if="loading" class="py-6 text-center text-gray-600">
          <i class="fa-solid fa-spinner animate-spin mr-2"></i> Cargando
          clientes...
        </div>

        <div v-else class="divide-y max-h-72 overflow-auto border rounded">
          <div v-if="!customers.length" class="p-4 text-center text-gray-500">
            No hay clientes que coincidan.
          </div>
          <div
            v-for="c in customers"
            :key="c.id"
            class="flex items-center justify-between p-3 hover:bg-gray-50"
          >
            <div class="flex flex-col">
              <span class="font-medium text-gray-800">{{ c.name }}</span>
              <span v-if="c.document_number" class="text-xs text-gray-500"
                >{{ identityLabel(c.identity_id) }}:
                {{ c.document_number }}</span
              >
            </div>
            <button
              class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700"
              @click="$emit('select', c)"
            >
              Seleccionar
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'new'" class="p-4 space-y-4">
        <form
          @submit.prevent="createCustomer"
          class="grid grid-cols-1 md:grid-cols-2 gap-3"
        >
          <div>
            <label class="block text-sm text-gray-700 mb-1"
              >Tipo de documento</label
            >
            <select
              v-model="form.identity_id"
              class="w-full border rounded px-3 py-2"
            >
              <option value="" disabled>Seleccione...</option>
              <option v-for="i in identities" :key="i.id" :value="i.id">
                {{ i.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1"
              >Número de documento</label
            >
            <input
              v-model="form.document_number"
              type="text"
              class="w-full border rounded px-3 py-2"
            />
            <div
              v-if="formErrors.document_number"
              class="text-red-600 text-xs mt-1"
            >
              {{ formErrors.document_number[0] }}
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-1"
              >Nombre o razón social</label
            >
            <input
              v-model="form.name"
              type="text"
              class="w-full border rounded px-3 py-2"
            />
            <div v-if="formErrors.name" class="text-red-600 text-xs mt-1">
              {{ formErrors.name[0] }}
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-1">Dirección</label>
            <input
              v-model="form.address"
              type="text"
              class="w-full border rounded px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full border rounded px-3 py-2"
            />
            <div v-if="formErrors.email" class="text-red-600 text-xs mt-1">
              {{ formErrors.email[0] }}
            </div>
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
            <input
              v-model="form.phone"
              type="text"
              class="w-full border rounded px-3 py-2"
            />
          </div>

          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="px-3 py-2 border rounded hover:bg-gray-100"
              @click="activeTab = 'search'"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            >
              <span v-if="saving"
                ><i class="fa-solid fa-spinner animate-spin mr-2"></i>
                Guardando...</span
              >
              <span v-else>Crear cliente</span>
            </button>
          </div>
        </form>
      </div>

      <div class="px-4 py-3 border-t bg-gray-50 flex justify-end gap-2">
        <button
          class="px-3 py-2 border rounded hover:bg-gray-100"
          @click="$emit('close')"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>
