<script setup>
import { computed } from 'vue';
const props = defineProps({
  company: {
    type: Object,
    default: () => ({
      name: 'Empresa name',
      trade_name: null,
      document_number: null,
      address: null,
      city: null,
      department: null,
      district: null,
      email: null,
      phone: null,
      logo: null,
      identity: null,
      slogan: null,
      policies: null,
    }),
  },
  sessionId: { type: [String, Number], required: true },
  type: { type: String, default: 'receipt' },
  serie: { type: String, default: '' },
  correlative: { type: [String, Number], default: '' },
  date: { type: String, default: '' },
  customer: {
    type: Object,
    default: () => ({
      name: 'Consumidor Final',
      document_number: null,
      identity: null,
    }),
  },
  isOffline: { type: Boolean, default: false },
  journalName: { type: String, default: '' },
  serieCode: { type: String, default: '' },
  posName: { type: String, default: '' },
  sellerName: { type: String, default: '' },
  condition: { type: String, default: 'Contado' },
});

const companyName = computed(
  () => props.company?.trade_name || props.company?.name || 'E-Zoma'
);
const logoSrc = computed(
  () => props.company?.logo || '/storage/images/images.png'
);
const companyDocLabel = computed(() => {
  const explicit = props.company?.identity?.name;
  if (explicit) return explicit;
  const num = String(props.company?.document_number || '').trim();
  if (num.length === 11) return 'RUC';
  if (num.length === 8) return 'DNI';
  return 'Documento';
});
const voucherLabel = computed(() =>
  props.type === 'invoice' ? 'Factura' : 'Boleta'
);
const formattedDate = computed(() => {
  try {
    return new Date(props.date || Date.now()).toLocaleString();
  } catch {
    return String(props.date || '');
  }
});
const customerDocLabel = computed(() => {
  const explicit = props.customer?.identity?.name;
  if (explicit) return explicit;
  const num = String(props.customer?.document_number || '').trim();
  if (num.length === 11) return 'RUC';
  if (num.length === 8) return 'DNI';
  return 'Documento';
});
</script>

<template>
  <div class="px-6 py-4 border-b space-y-3">
    <!-- Encabezado centrado -->
    <div class="text-center space-y-1">
      <img
        :src="logoSrc"
        alt="Logo"
        class="w-28 h-28 rounded object-cover mx-auto"
      />
      <div class="text-lg font-semibold">{{ companyName }}</div>
      <div v-if="props.company?.document_number" class="text-xs text-gray-700">
        {{ companyDocLabel }}: {{ props.company.document_number }}
      </div>
      <div v-if="props.company?.address" class="text-xs text-gray-500">
        {{ props.company.address }}
      </div>
      <!-- Ciudad - Departamento - Distrito en líneas separadas -->
      <div class="text-xs text-gray-500">
        <div v-if="props.company?.city">
          {{ props.company.city }} - {{ props.company.department }} -
          {{ props.company.district }}
        </div>
      </div>

      <!-- Salto de línea antes del journal -->
      <div class="text-sm font-medium mt-2">
        <template v-if="journalName">
          {{ journalName }}
        </template>
        <template v-else>
          {{ voucherLabel }}
        </template>
      </div>

      <!-- Serie - Correlativo actual -->
      <div class="text-xl font-bold">
        {{ props.serie || '—' }}
        <span class="text-gray-400 mx-1">-</span>
        {{ props.correlative || (props.isOffline ? 'Provisional' : '—') }}
      </div>

      <!-- Slogan de la empresa -->
      <div v-if="props.company?.slogan" class="text-xs text-gray-700 italic">
        {{ props.company.slogan }}
      </div>

      <div class="text-xs text-gray-600">Sesión POS #{{ props.sessionId }}</div>
    </div>

    <!-- Datos de la transacción -->
    <div class="text-sm space-y-1 mt-2">
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Fecha de emisión:</span>
        <span class="text-gray-700">{{ formattedDate }}</span>
      </div>
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Punto de venta:</span>
        <span class="text-gray-700">{{ posName || '—' }}</span>
      </div>

      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Vendedor:</span>
        <span class="text-gray-700">{{ sellerName || '—' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Condición:</span>
        <span class="text-gray-700">{{ condition || 'Contado' }}</span>
      </div>
    </div>
    <!-- Línea divisoria -->
    <div class="border-t border-gray-200 my-2"></div>

    <!-- Información del cliente -->
    <div class="text-sm space-y-1">
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Cliente:</span>
        <span class="text-gray-700">{{ props.customer?.name || '—' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">{{ customerDocLabel }}:</span>
        <span class="text-gray-700">{{
          props.customer?.document_number || '—'
        }}</span>
      </div>
      <div class="flex justify-between">
        <span class="font-semibold text-gray-800">Dirección:</span>
        <span class="text-gray-700"></span>
      </div>
    </div>

    <div v-if="props.isOffline" class="text-xs text-orange-600">
      Modo offline: documento provisional. Se asignará numeración oficial al
      sincronizar.
    </div>
  </div>
</template>
