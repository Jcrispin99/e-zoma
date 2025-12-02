<script setup lang="ts">
import ModuleLayout, {
    NavigationItem,
} from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import Form from '@/components/ui/Form.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import MultiSelect from '@/components/ui/MultiSelect.vue';
import Label from '@/components/ui/Label.vue';
import Textarea from '@/components/ui/Textarea.vue';
import CategorySearchModal from '@/components/ui/CategorySearchModal.vue';
import { Trash2, CloudUpload, Image as ImageIcon, Plus } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import type { Product, Category, Attribute, AttributeLine, FormVariant, AdditionalImage } from '@/types/product';

const props = defineProps<{
    product?: Product;
    categories: Category[];
    attributes: Attribute[];
}>();

const isEditing = computed(() => !!props.product);

const form = useForm({
    name: props.product?.name || '',
    description: props.product?.description || '',
    price: props.product?.price || '',
    category_id: props.product?.category_id || '',
    sku: props.product?.sku || '',
    barcode: props.product?.barcode || '',
    image: null as File | null,
});

const imagePreview = ref(props.product?.image || null);
const additionalImages = ref<AdditionalImage[]>([]);
const additionalImagesInput = ref<HTMLInputElement | null>(null);

watch(() => props.product?.image, (newImage) => {
    if (newImage) {
        imagePreview.value = newImage;
    }
});

watch(() => props.product, (product) => {
    if (product?.images && product.images.length > 0) {
        additionalImages.value = product.images.map((img: any) => ({
            id: img.id,
            url: `/storage/${img.path}`,
        }));
    }
}, { immediate: true });

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

const handleAdditionalImagesClick = () => {
    additionalImagesInput.value?.click();
};

const handleAdditionalImagesChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    if (files) {
        Array.from(files).forEach(file => {
            additionalImages.value.push({
                url: URL.createObjectURL(file),
                file: file,
            });
        });
    }
};

const removeAdditionalImage = (index: number) => {
    additionalImages.value.splice(index, 1);
};

const getAttributeValues = (attributeId: string | number) => {
    const attribute = props.attributes.find(a => a.id === attributeId);
    return attribute?.attribute_values || [];
};

const tabs = [
    { id: 'general', label: 'Información General' },
    { id: 'attributes', label: 'Atributos y Variantes' },
    { id: 'images', label: 'Imágenes' },
];

interface Field {
    id: string;
    label: string;
    component: any;
    props: Record<string, any>;
    gridCols: string;
    itemsCenter: boolean;
    labelClass?: string;
    prefix?: string;
    required?: boolean;
}

const getCategoryFullName = (category: Category): string => {
    if (category.full_name) {
        return category.full_name;
    }
    if (category.parent) {
        return `${category.parent.name} / ${category.name}`;
    }
    return category.name;
};

const showCategoryModal = ref(false);

const topCategories = computed(() => {
    return props.categories?.slice(0, 20) || [];
});

const handleCategorySelect = (category: Category) => {
    form.category_id = category.id;
    showCategoryModal.value = false;
};

const leftColumnFields = computed<Field[]>(() => [
    {
        id: 'category_id',
        label: 'Categoría',
        component: Input,
        props: {
            modelValue: form.category_id,
            options: topCategories.value.map((c) => ({
                value: c.id,
                label: getCategoryFullName(c)
            })),
            placeholder: 'Seleccionar',
            error: form.errors.category_id,
            showSearchMore: true,
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
        required: true,
    },
    {
        id: 'description',
        label: 'Descripción',
        component: Textarea,
        props: {
            modelValue: form.description,
            placeholder: 'Notas internas',
            error: form.errors.description,
            class: 'w-full min-h-[100px]',
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: false,
        labelClass: 'pt-2',
    },
]);

const rightColumnFields = computed<Field[]>(() => [
    {
        id: 'price',
        label: 'Precio de venta',
        component: Input,
        props: {
            modelValue: form.price,
            type: 'number',
            error: form.errors.price,
        },
        prefix: 'S/',
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
        required: true,
    },
    {
        id: 'sku',
        label: 'Referencia interna',
        component: Input,
        props: {
            modelValue: form.sku,
            placeholder: 'SKU-001',
            error: form.errors.sku,
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
    },
    {
        id: 'barcode',
        label: 'Código de barras',
        component: Input,
        props: {
            modelValue: form.barcode,
            placeholder: 'EAN-13',
            error: form.errors.barcode,
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
    },
]);

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
            toast.success(
                isEditing.value
                    ? 'Producto actualizado correctamente'
                    : 'Producto creado correctamente'
            );
        },
        onError: (errors: any) => {
            toast.error('Completa los campos obligatorios');
        },
    };

    const url = isEditing.value
        ? `/finanzas/inventario/productos/${props.product?.id}`
        : '/finanzas/inventario/productos';

    form.post(url, options);
};

const handleCancel = () => {
    form.reset();

    imagePreview.value = props.product?.image || null;

    attributeLines.value = JSON.parse(JSON.stringify(initialAttributeLines.value));
    generatedVariants.value = JSON.parse(JSON.stringify(initialGeneratedVariants.value));
    additionalImages.value = JSON.parse(JSON.stringify(initialAdditionalImages.value));

    toast.info('Cambios descartados');
};

const navigationItems: NavigationItem[] = [
    { label: 'Información general', href: '/finanzas/inventario' },
    {
        label: 'Productos',
        items: [
            { label: 'Productos', href: '/finanzas/inventario/productos' },
            { label: 'Variantes', href: '#' },
        ],
    },
    { label: 'Reportes', href: '#' },
    {
        label: 'Configuración',
        sections: [
            {
                title: 'Gestión del almacén',
                items: [{ label: 'Almacenes', href: '#' }],
            },
            {
                title: 'Productos',
                items: [
                    { label: 'Categorías', href: '#' },
                    { label: 'Atributos', href: '#' },
                ],
            },
        ],
    },
];

const attributeLines = ref<AttributeLine[]>([]);
const generatedVariants = ref<FormVariant[]>([]);

const initialAttributeLines = ref<AttributeLine[]>([]);
const initialGeneratedVariants = ref<FormVariant[]>([]);

const initialAdditionalImages = ref<AdditionalImage[]>([]);

const availableAttributes = computed(() => props.attributes || []);

const isDirty = computed(() => {
    const formChanged = form.isDirty;

    const attributesChanged = JSON.stringify(attributeLines.value) !== JSON.stringify(initialAttributeLines.value);

    const variantsChanged = JSON.stringify(generatedVariants.value) !== JSON.stringify(initialGeneratedVariants.value);

    const imagesChanged = additionalImages.value.length !== initialAdditionalImages.value.length ||
        additionalImages.value.some(img => img.file !== undefined);

    return formChanged || attributesChanged || variantsChanged || imagesChanged;
});

const initializeForm = () => {
    if (!props.product?.variants) return;

    const linesMap = new Map<string | number, Set<string>>();

    props.product.variants.forEach((variant) => {
        if (variant.attribute_values) {
            variant.attribute_values.forEach((av) => {
                if (!linesMap.has(av.attribute_id)) {
                    linesMap.set(av.attribute_id, new Set());
                }
                linesMap.get(av.attribute_id)?.add(av.value);
            });
        }
    });

    attributeLines.value = Array.from(linesMap.entries()).map(([attrId, valuesSet]) => ({
        attribute_id: attrId,
        values: Array.from(valuesSet)
    }));

    generatedVariants.value = props.product.variants.map((v) => {
        const attributesMap: Record<string, string> = {};
        if (v.attribute_values) {
            v.attribute_values.forEach((av) => {
                attributesMap[av.attribute_id.toString()] = av.value;
            });
        }

        const values = v.attribute_values ? v.attribute_values.map((av) => av.value).join(', ') : '';
        const name = values ? `${props.product?.name} - ${values}` : props.product?.name || '';

        return {
            name: name,
            sku: v.sku,
            price: v.price,
            barcode: v.barcode,
            stock: v.stock,
            attributes: attributesMap
        };
    });

    initialAttributeLines.value = JSON.parse(JSON.stringify(attributeLines.value));
    initialGeneratedVariants.value = JSON.parse(JSON.stringify(generatedVariants.value));
    initialAdditionalImages.value = JSON.parse(JSON.stringify(additionalImages.value));
};

watch(() => props.product, () => {
    initializeForm();
}, { immediate: true });

const addAttributeLine = () => {
    attributeLines.value.push({
        attribute_id: '',
        values: [],
    });
};

const removeAttributeLine = (index: number) => {
    attributeLines.value.splice(index, 1);
    generateVariants();
};

const handleAttributeChange = (line: AttributeLine) => {
    generateVariants();
};

const generateVariants = () => {
    const linesWithValues = attributeLines.value.filter(l => l.attribute_id && l.values.length > 0);

    if (linesWithValues.length === 0) {
        const name = form.name;
        const existing = generatedVariants.value.find(v => v.name === name);

        generatedVariants.value = [{
            name: name,
            sku: existing?.sku || form.sku,
            price: existing?.price || form.price,
            barcode: existing?.barcode || form.barcode,
            stock: existing?.stock || 0,
            attributes: {}
        }];
        return;
    }

    const cartesian = (args: any[]): any[] => {
        const r: any[] = [];
        const max = args.length - 1;
        function helper(arr: any[], i: number) {
            for (let j = 0, l = args[i].length; j < l; j++) {
                const a = arr.slice(0);
                a.push(args[i][j]);
                if (i == max)
                    r.push(a);
                else
                    helper(a, i + 1);
            }
        }
        helper([], 0);
        return r;
    };

    const attributeValues = linesWithValues.map(l => l.values);
    const combinations = cartesian(attributeValues);

    const existingVariantsMap = new Map(
        generatedVariants.value.map(v => [v.name, v])
    );

    generatedVariants.value = combinations.map(combo => {
        const name = `${form.name} - ${combo.join(', ')}`;
        const existing = existingVariantsMap.get(name);

        const attributesMap: Record<string, string> = {};
        linesWithValues.forEach((line, index) => {
            attributesMap[line.attribute_id.toString()] = combo[index];
        });

        return {
            name: name,
            sku: existing?.sku || form.sku,
            price: existing?.price || form.price,
            barcode: existing?.barcode || form.barcode,
            stock: existing?.stock || 0,
            attributes: attributesMap
        };
    });
};
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div v-for="field in leftColumnFields" :key="field.id" :class="[
                            'grid gap-4',
                            field.gridCols,
                            field.itemsCenter ? 'items-center' : 'items-start',
                        ]">
                            <Label class="text-sm font-semibold text-gray-900" :class="field.labelClass">{{ field.label
                            }}
                                <span v-if="field.required" class="text-red-500">*</span></Label>
                            <component :is="field.component" v-bind="field.props" @update:modelValue="
                                (form as any)[field.id] = $event
                                " @search-more="showCategoryModal = true" />
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div v-for="field in rightColumnFields" :key="field.id" :class="[
                            'grid gap-4',
                            field.gridCols,
                            field.itemsCenter ? 'items-center' : 'items-start',
                        ]">
                            <Label class="text-sm font-semibold text-gray-900" :class="field.labelClass">{{ field.label
                            }}
                                <span v-if="field.required" class="text-red-500">*</span></Label>
                            <div v-if="field.prefix" class="flex items-center gap-2">
                                <span class="text-gray-400 font-medium pb-1">{{
                                    field.prefix
                                }}</span>
                                <component :is="field.component" v-bind="field.props" @update:modelValue="
                                    (form as any)[field.id] = $event
                                    " />
                            </div>
                            <component v-else :is="field.component" v-bind="field.props" @update:modelValue="
                                (form as any)[field.id] = $event
                                " />
                        </div>
                    </div>
                </div>
            </template>

            <template #attributes>
                <div class="space-y-8">
                    <div class="border-y border-gray-200">
                        <div class="grid grid-cols-[1fr_2fr_40px] gap-4 p-4 border-b border-gray-200">
                            <Label class="text-sm font-semibold text-gray-900">Atributo</Label>
                            <Label class="text-sm font-semibold text-gray-900">Valores</Label>
                            <div></div>
                        </div>

                        <div class="p-4 space-y-4">
                            <div v-for="(line, index) in attributeLines" :key="index" class="group">
                                <div class="grid grid-cols-[1fr_2fr_40px] gap-4 items-start">
                                    <div>
                                        <Input v-model="line.attribute_id"
                                            :options="availableAttributes.map(a => ({ value: a.id, label: a.name }))"
                                            placeholder="Seleccionar atributo..."
                                            @update:modelValue="handleAttributeChange(line)" />
                                    </div>
                                    <div>
                                        <MultiSelect v-model="line.values"
                                            :options="getAttributeValues(line.attribute_id).map((v: any) => ({ value: v.value, label: v.value }))"
                                            :allow-custom="true" placeholder="Escribe o selecciona"
                                            @update:modelValue="generateVariants" />
                                    </div>
                                    <div class="flex justify-center pt-2">
                                        <button type="button" @click="removeAttributeLine(index)"
                                            class="text-gray-400 hover:text-red-500 transition-colors"
                                            title="Eliminar atributo">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="addAttributeLine"
                                class="text-sm font-medium text-teal-600 hover:text-teal-700 flex items-center mt-2">
                                <Plus class="w-4 h-4 mr-2" />
                                Agregar una línea
                            </button>
                        </div>
                    </div>

                    <div v-if="generatedVariants.length > 0" class="border-y border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Detalles de las Variantes Generadas</h3>
                        <div class="space-y-4">
                            <div v-for="(variant, index) in generatedVariants" :key="index" class="p-4">
                                <div class="font-medium text-gray-900 mb-3">{{ variant.name }}</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <Label class="text-xs text-gray-500 mb-1 block">SKU</Label>
                                        <Input :modelValue="variant.sku" class="h-8 text-sm"
                                            @update:modelValue="generatedVariants[index].sku = $event" />
                                    </div>
                                    <div>
                                        <Label class="text-xs text-gray-500 mb-1 block">Precio</Label>
                                        <Input :modelValue="variant.price" type="number" class="h-8 text-sm"
                                            @update:modelValue="generatedVariants[index].price = $event" />
                                    </div>
                                    <div>
                                        <Label class="text-xs text-gray-500 mb-1 block">Código de Barras</Label>
                                        <Input :modelValue="variant.barcode" class="h-8 text-sm"
                                            @update:modelValue="generatedVariants[index].barcode = $event" />
                                    </div>
                                    <div>
                                        <Label class="text-xs text-gray-500 mb-1 block">Stock</Label>
                                        <Input :modelValue="variant.stock" type="number" class="h-8 text-sm" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #images>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Galería de imágenes</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Agrega imágenes adicionales para mostrar en la galería del producto.
                            </p>
                        </div>
                        <Button variant="secondary" @click="handleAdditionalImagesClick">
                            <Plus class="w-4 h-4 mr-2" />
                            Agregar imágenes
                        </Button>
                        <input ref="additionalImagesInput" type="file" class="hidden" accept="image/*" multiple
                            @change="handleAdditionalImagesChange" />
                    </div>

                    <div v-if="additionalImages.length > 0"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div v-for="(image, index) in additionalImages" :key="index"
                            class="relative group aspect-square rounded-lg overflow-hidden border border-gray-200 hover:border-teal-500 transition-colors">
                            <img :src="image.url" :alt="`Imagen ${index + 1}`" class="w-full h-full object-cover" />
                            <div
                                class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity flex items-center justify-center">
                                <button type="button" @click="removeAdditionalImage(index)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity bg-red-500 text-white rounded-full p-2 hover:bg-red-600">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center border-2 border-dashed border-gray-300 rounded-lg">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <ImageIcon class="w-8 h-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Sin imágenes adicionales</h3>
                        <p class="mt-1 text-gray-500">
                            Haz clic en "Agregar imágenes" para subir fotos del producto.
                        </p>
                    </div>
                </div>
            </template>
        </Form>

        <CategorySearchModal v-model="showCategoryModal" :selected-category-id="form.category_id"
            @select="handleCategorySelect" />
    </ModuleLayout>
</template>