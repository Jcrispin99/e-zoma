<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { inventoryNavigation, inventoryIcon } from '@/config/inventoryNavigation';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { Trash2, Plus } from 'lucide-vue-next';
import { useNotification } from '@/hooks/useNotification';
import type { Attribute } from '@/types/product';

const props = defineProps<{
    attribute?: Attribute;
}>();

const isEditing = !!props.attribute;
const navigationItems = inventoryNavigation;
const { notify } = useNotification();

const form = useForm({
    name: props.attribute?.name || '',
    values: (props.attribute?.attribute_values?.map((v) => ({
        id: v.id,
        value: v.value,
    })) || []) as { id: number | null; value: string }[]
});

watch(() => props.attribute, (newVal) => {
    if (newVal) {
        form.defaults({
            name: newVal.name,
            values: newVal.attribute_values?.map((v) => ({
                id: v.id,
                value: v.value,
            })) || []
        });
        form.reset();
    }
}, { deep: true });

const breadcrumbs = [
    { label: 'Atributos', route: '/finanzas/inventario/atributos' },
    { label: isEditing ? 'Editar' : 'Nuevo' },
];

const tabs = [
    { id: 'values', label: 'Valores de atributo' }
];

const addAttributeLine = () => {
    form.values.push({
        id: null,
        value: '',
    });
};

const removeValue = (index: number) => {
    form.values.splice(index, 1);
};

const handleSubmit = () => {
    if (isEditing) {
        form.put(`/finanzas/inventario/atributos/${props.attribute.id}`, {
            onSuccess: () => notify('Atributo actualizado correctamente', 'success'),
            onError: () => notify('Error al actualizar el atributo', 'error'),
        });
    } else {
        form.post('/finanzas/inventario/atributos', {
            onSuccess: () => notify('Atributo creado correctamente', 'success'),
            onError: () => notify('Error al crear el atributo', 'error'),
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
    <ModuleLayout title="Inventario" :icon="inventoryIcon" :navigation-items="navigationItems">
        <Form :title="isEditing ? 'Editar Atributo' : 'Nuevo Atributo'" :breadcrumbs="breadcrumbs"
            :loading="form.processing" @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty" :tabs="tabs">

            <template #top-left>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1 md:col-span-2">
                        <Label for="name">Nombre del atributo <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" placeholder="Ej. Color, Talla" :error="form.errors.name" />
                    </div>
                </div>
            </template>

            <template #values>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-full">
                                    Valor
                                </th>
                                <th scope="col" class="relative px-6 py-3 w-20">
                                    <span class="sr-only">Eliminar</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(val, index) in form.values" :key="index">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Input v-model="val.value" placeholder="Valor" class="h-8 text-sm" />
                                    <div v-if="(form.errors as any)[`values.${index}.value`]"
                                        class="text-red-500 text-xs mt-1">
                                        {{ (form.errors as any)[`values.${index}.value`] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="removeValue(index)" class="text-red-600 hover:text-red-900">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.values.length === 0">
                                <td colspan="2" class="px-6 pt-10 text-center text-gray-500 text-sm">
                                    No hay valores definidos. Añade uno abajo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <button type="button" @click="addAttributeLine"
                        class="text-sm font-medium text-teal-600 hover:text-teal-700 flex items-center mt-2">
                        <Plus class="w-4 h-4 mr-2" />
                        Agregar una línea
                    </button>
                </div>
            </template>

        </Form>
    </ModuleLayout>
</template>
