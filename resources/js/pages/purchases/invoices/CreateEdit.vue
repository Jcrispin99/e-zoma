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
import { PlusIcon, Trash, Menu, FileText, Mail, CheckCircle, XCircle, DollarSign, RefreshCw } from 'lucide-vue-next';
import { PurchaseOrder, Supplier, Tax, Journal, PurchaseOrderItem, VariantOption, Purchase, FormItem } from '@/types/purchases';
import { AttributeValue } from '@/types/product';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import axios from 'axios';
import { PageProps } from '@inertiajs/core';

interface PageWithFlash extends PageProps {
    flash: {
        success?: string;
        error?: string;
    }
}

const props = defineProps<{
    purchase?: Purchase;
    suppliers: Supplier[];
    taxes?: Tax[];
    journals?: Journal[];
    products?: VariantOption[];
    purchaseOrders?: { id: number; label: string; supplier_id: number }[];
}>();

const { notify } = useNotification();
const isEditing = computed(() => !!props.purchase);
const showProductSearch = ref(false);
const selectedProductId = ref<number | string>('');
const extraProducts = ref<VariantOption[]>([]);
const searchedProducts = ref<VariantOption[]>([]);
const isSearching = ref(false);
const isLoading = ref(false);
const showConfirmModal = ref(false);
const isProcessingAction = ref(false);
const confirmModalConfig = ref({
    title: '',
    message: '',
    confirmText: 'Confirmar',
    variant: 'danger' as 'danger' | 'warning' | 'info',
    action: () => { }
});

const form = useForm({
    supplier_id: props.purchase?.supplier_id || '',
    journal_id: props.purchase?.journal_id || '',
    serie: props.purchase?.serie || '',
    correlative: props.purchase?.correlative || '',
    date: props.purchase?.date ? new Date(props.purchase.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    observation: props.purchase?.observation || '',
    total: props.purchase?.total || 0,
    purchase_order_id: props.purchase?.purchase_order_id || '',
    items: props.purchase?.variants?.map((v: PurchaseOrderItem) => {
        let taxId: number | string = '';
        if (props.taxes) {
            const matchingTax = props.taxes.find(t => Number(t.rate_percent) === Number(v.pivot?.tax_rate || 0));
            if (matchingTax) taxId = matchingTax.id;
        }

        return {
            id: v.id,
            name: v.product?.name + (v.attribute_values?.length ? ' - ' + v.attribute_values.map((av: AttributeValue) => av.value).join(', ') : ''),
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

const purchaseOrderOptions = computed(() => {
    return props.purchaseOrders?.map(po => ({
        value: po.id,
        label: po.label
    })) || [];
});

watch(() => form.purchase_order_id, (newPoId) => {
    if (newPoId && !isEditing.value) {
        fetchPurchaseOrderDetails(newPoId);
    }
});

const fetchPurchaseOrderDetails = async (poId: number | string) => {
    try {
        const response = await axios.get(`/finanzas/compras/ordenes/${poId}/api-details`);
        const po: PurchaseOrder = response.data;

        form.supplier_id = po.supplier_id;

        if (po.date) {
            form.date = po.date.split('T')[0];
        }

        form.observation = po.observation || '';
        form.correlative = po.correlative || '';

        if (!form.journal_id && props.journals && props.journals.length > 0) {
            form.journal_id = props.journals[0].id;
        }

        form.items = (po.variants?.map((v: PurchaseOrderItem) => {
            const quantity = Number(v.pivot?.quantity || 1);
            const price = Number(v.pivot?.price || 0);
            const taxRate = Number(v.pivot?.tax_rate || 0);

            let taxId: number | string = '';
            if (props.taxes) {
                const matchingTax = props.taxes.find(t => Number(t.rate_percent) === taxRate);
                if (matchingTax) taxId = matchingTax.id;
            }

            return {
                id: v.id,
                name: v.product?.name + (v.attribute_values?.length ? ' - ' + v.attribute_values.map((av: AttributeValue) => av.value).join(', ') : ''),
                quantity: quantity,
                price: price,
                tax_rate: taxRate,
                tax_id: taxId,
                subtotal: Number(v.pivot?.subtotal || 0)
            };
        }) || []) as FormItem[];

        notify('Datos de la orden cargados correctamente', 'success');
    } catch (error) {
        console.error(error);
        notify('Error al cargar detalles de la orden', 'error');
    }
};


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
                baseName += ' - ' + product.attribute_values.map((av: AttributeValue) => av.value).join(', ');
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

const handleProductSelect = (productId: number | string) => {
    selectedProductId.value = productId;
};

const handleModalSelect = (product: VariantOption) => {
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


watch(() => form.items, (items: FormItem[]) => {
    items.forEach((item: FormItem) => {
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
    return form.items.reduce((acc: number, item: FormItem) => {
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
    return form.items.reduce((acc: number, item: FormItem) => {
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

const addItem = (product: VariantOption) => {
    let name = product.full_name || product.name;

    if (!name || name === product.product?.name || name === 'undefined - undefined') {
        let baseName = product.product?.name || product.name || 'Producto';
        if (product.attribute_values && product.attribute_values.length > 0) {
            baseName += ' - ' + product.attribute_values.map((av: AttributeValue) => av.value).join(', ');
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
    } as FormItem;

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

const updateItemTax = (item: FormItem) => {
    const tax = props.taxes?.find(t => t.id == item.tax_id);
    item.tax_rate = tax ? Number(tax.rate_percent) : 0;
};

const handleSubmit = () => {
    const options = {
        onSuccess: () => {
            notify(
                isEditing.value
                    ? 'Compra actualizada correctamente'
                    : 'Compra creada correctamente',
                'success'
            );
        },
        onError: (errors: Record<string, string>) => {
            if (Object.keys(errors).length > 0) {
                const errorMessages = Object.values(errors).flat().join('\n');
                notify(`Errores de validación:\n${errorMessages}`, 'error');
            } else {
                notify('Completa los campos obligatorios', 'error');
            }
        },
    };

    if (isEditing.value && props.purchase) {
        form.put(`/finanzas/compras/facturas/${props.purchase.id}`, options);
    } else {
        form.post('/finanzas/compras/facturas', options);
    }
};

const handleCancel = () => {
    if (isEditing.value) {
        router.visit(location.pathname, {
            replace: true,
            only: ['purchase'],
            preserveScroll: true,
            onSuccess: () => notify('Cambios descartados', 'info')
        });
    } else {
        form.reset();
        notify('Cambios descartados', 'info');
    }
};

const breadcrumbs = computed(() => [
    { label: 'Compras', route: '/finanzas/compras/facturas' },
    { label: isEditing.value ? 'Editar' : 'Nuevo' }
]);

const isDirty = computed(() => form.isDirty);

const showActionsDropdown = ref(false);

const handleConfirm = () => {
    if (!props.purchase) return;
    router.post(`/finanzas/compras/facturas/${props.purchase.id}/contabilizar`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Compra contabilizada', 'success');
            }
        },
        onError: () => notify('Error al contabilizar', 'error')
    });
    showActionsDropdown.value = false;
};

const handleCancelOrder = () => {
    if (!props.purchase) return;

    confirmModalConfig.value = {
        title: 'Anular Compra',
        message: '¿Estás seguro que deseas anular esta compra? Esta acción no se puede deshacer y revertirá los movimientos de inventario.',
        confirmText: 'Sí, anular',
        variant: 'danger',
        action: processCancelOrder
    };
    showConfirmModal.value = true;
    showActionsDropdown.value = false;
};

const processCancelOrder = () => {
    if (!props.purchase) return;
    isProcessingAction.value = true;

    router.post(`/finanzas/compras/facturas/${props.purchase.id}/cancelar`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Compra anulada', 'success');
            }
            showConfirmModal.value = false;
        },
        onError: (err: Record<string, string>) => {
            notify(Object.values(err).flat().join('\n') || 'Error al anular', 'error');
        },
        onFinish: () => {
            isProcessingAction.value = false;
        }
    });
};

const handleReopen = () => {
    if (!props.purchase) return;
    router.post(`/finanzas/compras/facturas/${props.purchase.id}/reabrir`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Compra reabierta', 'success');
            }
        },
        onError: () => notify('Error al reabrir', 'error')
    });
    showActionsDropdown.value = false;
};

const handleMarkPaid = () => {
    if (!props.purchase) return;
    router.post(`/finanzas/compras/facturas/${props.purchase.id}/pagar`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Pago registrado', 'success');
            }
        },
        onError: () => notify('Error al registrar pago', 'error')
    });
    showActionsDropdown.value = false;
};

const handleMarkUnpaid = () => {
    if (!props.purchase) return;

    confirmModalConfig.value = {
        title: 'Anular Pago',
        message: '¿Estás seguro que deseas anular el pago de esta compra? El estado volverá a pendiente de pago.',
        confirmText: 'Sí, anular pago',
        variant: 'warning',
        action: processMarkUnpaid
    };
    showConfirmModal.value = true;
    showActionsDropdown.value = false;
};

const processMarkUnpaid = () => {
    if (!props.purchase) return;
    isProcessingAction.value = true;

    router.post(`/finanzas/compras/facturas/${props.purchase.id}/anular-pago`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Pago anulado', 'success');
            }
            showConfirmModal.value = false;
        },
        onError: () => notify('Error al anular pago', 'error'),
        onFinish: () => {
            isProcessingAction.value = false;
        }
    });
};
</script>

<template>
    <ModuleLayout title="Compras" :icon="purchasesIcon" :navigation-items="purchasesNavigation">
        <Form title="Compras (Facturas)" :subtitle="isEditing ? 'Editar' : 'Nuevo'" :loading="form.processing"
            @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty" :breadcrumbs="breadcrumbs">
            <template #header-actions>
                <div class="relative" v-if="isEditing">
                    <Button variant="secondary" @click="showActionsDropdown = !showActionsDropdown" type="button">
                        <Menu class="w-4 h-4" />
                    </Button>

                    <div v-if="showActionsDropdown"
                        class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                        <div class="py-1">
                            <template v-if="purchase?.status === 'draft'">
                                <button @click="handleConfirm" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <CheckCircle class="w-4 h-4 text-emerald-500" />
                                    Contabilizar
                                </button>
                                <button @click="handleCancelOrder" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <XCircle class="w-4 h-4" />
                                    Cancelar
                                </button>
                            </template>

                            <template v-if="purchase?.status === 'posted'">
                                <button v-if="purchase?.payment_status !== 'paid'" @click="handleMarkPaid" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <DollarSign class="w-4 h-4 text-emerald-500" />
                                    Registrar pago
                                </button>
                                <button v-else @click="handleMarkUnpaid" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <DollarSign class="w-4 h-4 text-amber-500" />
                                    Anular pago
                                </button>

                                <div class="border-t border-gray-100 my-1"></div>

                                <button @click="handleCancelOrder" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <XCircle class="w-4 h-4" />
                                    Anular
                                </button>
                            </template>

                            <template v-if="purchase?.status === 'cancelled'">
                                <button @click="handleReopen" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <RefreshCw class="w-4 h-4 text-blue-500" />
                                    Reabrir
                                </button>
                            </template>

                            <div class="border-t border-gray-100 my-1" v-if="purchase?.purchase_order_id"></div>
                            <button v-if="purchase?.purchase_order_id"
                                @click="router.visit(`/finanzas/compras/ordenes/${purchase.purchase_order_id}/editar`)"
                                type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <FileText class="w-4 h-4 text-indigo-500" />
                                Ver Orden de Compra
                            </button>

                            <div class="border-t border-gray-100 my-1"></div>

                            <button @click="notify('Funcionalidad pendiente: Enviar por correo', 'info')" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <Mail class="w-4 h-4 text-gray-500" />
                                Enviar por correo
                            </button>
                        </div>
                    </div>

                    <div v-if="showActionsDropdown" @click="showActionsDropdown = false"
                        class="fixed inset-0 z-40 bg-transparent"></div>
                </div>
            </template>

            <template #top-left>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Orden de Compra</Label>
                        <Input v-model="form.purchase_order_id" :options="purchaseOrderOptions"
                            placeholder="Seleccione una orden de compra" :disabled="isEditing" />
                    </div>

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

        <ConfirmationModal :show="showConfirmModal" :title="confirmModalConfig.title"
            :message="confirmModalConfig.message" :confirmText="confirmModalConfig.confirmText" cancelText="Cancelar"
            :variant="confirmModalConfig.variant" :loading="isProcessingAction" @close="showConfirmModal = false"
            @confirm="confirmModalConfig.action" />
    </ModuleLayout>
</template>
