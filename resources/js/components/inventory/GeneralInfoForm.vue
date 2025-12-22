<script setup lang="ts">
import { computed, ref } from 'vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Textarea from '@/components/ui/Textarea.vue';
import GeneralSearchModal from '@/components/ui/GeneralSearchModal.vue';
import type { Category } from '@/types/product';
import axios from 'axios';

const props = defineProps<{
    form: any;
    categories: Category[];
}>();

const showCategoryModal = ref(false);
const searchedCategories = ref<Category[]>([]);
const isSearching = ref(false);
const isLoading = ref(false);

const handleCategorySearch = async (query: string) => {
    if (!query) {
        isSearching.value = false;
        searchedCategories.value = [];
        return;
    }
    isSearching.value = true;
    isLoading.value = true;
    try {
        const response = await axios.post('/api/categories/search', {
            search: query,
            page: 1,
            per_page: 50
        });
        searchedCategories.value = response.data.data;
    } catch (error) {
        console.error('Error searching categories:', error);
    } finally {
        isLoading.value = false;
    }
};

const topCategories = computed(() => {
    return props.categories?.slice(0, 20) || [];
});

const getCategoryFullName = (category: Category): string => {
    if (category.full_name) return category.full_name;
    if (category.parent) return `${category.parent.name} / ${category.name}`;
    return category.name;
};

const handleCategorySelect = (category: Category) => {
    props.form.category_id = category.id;
    showCategoryModal.value = false;
};

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

const leftColumnFields = computed<Field[]>(() => [
    {
        id: 'category_id',
        label: 'Categoría',
        component: Input,
        props: {
            modelValue: props.form.category_id,
            options: isSearching.value
                ? searchedCategories.value.map((c) => ({
                    value: c.id,
                    label: getCategoryFullName(c)
                }))
                : topCategories.value.map((c) => ({
                    value: c.id,
                    label: getCategoryFullName(c)
                })),
            placeholder: 'Seleccionar',
            error: props.form.errors.category_id,
            showSearchMore: true,
            disableLocalFilter: true,
            loading: isLoading.value,
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
            modelValue: props.form.description,
            placeholder: 'Notas internas',
            error: props.form.errors.description,
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
            modelValue: props.form.price,
            type: 'number',
            error: props.form.errors.price,
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
            modelValue: props.form.sku,
            placeholder: 'SKU-001',
            error: props.form.errors.sku,
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
    },
    {
        id: 'barcode',
        label: 'Código de barras',
        component: Input,
        props: {
            modelValue: props.form.barcode,
            placeholder: 'EAN-13',
            error: props.form.errors.barcode,
        },
        gridCols: 'grid-cols-[140px_1fr]',
        itemsCenter: true,
    },
]);
</script>

<template>
    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-6">
                <div v-for="field in leftColumnFields" :key="field.id" :class="[
                    'grid gap-4',
                    field.gridCols,
                    field.itemsCenter ? 'items-center' : 'items-start',
                ]">
                    <Label class="text-sm font-semibold text-gray-900" :class="field.labelClass">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                    </Label>
                    <component :is="field.component" v-bind="field.props" @update:modelValue="
                        (form as any)[field.id] = $event
                        " @search-more="showCategoryModal = true"
                        @search="(query: string) => field.id === 'category_id' ? handleCategorySearch(query) : null" />
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

        <GeneralSearchModal v-model="showCategoryModal" :selected-category-id="form.category_id"
            @select="handleCategorySelect" />
    </div>
</template>
