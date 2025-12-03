<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { CloudUpload } from 'lucide-vue-next';
import ProductImageGallery from '@/components/inventory/ProductImageGallery.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useNotification } from '@/hooks/useNotification';
import type { AdditionalImage } from '@/types/product';
import { inventoryNavigation } from '@/config/inventoryNavigation';

const props = defineProps<{
    variant: any;
}>();

const { notify } = useNotification();

const form = useForm({
    sku: props.variant.sku || '',
    barcode: props.variant.barcode || '',
    price: props.variant.price || '',
    stock: props.variant.stock || 0,
    image: null as File | null,
    _method: 'PUT',
});

const imagePreview = ref<string | null>(props.variant.image || null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handleImageClick = () => {
    fileInputRef.value?.click();
};

const handleImageChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const additionalImages = ref<AdditionalImage[]>([]);
const initialAdditionalImages = ref<AdditionalImage[]>([]);

watch(() => props.variant, (variant) => {
    if (variant?.images && variant.images.length > 0) {
        additionalImages.value = variant.images.map((img: any) => ({
            id: img.id,
            url: `/storage/${img.path}`,
        }));
        initialAdditionalImages.value = JSON.parse(JSON.stringify(additionalImages.value));
    }
}, { immediate: true });

const tabs = [
    { id: 'general', label: 'Información General' },
    { id: 'images', label: 'Imágenes' },
];

const handleSubmit = () => {
    form.transform((data) => {
        const newImages = additionalImages.value
            .filter(img => img.file)
            .map(img => img.file);

        return {
            ...data,
            additionalImages: newImages,
            existingImageIds: additionalImages.value
                .filter(img => img.id)
                .map(img => img.id),
        };
    });

    form.post(`/finanzas/inventario/variantes/${props.variant.id}`, {
        forceFormData: true,
        onSuccess: () => {
            notify('Variante actualizada correctamente', 'success');
        },
        onError: () => {
            notify('Error al actualizar la variante', 'error');
        },
    });
};

const handleCancel = () => {
    form.reset();
    additionalImages.value = JSON.parse(JSON.stringify(initialAdditionalImages.value));
    notify('Cambios descartados', 'info');
};

const navigationItems = inventoryNavigation;

const isDirty = computed(() => {
    const formChanged = form.isDirty;
    const imagesChanged = additionalImages.value.length !== initialAdditionalImages.value.length ||
        additionalImages.value.some(img => img.file !== undefined);
    return formChanged || imagesChanged;
});

const variantName = computed(() => {
    const attributes = props.variant.attribute_values?.map((av: any) => av.value).join(', ') || 'Sin atributos';
    return `${props.variant.product?.name} - ${attributes}`;
});
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form title="Variantes" subtitle="Editar" :tabs="tabs" :loading="form.processing" @submit="handleSubmit"
            @cancel="handleCancel" :disabled="!isDirty" back-route="/finanzas/inventario/variantes">

            <template #top-left>
                <Label class="text-sm font-bold text-gray-700 mb-1 block">Nombre del producto</Label>
                <Input :model-value="variantName" readonly disabled
                    inputClass="text-3xl font-medium bg-gray-100 text-gray-500" />
            </template>

            <template #top-right>
                <div class="w-32 h-32 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center relative group cursor-pointer hover:bg-gray-100 transition-colors overflow-hidden"
                    @click="handleImageClick">
                    <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                    <div v-else class="text-center">
                        <CloudUpload class="w-8 h-8 text-gray-400 mx-auto mb-1" />
                        <span class="text-xs text-gray-500">Imagen</span>
                    </div>
                    <input ref="fileInputRef" type="file" class="hidden" accept="image/*" @change="handleImageChange" />
                </div>
            </template>

            <template #general>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="grid gap-4 grid-cols-[140px_1fr] items-center">
                            <Label class="text-sm font-semibold text-gray-900">Precio de venta <span
                                    class="text-red-500">*</span></Label>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 font-medium pb-1">S/</span>
                                <Input v-model="form.price" type="number" step="0.01" :error="form.errors.price" />
                            </div>
                        </div>
                        <div class="grid gap-4 grid-cols-[140px_1fr] items-center">
                            <Label class="text-sm font-semibold text-gray-900">Stock <span
                                    class="text-red-500">*</span></Label>
                            <Input v-model="form.stock" type="number" :error="form.errors.stock" />
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid gap-4 grid-cols-[140px_1fr] items-center">
                            <Label class="text-sm font-semibold text-gray-900">Referencia interna</Label>
                            <Input v-model="form.sku" placeholder="SKU-001" :error="form.errors.sku" />
                        </div>
                        <div class="grid gap-4 grid-cols-[140px_1fr] items-center">
                            <Label class="text-sm font-semibold text-gray-900">Código de barras</Label>
                            <Input v-model="form.barcode" placeholder="EAN-13" :error="form.errors.barcode" />
                        </div>
                    </div>
                </div>
            </template>

            <template #images>
                <ProductImageGallery v-model:images="additionalImages" />
            </template>
        </Form>
    </ModuleLayout>
</template>
