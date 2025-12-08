<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Textarea from '@/components/ui/Textarea.vue';
import CategorySearchModal from '@/components/ui/CategorySearchModal.vue';
import { useNotification } from '@/hooks/useNotification';
import type { Category } from '@/types/product';

const props = defineProps<{
    category?: Category;
    categories: Category[];
}>();

const isEditing = !!props.category;
const navigationItems = inventoryNavigation;
const { notify } = useNotification();
const showCategoryModal = ref(false);

const form = useForm({
    name: props.category?.name || '',
    description: props.category?.description || '',
    parent_id: props.category?.parent_id ?? '',
    category_id: props.category?.parent_id ?? '',
});

watch(() => props.category, (newVal) => {
    if (newVal) {
        form.defaults({
            name: newVal.name,
            description: newVal.description || '',
            parent_id: newVal.parent_id ?? '',
            category_id: newVal.parent_id ?? '',
        });
        form.reset();
    }
}, { deep: true });

const breadcrumbs = [
    { label: 'Categorías', route: '/finanzas/inventario/categorias' },
    { label: isEditing ? 'Editar' : 'Nuevo' },
];

const getCategoryFullName = (category: Category): string => {
    if (category.full_name) return category.full_name;
    if (category.parent) return `${category.parent.name} / ${category.name}`;
    return category.name;
};

const topCategories = computed(() => {
    return props.categories.slice(0, 20).map(cat => ({
        value: cat.id,
        label: getCategoryFullName(cat)
    }));
});

const parentOptions = computed(() => {
    return [{ value: '', label: 'Ninguna' }, ...topCategories.value];
});

const handleCategorySelect = (category: Category) => {
    form.parent_id = category.id;
    showCategoryModal.value = false;
};

const handleSubmit = () => {
    if (isEditing) {
        form.put(`/finanzas/inventario/categorias/${props.category!.id}`, {
            onSuccess: () => notify('Categoría actualizada correctamente', 'success'),
            onError: () => notify('Error al actualizar la categoría', 'error'),
        });
    } else {
        form.post('/finanzas/inventario/categorias', {
            onSuccess: () => notify('Categoría creada correctamente', 'success'),
            onError: () => notify('Error al crear la categoría', 'error'),
        });
    }
};

const handleCancel = () => {
    form.reset();
    notify('Cambios descartados', 'info');
};

const isDirty = computed(() => form.isDirty);
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form :title="isEditing ? 'Editar Categoría' : 'Nueva Categoría'" :breadcrumbs="breadcrumbs"
            :loading="form.processing" @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <div class="col-span-1 md:col-span-2">
                    <Label for="name">Nombre <span class="text-red-500">*</span></Label>
                    <Input id="name" v-model="form.name" placeholder="Nombre de la categoría"
                        :error="form.errors.name" />
                </div>

                <div class="col-span-1 md:col-span-2">
                    <Label for="description">Descripción</Label>
                    <Textarea id="description" v-model="form.description" placeholder="Breve descripción" :rows="1" />
                </div>

                <div class="col-span-1 md:col-span-2">
                    <Label for="parent_id">Categoría Padre</Label>
                    <Input id="parent_id" v-model="form.parent_id" :options="parentOptions"
                        placeholder="Seleccione una categoría padre" :error="form.errors.parent_id" show-search-more
                        @search-more="showCategoryModal = true" />
                </div>
            </div>
        </Form>

        <CategorySearchModal v-model="showCategoryModal" :selected-category-id="form.parent_id || ''"
            @select="handleCategorySelect" />
    </ModuleLayout>
</template>
