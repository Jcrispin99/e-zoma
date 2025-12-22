<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { purchasesNavigation, purchasesIcon } from '@/config/purchasesNavigation';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Button from '@/components/ui/Button.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import GeneralSearchModal from '@/components/ui/GeneralSearchModal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useNotification } from '@/hooks/useNotification';
import { PlusIcon, Trash, Menu, CheckCircle, XCircle, FileText, Mail } from 'lucide-vue-next';
import { PurchaseOrder, Supplier, Tax, Journal, PurchaseOrderItem, VariantOption } from '@/types/purchases';
import axios from 'axios';

const props = defineProps<{
    purchaseOrder?: PurchaseOrder;
    suppliers: Supplier[];
    taxes?: Tax[];
    journals?: Journal[];
    products?: VariantOption[];
}>();

const { notify } = useNotification();
const isEditing = computed(() => !!props.purchaseOrder);
const showProductSearch = ref(false);
const selectedProductId = ref<number | string>('');
const extraProducts = ref<VariantOption[]>([]);
const searchedProducts = ref<VariantOption[]>([]);
const isSearching = ref(false);
const isLoading = ref(false);

const form = useForm({
    supplier_id: props.purchaseOrder?.supplier_id || '',
    journal_id: props.purchaseOrder?.journal_id || '',
    serie: props.purchaseOrder?.serie || '',
    correlative: props.purchaseOrder?.correlative || '',
    date: props.purchaseOrder?.date ? new Date(props.purchaseOrder.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    observation: props.purchaseOrder?.observation || '',
    total: props.purchaseOrder?.total || 0,
    items: props.purchaseOrder?.variants?.map((v: PurchaseOrderItem) => {
        let taxId: number | string = '';
        if (props.taxes) {
            const matchingTax = props.taxes.find(t => Number(t.rate_percent) === Number(v.pivot?.tax_rate || 0));
            if (matchingTax) taxId = matchingTax.id;
        }

        return {
            id: v.id,
            name: v.product?.name + (v.attribute_values?.length ? ' - ' + v.attribute_values.map((av: any) => av.value).join(', ') : ''),
            quantity: Number(v.pivot?.quantity || 1),
            price: Number(v.pivot?.price || 0),
            tax_rate: Number(v.pivot?.tax_rate || 0),
            tax_id: taxId,
            subtotal: Number(v.pivot?.subtotal || 0)
        };
    }) || []
});

const supplierOptions = computed(() => {
    return props.suppliers.map(supplier => ({
        value: supplier.id,
        label: `${supplier.name} (${supplier.document_number})`
    }));
});

const allProducts = computed(() => {
    if (isSearching.value) {
        return [...searchedProducts.value, ...extraProducts.value];
    }
    const base = props.products || [];
    return [...base, ...extraProducts.value];
});

const productOptions = computed(() => {
    const seen = new Set();
    const unique = allProducts.value.filter(p => {
        if (seen.has(p.id)) return false;
        seen.add(p.id);
        return true;
    });

    return unique.map(product => {
        let label = product.full_name || product.name;
        if (!label || label === product.product?.name || label === 'undefined - undefined') {
            let baseName = product.product?.name || product.name || 'Producto';
            if (product.attribute_values && product.attribute_values.length > 0) {
                baseName += ' - ' + product.attribute_values.map((av: any) => av.value).join(', ');
            } else if (product.sku) {
                baseName += ` (${product.sku})`;
            }
            label = baseName;
        }
        return {
            value: product.id,
            label: label
        };
    });
});

const handleProductSelect = (productId: any) => {
    selectedProductId.value = productId;
};

const handleModalSelect = (product: any) => {
    extraProducts.value.push(product);
    selectedProductId.value = product.id;
};

const confirmAddProduct = () => {
    if (!selectedProductId.value) return;
    const pid = Number(selectedProductId.value);
    const product = allProducts.value.find(p => p.id === pid);
    if (product) {
        addItem(product);
        selectedProductId.value = '';
    }
};

const handleSearch = async (query: string) => {
    if (!query) {
        isSearching.value = false;
        searchedProducts.value = [];
        return;
    }
    isSearching.value = true;
    isLoading.value = true;
    try {
        const response = await axios.post('/api/product/search', {
            search: query,
            page: 1,
            per_page: 50
        });
        searchedProducts.value = response.data.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        isLoading.value = false;
    }
};

const journalOptions = computed(() => {
    return props.journals?.map(journal => ({
        value: journal.id,
        label: journal.name || journal.serie || ''
    })) || [];
});

const taxOptions = computed(() => {
    return props.taxes?.map(tax => ({
        value: tax.id,
        label: `${tax.invoice_label || tax.name} ${tax.is_price_inclusive ? '(TTC)' : ''}`
    })) || [];
});


watch(() => form.items, (items: any[]) => {
    items.forEach((item: any) => {
        const q = Number(item.quantity || 0);
        const p = Number(item.price || 0);
        const tax = props.taxes?.find(t => t.id == item.tax_id);
        const rate = tax ? Number(tax.rate_percent) : 0;
        const inclusive = tax ? Boolean(tax.is_price_inclusive) : false;

        let subtotal = 0;
        const gross = q * p;

        if (inclusive && rate > 0) {
            subtotal = gross;
        } else {
            subtotal = q * p;
        }
        item.subtotal = subtotal;
    });
}, { deep: true });

const calculatedSubtotal = computed(() => {
    return form.items.reduce((acc: number, item: any) => {
        const q = Number(item.quantity || 0);
        const p = Number(item.price || 0);
        const tax = props.taxes?.find(t => t.id == item.tax_id);
        const rate = tax ? Number(tax.rate_percent) : 0;
        const inclusive = tax ? Boolean(tax.is_price_inclusive) : false;

        let base = q * p;
        if (inclusive && rate > 0) {
            base = base / (1 + (rate / 100));
        }
        return acc + base;
    }, 0);
});

const calculatedTaxTotal = computed(() => {
    return form.items.reduce((acc: number, item: any) => {
        const q = Number(item.quantity || 0);
        const p = Number(item.price || 0);
        const tax = props.taxes?.find(t => t.id == item.tax_id);
        const rate = tax ? Number(tax.rate_percent) : 0;
        const inclusive = tax ? Boolean(tax.is_price_inclusive) : false;

        const gross = q * p;
        if (inclusive && rate > 0) {
            const base = gross / (1 + (rate / 100));
            return acc + (gross - base);
        } else {
            return acc + (gross * (rate / 100));
        }
    }, 0);
});


const calculatedTotal = computed(() => {
    return calculatedSubtotal.value + calculatedTaxTotal.value;
});

watch(calculatedTotal, (newTotal) => {
    form.total = Number(newTotal.toFixed(2));
});

watch(() => form.journal_id, (newVal) => {
    if (newVal) {
        const journal = props.journals?.find(j => j.id === newVal);
        if (journal) {
            form.serie = journal.serie || '';
        }
    }
});

const addItem = (product: any) => {
    let name = product.full_name || product.name;

    if (!name || name === product.product?.name || name === 'undefined - undefined') {
        let baseName = product.product?.name || product.name || 'Producto';
        if (product.attribute_values && product.attribute_values.length > 0) {
            baseName += ' - ' + product.attribute_values.map((av: any) => av.value).join(', ');
        } else if (product.sku) {
            baseName += ` (${product.sku})`;
        }
        name = baseName;
    }

    const newItem = {
        id: product.id,
        name: name,
        quantity: 1,
        price: Number(product.price || 0),
        tax_id: props.taxes?.[0]?.id || '',
        tax_rate: 0,
        subtotal: 0
    } as any;

    if (newItem.tax_id && props.taxes) {
        const matchingTax = props.taxes.find(t => t.id === newItem.tax_id);
        if (matchingTax) {
            newItem.tax_rate = Number(matchingTax.rate_percent);
        }
    }

    form.items.push(newItem);
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const updateItemTax = (item: any) => {
    const tax = props.taxes?.find(t => t.id == item.tax_id);
    item.tax_rate = tax ? Number(tax.rate_percent) : 0;
};

const handleSubmit = () => {
    const options = {
        onSuccess: () => {
            notify(
                isEditing.value
                    ? 'Orden de compra actualizada correctamente'
                    : 'Orden de compra creada correctamente',
                'success'
            );
        },
        onError: (errors: any) => {
            if (Object.keys(errors).length > 0) {
                const errorMessages = Object.values(errors).flat().join('\n');
                notify(`Errores de validación:\n${errorMessages}`, 'error');
            } else {
                notify('Completa los campos obligatorios', 'error');
            }
        },
    };

    if (isEditing.value && props.purchaseOrder) {
        form.put(`/finanzas/compras/ordenes/${props.purchaseOrder.id}`, options);
    } else {
        form.post('/finanzas/compras/ordenes', options);
    }
};

const handleCancel = () => {
    if (isEditing.value) {
        router.visit(location.pathname, {
            replace: true,
            only: ['purchaseOrder'],
            preserveScroll: true,
            onSuccess: () => notify('Cambios descartados', 'info')
        });
    } else {
        form.reset();
        notify('Cambios descartados', 'info');
    }
};

const breadcrumbs = computed(() => [
    { label: 'Ordenes de Compra', route: '/finanzas/compras/ordenes' },
    { label: isEditing.value ? 'Editar' : 'Nuevo' }
]);

const isDirty = computed(() => form.isDirty);

const showActionsDropdown = ref(false);

const handleConfirm = () => {
    showActionsDropdown.value = false;
    if (!props.purchaseOrder) return;
    router.post(`/finanzas/compras/ordenes/${props.purchaseOrder.id}/confirm`, {}, {
        onSuccess: () => notify('Orden confirmada correctamente', 'success'),
        onError: () => notify('Error al confirmar la orden', 'error')
    });
};

const handleCancelOrder = () => {
    showActionsDropdown.value = false;
    if (!props.purchaseOrder) return;
    if (!confirm('¿Estás seguro de cancelar esta orden? Esta acción no se puede deshacer.')) return;

    router.post(`/finanzas/compras/ordenes/${props.purchaseOrder.id}/cancel`, {}, {
        onSuccess: () => notify('Orden cancelada correctamente', 'success'),
        onError: () => notify('Error al cancelar la orden', 'error')
    });
};
</script>

<template>
    <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
        <Form title="Ordenes de Compra" :subtitle="isEditing ? 'Editar' : 'Nuevo'" :loading="form.processing"
            @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty" :breadcrumbs="breadcrumbs">

            <template #header-actions>
                <div class="relative" v-if="isEditing">
                    <Button variant="secondary" @click="showActionsDropdown = !showActionsDropdown" type="button">
                        <Menu class="w-4 h-4" />
                    </Button>

                    <div v-if="showActionsDropdown"
                        class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                        <div class="py-1">
                            <button v-if="purchaseOrder?.status === 'draft'" @click="handleConfirm" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <CheckCircle class="w-4 h-4 text-green-500" />
                                Confirmar Orden
                            </button>

                            <button v-if="purchaseOrder?.status === 'confirmed'"
                                @click="notify('Funcionalidad pendiente: Enviar por Correo', 'info')" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <Mail class="w-4 h-4 text-blue-500" />
                                Enviar por Correo (OC)
                            </button>

                            <button v-if="purchaseOrder?.status === 'confirmed'"
                                @click="notify('Funcionalidad pendiente: Ver PDF', 'info')" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <FileText class="w-4 h-4 text-gray-500" />
                                Ver PDF
                            </button>

                            <div class="border-t border-gray-100 my-1" v-if="purchaseOrder?.status !== 'cancelled'">
                            </div>

                            <button v-if="purchaseOrder?.status !== 'cancelled'" @click="handleCancelOrder"
                                type="button"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <XCircle class="w-4 h-4" />
                                Cancelar Orden
                            </button>
                        </div>
                    </div>

                    <div v-if="showActionsDropdown" @click="showActionsDropdown = false"
                        class="fixed inset-0 z-40 bg-transparent"></div>
                </div>
            </template>

            <template #top-left>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Proveedor <span
                                class="text-red-500">*</span></Label>
                        <Input v-model="form.supplier_id" :options="supplierOptions"
                            placeholder="Seleccione un proveedor" :error="form.errors.supplier_id"
                            :showSearchMore="false" :allowCustom="false" />
                    </div>
                    <div>
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Serie del Documento</Label>
                        <Input v-model="form.journal_id" :options="journalOptions" placeholder="Seleccione una serie"
                            :error="form.errors.serie" :disabled="isEditing" />
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label class="block text-sm font-medium text-gray-700">Correlativo</Label>
                        <Input v-model="form.correlative" :error="form.errors.correlative" placeholder="Autogenerado"
                            readonly disabled class="bg-gray-50" />
                    </div>

                    <div>
                        <Label class="block text-sm font-medium text-gray-700">Fecha</Label>
                        <DatePicker v-model="form.date" :error="form.errors.date" />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Producto</Label>
                        <Input v-model="selectedProductId" :options="productOptions"
                            placeholder="Seleccione un producto" show-search-more disable-local-filter
                            @search-more="showProductSearch = true" @update:model-value="handleProductSelect"
                            @search="handleSearch" :loading="isLoading" />
                    </div>
                    <Button type="button" @click="confirmAddProduct" variant="primary" :disabled="!selectedProductId">
                        <PlusIcon class="w-4 h-4 mr-1" />
                        Agregar
                    </Button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Cantidad</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Precio</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">
                                    Impuesto</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Subtotal</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(item, index) in form.items" :key="index">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ item.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Input type="number" v-model="item.quantity" min="0.1" step="0.1"
                                        inputClass="text-right" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Input type="number" v-model="item.price" min="0" step="0.01"
                                        inputClass="text-right" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Input v-if="taxes" v-model="item.tax_id" :options="taxOptions" :allowCustom="false"
                                        @update:modelValue="updateItemTax(item)" />
                                    <span v-else class="text-xs text-gray-500">Sin impuestos</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    S/ {{ (Number(item.subtotal) || 0).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Button type="button" @click="removeItem(index)" variant="danger-ghost" size="icon">
                                        <Trash class="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 text-sm">
                                    No hay productos agregados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <Label class="block text-sm font-medium text-gray-700">Observaciones</Label>
                        <Textarea v-model="form.observation" :error="form.errors.observation"
                            placeholder="Observaciones adicionales" :rows="3" />
                    </div>

                    <div class="space-y-2 bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal:</span>
                            <span>S/ {{ calculatedSubtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Impuestos:</span>
                            <span>S/ {{ calculatedTaxTotal.toFixed(2) }}</span>
                        </div>
                        <div
                            class="border-t border-gray-200 pt-2 flex justify-between text-base font-bold text-gray-900">
                            <span>Total:</span>
                            <span>S/ {{ calculatedTotal.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Form>

        <GeneralSearchModal v-model="showProductSearch" type="product" @select="handleModalSelect" />
    </ModuleLayout>
</template>
