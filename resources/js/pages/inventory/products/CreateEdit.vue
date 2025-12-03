<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { CloudUpload } from 'lucide-vue-next';
import GeneralInfoForm from '@/components/inventory/GeneralInfoForm.vue';
import ProductImageGallery from '@/components/inventory/ProductImageGallery.vue';
import ProductAttributesForm from '@/components/inventory/ProductAttributesForm.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useNotification } from '@/hooks/useNotification';
import type { Product, Category, Attribute, AdditionalImage, AttributeLine, FormVariant } from '@/types/product';
import { inventoryNavigation } from '@/config/inventoryNavigation';

const props = defineProps<{
    product?: Product;
    categories: Category[];
    attributes: Attribute[];
}>();

const { notify } = useNotification();
const isEditing = computed(() => !!props.product);

const form = useForm({
    name: props.product?.name || '',
    description: props.product?.description || '',
    price: props.product?.price || '',
    category_id: props.product?.category_id || '',
    sku: props.product?.sku || '',
    barcode: props.product?.barcode || '',
    image: null as File | null,
    image_url: props.product?.image || null,
});

const imagePreview = ref<string | null>(props.product?.image || null);
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

const attributeLines = ref<AttributeLine[]>([]);
const generatedVariants = ref<FormVariant[]>([]);

const attributesFormRef = ref<InstanceType<typeof ProductAttributesForm> | null>(null);

watch(() => props.product, (product) => {
    if (product?.images && product.images.length > 0) {
        additionalImages.value = product.images.map((img: any) => ({
            id: img.id,
            url: `/storage/${img.path}`,
        }));
        initialAdditionalImages.value = JSON.parse(JSON.stringify(additionalImages.value));
    }
}, { immediate: true });

const tabs = [
    { id: 'general', label: 'Información General' },
    { id: 'attributes', label: 'Atributos y Variantes' },
    { id: 'images', label: 'Imágenes' },
];

const handleSubmit = () => {
    form.transform((data) => {
        const newImages = additionalImages.value
            .filter(img => img.file)
            .map(img => img.file);

        const transformed: any = {
            name: data.name,
            description: data.description || '',
            price: data.price,
            category_id: data.category_id,
            sku: data.sku || '',
            barcode: data.barcode || '',
            image: data.image,
            attributeLines: attributeLines.value,
            generatedVariants: generatedVariants.value,
            additionalImages: newImages,
            existingImageIds: additionalImages.value
                .filter(img => img.id)
                .map(img => img.id),
        };

        if (isEditing.value) {
            transformed._method = 'PUT';
        }

        return transformed;
    });

    const options = {
        forceFormData: true,
        onSuccess: () => {
            notify(
                isEditing.value
                    ? 'Producto actualizado correctamente'
                    : 'Producto creado correctamente',
                'success'
            );
        },
        onError: (errors: any) => {
            console.error('Validation errors:', errors);
            if (Object.keys(errors).length > 0) {
                const errorMessages = Object.values(errors).flat().join('\n');
                notify(`Errores de validación:\n${errorMessages}`, 'error');
            } else {
                notify('Completa los campos obligatorios', 'error');
            }
        },
    };

    const url = isEditing.value
        ? `/finanzas/inventario/productos/${props.product?.id}`
        : '/finanzas/inventario/productos';

    form.post(url, options);
};

const handleCancel = () => {
    form.reset();
    additionalImages.value = JSON.parse(JSON.stringify(initialAdditionalImages.value));
    attributesFormRef.value?.reset();
    notify('Cambios descartados', 'info');
};

const navigationItems = inventoryNavigation;

const attributesDirty = ref(false);

const isDirty = computed(() => {
    const formChanged = form.isDirty;
    const imagesChanged = additionalImages.value.length !== initialAdditionalImages.value.length ||
        additionalImages.value.some(img => img.file !== undefined);
    return formChanged || imagesChanged || attributesDirty.value;
});
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form title="Productos" :subtitle="isEditing ? 'Editar' : 'Nuevo'" :tabs="tabs" :loading="form.processing"
            @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty">

            <template #top-left>
                <Label class="text-sm font-bold text-gray-700 mb-1 block">Nombre del producto <span
                        class="text-red-500">*</span></Label>
                <Input v-model="form.name" placeholder="Por ejemplo, hamburguesa de queso"
                    inputClass="text-3xl font-medium" :error="form.errors.name" />
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
                <GeneralInfoForm :form="form" :categories="categories" />
            </template>

            <template #attributes>
                <ProductAttributesForm ref="attributesFormRef" :attributes="attributes" :product="product"
                    :form-name="form.name" :form-sku="form.sku" :form-price="form.price" :form-barcode="form.barcode"
                    v-model:attributeLines="attributeLines" v-model:generatedVariants="generatedVariants"
                    @dirty="attributesDirty = $event" @update:formSku="form.sku = $event"
                    @update:formBarcode="form.barcode = $event" @update:formPrice="form.price = $event" />
            </template>

            <template #images>
                <ProductImageGallery v-model:images="additionalImages" />
            </template>
        </Form>
    </ModuleLayout>
</template>