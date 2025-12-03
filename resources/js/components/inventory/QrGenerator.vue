<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import Button from '@/components/ui/Button.vue';
import Label from '@/components/ui/Label.vue';
import Input from '@/components/ui/Input.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Table from '@/components/ui/Table.vue';
import { RefreshCw, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface QrItem {
    id: number;
    product_name: string;
    description: string;
    sku: string;
    barcode: string;
    price: number;
    qty: number;
}

const props = defineProps<{
    items: any[];
    styles: any[];
}>();

const localItems = ref<QrItem[]>([]);
const selectedStyleId = ref<number | undefined>(undefined);
const qrSize = ref(200);
const labelWidth = ref(50);
const labelHeight = ref(50);
const showProductName = ref(true);
const showDescription = ref(true);
const showPrice = ref(true);
const showSku = ref(true);
const showBarcodeText = ref(true);
const globalQty = ref(1);

const currentPage = ref(1);
const itemsPerPage = 10;

const totalPages = computed(() => Math.ceil(localItems.value.length / itemsPerPage));

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return localItems.value.slice(start, end);
});

const paginationInfo = computed(() => {
    const total = localItems.value.length;
    const from = total === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1;
    const to = Math.min(currentPage.value * itemsPerPage, total);
    return { from, to, total };
});

const tableHeaders = [
    { key: 'product_name', label: 'Producto' },
    { key: 'description', label: 'Variante' },
    { key: 'sku', label: 'Referencia Interna / Codigo de Barras' },
    { key: 'qty', label: 'Cantidad', class: 'w-32' },
];

watch(() => props.items, (newItems) => {
    localItems.value = newItems.map(item => ({
        id: item.id,
        product_name: item.product?.name || item.name || '',
        description: item.attribute_values?.map((av: any) => av.value).join(' / ') || item.description || '',
        sku: item.sku,
        barcode: item.barcode,
        price: item.price,
        qty: 1
    }));
    currentPage.value = 1;
}, { immediate: true });

onMounted(() => {
    const defaultStyle = props.styles.find(s => s.is_default) || props.styles[0];
    if (defaultStyle) {
        applyStyle(defaultStyle);
    }
});

const applyStyle = (style: any) => {
    selectedStyleId.value = style.id;
    qrSize.value = style.qr_size || 200;
    labelWidth.value = style.label_width || 50;
    labelHeight.value = style.label_height || 50;
    showProductName.value = style.show_product_name;
    showDescription.value = style.show_description;
    showPrice.value = style.show_price;
    showSku.value = style.show_sku;
    showBarcodeText.value = style.show_barcode_text;
};

const styleOptions = computed(() => {
    return props.styles.map(s => ({
        value: s.id,
        label: s.name
    }));
});

watch(selectedStyleId, (newId) => {
    if (!newId) return;
    const style = props.styles.find(s => s.id === newId);
    if (style) {
        applyStyle(style);
    }
});

const getQrValue = (item: QrItem) => {
    return item.barcode || item.sku || String(item.id);
};

const handlePrint = () => {
    window.print();
};

const applyGlobalQty = () => {
    localItems.value.forEach(item => {
        item.qty = globalQty.value;
    });
};

defineExpose({ handlePrint });
</script>

<template>
    <div>
        <div class="mb-8 print:hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-4">
                    <h3 class="font-medium text-gray-700">Estilo</h3>
                    <div>
                        <Label>Seleccionar Estilo</Label>
                        <Input v-model="selectedStyleId" :options="styleOptions" placeholder="Seleccionar estilo" />
                    </div>

                    <div class="p-4 bg-gray-50 rounded-md border border-gray-200">
                        <Label class="mb-2 block">Cantidad Global</Label>
                        <div class="flex gap-2">
                            <Input v-model="globalQty" type="number" min="0" class="w-24" />
                            <Button @click="applyGlobalQty" variant="secondary" size="sm" title="Aplicar a todos">
                                <RefreshCw class="w-4 h-4" />
                            </Button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Aplica esta cantidad a todos los items.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-medium text-gray-700">Dimensiones (mm)</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Ancho Etiqueta</Label>
                            <Input v-model="labelWidth" type="number" />
                        </div>
                        <div>
                            <Label>Alto Etiqueta</Label>
                            <Input v-model="labelHeight" type="number" />
                        </div>
                        <div>
                            <Label>Tamaño QR (px)</Label>
                            <Input v-model="qrSize" type="number" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-medium text-gray-700">Mostrar</h3>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <Checkbox v-model="showProductName" id="showProductName" />
                            <label for="showProductName" class="ml-2 text-sm text-gray-600">Nombre Producto</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model="showDescription" id="showDescription" />
                            <label for="showDescription" class="ml-2 text-sm text-gray-600">Descripción
                                (Atributos)</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model="showPrice" id="showPrice" />
                            <label for="showPrice" class="ml-2 text-sm text-gray-600">Precio</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model="showSku" id="showSku" />
                            <label for="showSku" class="ml-2 text-sm text-gray-600">Referencia Interna</label>
                        </div>
                        <div class="flex items-center">
                            <Checkbox v-model="showBarcodeText" id="showBarcodeText" />
                            <label for="showBarcodeText" class="ml-2 text-sm text-gray-600">Código de Barras
                                (Texto)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                    <h3 class="font-medium text-gray-700">Items a Imprimir</h3>

                    <div class="flex items-center gap-4" v-if="localItems.length > 0">
                        <span class="text-sm text-gray-600 font-medium">
                            {{ paginationInfo.from }}-{{ paginationInfo.to }} / {{ paginationInfo.total }}
                        </span>
                        <div class="flex items-center bg-white rounded-lg border border-gray-200 p-1">
                            <button :disabled="currentPage === 1" @click="currentPage--"
                                class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                                :class="currentPage === 1 ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                                <ChevronLeft class="w-5 h-5" />
                            </button>
                            <div class="w-[1px] h-4 bg-gray-200 mx-0.5"></div>
                            <button :disabled="currentPage === totalPages" @click="currentPage++"
                                class="p-1.5 rounded-md transition-colors disabled:opacity-50"
                                :class="currentPage === totalPages ? 'text-gray-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                                <ChevronRight class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <Table :headers="tableHeaders" :items="paginatedItems">
                    <template #cell-product_name="{ item }">
                        <span class="text-sm font-medium text-gray-900">{{ item.product_name }}</span>
                    </template>

                    <template #cell-description="{ item }">
                        <span class="text-sm text-gray-500">{{ item.description }}</span>
                    </template>

                    <template #cell-sku="{ item }">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-500">{{ item.sku }}</span>
                            <span class="text-xs text-gray-400">{{ item.barcode }}</span>
                        </div>
                    </template>

                    <template #cell-qty="{ item }">
                        <Input v-model="item.qty" type="number" min="0" class="w-20" />
                    </template>
                </Table>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-8 print:border-0 print:pt-0">
            <div class="flex flex-wrap gap-1 items-center justify-center" id="print-area">
                <template v-for="item in localItems" :key="item.id">
                    <div v-for="n in Number(item.qty)" :key="`${item.id}-${n}`"
                        class="border border-gray-200 bg-white overflow-hidden relative flex items-center justify-center p-1 break-inside-avoid"
                        :class="[labelWidth > labelHeight ? 'flex-row gap-1' : 'flex-col text-center']" :style="{
                            width: `${labelWidth}mm`,
                            height: `${labelHeight}mm`,
                        }">

                        <div class="pl-6 py-5 pr-2" :class="[labelWidth > labelHeight ? 'flex-shrink-0' : 'mb-1 mt-3']">
                            <QrcodeVue :value="getQrValue(item)" :size="Number(qrSize)" level="M" render-as="svg" />
                        </div>

                        <div
                            :class="[labelWidth > labelHeight ? 'flex-1 flex flex-col justify-between h-full min-w-0 text-left py-5 pr-6' : 'w-full mb-9 text-center justify-center items-center']">
                            <div>
                                <div v-if="showProductName"
                                    class="text-[10px] font-bold leading-none mb-0.5 w-full truncate">
                                    {{ item.product_name }}
                                </div>

                                <div v-if="showDescription" class="text-[9px] leading-none mb-0.5 w-full truncate">
                                    {{ item.description }}
                                </div>
                            </div>

                            <div :class="{ 'mt-0.5': !(labelWidth > labelHeight) }">
                                <div v-if="showPrice" class="text-[10px] font-bold leading-none"
                                    :class="{ 'text-right': labelWidth > labelHeight, 'mt-0.5': !(labelWidth > labelHeight) }">
                                    S/ {{ Number(item.price).toFixed(2) }}
                                </div>

                                <div v-if="showSku" class="text-[8px] text-gray-500 leading-none mt-0.5"
                                    :class="{ 'text-right': labelWidth > labelHeight }">
                                    SKU: {{ item.sku }}
                                </div>

                                <div v-if="showBarcodeText" class="text-[8px] font-mono leading-none mt-0.5"
                                    :class="{ 'text-right': labelWidth > labelHeight }">
                                    BC: {{ item.barcode }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
    }

    body>*:not(#app) {
        display: none;
    }
}
</style>
