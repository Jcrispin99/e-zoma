<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { salesNavigation, salesIcon } from '@/config/salesNavigation';
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
import { PlusIcon, Trash, Menu, Mail, CheckCircle, XCircle, RefreshCw } from 'lucide-vue-next';
import { Sale, Customer, Tax, Journal, SaleItem, VariantOption, FormItem } from '@/types/sales';
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
    sale?: Sale & { variants?: SaleItem[] };
    customers: Customer[];
    taxes?: Tax[];
    journals?: Journal[];
    products?: VariantOption[];
    quotes?: { id: number; label: string; customer_id: number }[];
}>();

const { notify } = useNotification();
const isEditing = computed(() => !!props.sale);
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

interface FormType {
    customer_id: number | string;
    journal_id: number | string;
    serie: string;
    correlative: string;
    date: string;
    observation: string;
    total: number;
    quote_id: number | string;
    items: FormItem[];
}

const form = useForm<FormType>({
    customer_id: props.sale?.customer_id || '',
    journal_id: (props.sale as any)?.journal_id || '',
    serie: (props.sale as any)?.serie || '',
    correlative: (props.sale as any)?.correlative || '',
    date: (props.sale as any)?.date ? new Date((props.sale as any).date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    observation: (props.sale as any)?.observation || '',
    total: (props.sale as any)?.total || 0,
    quote_id: (props.sale as any)?.quote_id || '',
    items: props.sale?.variants?.map((v: SaleItem) => {
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
            tax_inclusive: false,
            subtotal: Number(v.pivot?.subtotal || 0)
        };
    }) || []
});

const customerOptions = computed(() => {
    return props.customers.map(customer => ({
        value: customer.id,
        label: `${customer.name} (${customer.document_number})`
    }));
});

watch(() => form.quote_id, (newQuoteId) => {
    if (newQuoteId && !isEditing.value) {
        fetchQuoteDetails(newQuoteId);
    }
});

const fetchQuoteDetails = async (quoteId: number | string) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/finanzas/ventas/ordenes/${quoteId}/api-details`);
        const quote = response.data;

        form.customer_id = quote.customer_id;

        if (quote.variants) {
            form.items = quote.variants.map((v: any) => ({
                id: v.id,
                name: v.full_name || v.product?.name || v.name,
                quantity: Number(v.pivot?.quantity || 1),
                price: Number(v.pivot?.price || 0),
                tax_rate: Number(v.pivot?.tax_rate || 0),
                tax_id: '',
                tax_inclusive: false,
                subtotal: Number(v.pivot?.subtotal || 0)
            }));

            if (props.taxes) {
                form.items.forEach(item => {
                    const matchingTax = props.taxes?.find(t => Number(t.rate_percent) === Number(item.tax_rate));
                    if (matchingTax) {
                        item.tax_id = matchingTax.id;
                        item.tax_inclusive = Boolean(matchingTax.is_price_inclusive);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error fetching quote details:', error);
        notify('Error al cargar datos de la cotización', 'error');
    } finally {
        isLoading.value = false;
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
        label: `${journal.name} (${journal.code})`
    })) || [];
});

if (!isEditing.value && props.journals?.length && !form.journal_id) {
    const first = props.journals[0];
    form.journal_id = first.id;
}

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
        tax_inclusive: false,
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
                    ? 'Venta actualizada correctamente'
                    : 'Venta creada correctamente',
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

    if (isEditing.value && props.sale) {
        form.put(`/finanzas/ventas/ordenes/${props.sale.id}`, options);
    } else {
        form.post('/finanzas/ventas/ordenes', options);
    }
};

const handleCancel = () => {
    if (isEditing.value) {
        router.visit(location.pathname, {
            replace: true,
            only: ['sale'],
            preserveScroll: true,
            onSuccess: () => notify('Cambios descartados', 'info')
        });
    } else {
        form.reset();
        notify('Cambios descartados', 'info');
    }
};

const breadcrumbs = computed(() => [
    { label: 'Ventas', route: '/finanzas/ventas/ordenes' },
    { label: isEditing.value ? 'Editar' : 'Nuevo' }
]);

const isDirty = computed(() => form.isDirty);

const showActionsDropdown = ref(false);

const handlePost = () => {
    if (!props.sale) return;
    router.post(`/finanzas/ventas/ordenes/${props.sale.id}/publicar`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Cotización publicada', 'success');
            }
        },
        onError: () => notify('Error al publicar', 'error')
    });
    showActionsDropdown.value = false;
};

const handleCancelOrder = () => {
    if (!props.sale) return;

    confirmModalConfig.value = {
        title: 'Anular Venta',
        message: '¿Estás seguro que deseas anular esta venta? Esta acción no se puede deshacer y revertirá los movimientos de inventario.',
        confirmText: 'Sí, anular',
        variant: 'danger',
        action: processCancelOrder
    };
    showConfirmModal.value = true;
    showActionsDropdown.value = false;
};

const processCancelOrder = () => {
    if (!props.sale) return;
    isProcessingAction.value = true;

    router.post(`/finanzas/ventas/ordenes/${props.sale.id}/cancelar`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Venta anulada', 'success');
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
    if (!props.sale) return;
    router.post(`/finanzas/ventas/ordenes/${props.sale.id}/reabrir`, {}, {
        onSuccess: (page) => {
            const props = page.props as unknown as PageWithFlash;
            if (props.flash?.error) {
                notify(props.flash.error, 'error');
            } else {
                notify(props.flash?.success || 'Venta reabierta', 'success');
            }
        },
        onError: () => notify('Error al reabrir', 'error')
    });
    showActionsDropdown.value = false;
};

const handleMarkPaid = () => {
    if (!props.sale) return;
    router.post(`/finanzas/ventas/ordenes/${props.sale.id}/pagar`, {}, {
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
    if (!props.sale) return;

    confirmModalConfig.value = {
        title: 'Anular Pago',
        message: '¿Estás seguro que deseas anular el pago de esta venta? El estado volverá a pendiente de pago.',
        confirmText: 'Sí, anular pago',
        variant: 'warning',
        action: processMarkUnpaid
    };
    showConfirmModal.value = true;
    showActionsDropdown.value = false;
};

const processMarkUnpaid = () => {
    if (!props.sale) return;
    isProcessingAction.value = true;

    router.post(`/finanzas/ventas/ordenes/${props.sale.id}/anular-pago`, {}, {
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
    <ModuleLayout title="Ventas" :icon="salesIcon" :navigation-items="salesNavigation">
        <Form title="Ventas (Ordenes)" :subtitle="isEditing ? 'Editar' : 'Nuevo'" :loading="form.processing"
            @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty" :breadcrumbs="breadcrumbs">
            <template #header-actions>
                <div class="relative" v-if="isEditing">
                    <Button variant="secondary" @click="showActionsDropdown = !showActionsDropdown" type="button">
                        <Menu class="w-4 h-4" />
                    </Button>

                    <div v-if="showActionsDropdown"
                        class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                        <div class="py-1">
                            <template v-if="(sale as any)?.status === 'draft'">
                                <button @click="handlePost" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <CheckCircle class="w-4 h-4 text-emerald-500" />
                                    Publicar
                                </button>
                                <button @click="handleCancelOrder" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <XCircle class="w-4 h-4" />
                                    Cancelar
                                </button>
                            </template>

                            <template v-if="(sale as any)?.status === 'posted'">
                                <button @click="handleCancelOrder" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <XCircle class="w-4 h-4" />
                                    Anular
                                </button>
                            </template>

                            <template v-if="(sale as any)?.status === 'cancelled'">
                                <button @click="handleReopen" type="button"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <RefreshCw class="w-4 h-4 text-blue-500" />
                                    Reabrir
                                </button>
                            </template>

                            <div class="border-t border-gray-100 my-1" v-if="(sale as any)?.quote_id"></div>

                            <div class="border-t border-gray-100 my-1"></div>

                            <button @click="notify('Funcionalidad pendiente: Enviar por correo', 'info')" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <Mail class="w-4 h-4 text-gray-500" />
                                Enviar por correo
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template #top-left>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Cliente <span
                                class="text-red-500">*</span></Label>
                        <Input v-model="form.customer_id" :options="customerOptions" placeholder="Seleccione un cliente"
                            :error="form.errors.customer_id" :showSearchMore="false" :allowCustom="false" />
                    </div>
                    <div>
                        <Label class="text-sm font-bold text-gray-700 mb-1 block">Serie del Documento</Label>
                        <Input v-model="form.journal_id" :options="journalOptions" placeholder="Seleccione una serie"
                            :error="form.errors.serie" :disabled="isEditing" />
                    </div>
                </div>
            </template>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
