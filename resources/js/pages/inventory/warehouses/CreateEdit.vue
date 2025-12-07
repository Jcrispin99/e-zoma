<script setup lang="ts">
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { useNotification } from '@/hooks/useNotification';
import type { Warehouse } from '@/types/warehouse';

const props = defineProps<{
    warehouse?: Warehouse;
}>();

const isEditing = !!props.warehouse;
const navigationItems = inventoryNavigation;
const { notify } = useNotification();

const form = useForm({
    name: props.warehouse?.name || '',
    location: props.warehouse?.location || '',
});

const breadcrumbs = [
    { label: 'Almacenes', route: '/finanzas/inventario/almacenes' },
    { label: isEditing ? 'Editar' : 'Nuevo' },
];

const handleSubmit = () => {
    if (isEditing) {
        form.put(`/finanzas/inventario/almacenes/${props.warehouse!.id}`, {
            onSuccess: () => notify('Almacén actualizado correctamente', 'success'),
            onError: () => notify('Error al actualizar el almacén', 'error'),
        });
    } else {
        form.post('/finanzas/inventario/almacenes', {
            onSuccess: () => notify('Almacén creado correctamente', 'success'),
            onError: () => notify('Error al crear el almacén', 'error'),
        });
    }
};

const handleCancel = () => {
    form.reset();
    notify('Cambios descartados', 'info');
    router.visit('/finanzas/inventario/almacenes');
};

const isDirty = computed(() => form.isDirty);
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form :title="isEditing ? 'Editar Almacén' : 'Nuevo Almacén'" :breadcrumbs="breadcrumbs"
            :loading="form.processing" @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <div>
                    <Label for="name">Nombre <span class="text-red-500">*</span></Label>
                    <Input id="name" v-model="form.name" placeholder="Nombre del almacén" :error="form.errors.name" />
                </div>
                <div>
                    <Label for="location">Ubicación</Label>
                    <Input id="location" v-model="form.location" placeholder="Ubicación física"
                        :error="form.errors.location" />
                </div>
            </div>
        </Form>
    </ModuleLayout>
</template>
