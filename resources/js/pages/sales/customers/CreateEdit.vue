<script setup lang="ts">
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import { salesNavigation, salesIcon } from '@/config/salesNavigation';
import Form from '@/components/ui/Form.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useNotification } from '@/hooks/useNotification';
import { Customer, Identity } from '@/types/sales';

const props = defineProps<{
    customer?: Customer;
    identities: Identity[];
}>();

const { notify } = useNotification();
const isEditing = computed(() => !!props.customer);

const form = useForm({
    identity_id: props.customer?.identity_id || '',
    document_number: props.customer?.document_number || '',
    name: props.customer?.name || '',
    address: props.customer?.address || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
});

const handleSubmit = () => {
    const options = {
        onSuccess: () => {
            notify(
                isEditing.value
                    ? 'Cliente actualizado correctamente'
                    : 'Cliente creado correctamente',
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

    if (isEditing.value && props.customer) {
        form.put(`/finanzas/ventas/clientes/${props.customer.id}`, options);
    } else {
        form.post('/finanzas/ventas/clientes', options);
    }
};

const handleCancel = () => {
    form.reset();
    notify('Cambios descartados', 'info');
};

const breadcrumbs = computed(() => [
    { label: 'Clientes', route: '/finanzas/ventas/clientes' },
    { label: isEditing.value ? 'Editar' : 'Nuevo' }
]);

const isDirty = computed(() => form.isDirty);

const identityOptions = computed(() => {
    return props.identities.map(identity => ({
        value: identity.id,
        label: identity.name
    }));
});
</script>

<template>
    <ModuleLayout title="Ventas" :icon="salesIcon" :navigation-items="salesNavigation">
        <Form title="Clientes" :subtitle="isEditing ? 'Editar' : 'Nuevo'" :loading="form.processing"
            @submit="handleSubmit" @cancel="handleCancel" :disabled="!isDirty" :breadcrumbs="breadcrumbs">

            <template #top-left>
                <Label class="text-sm font-bold text-gray-700 mb-1 block">Nombre <span
                        class="text-red-500">*</span></Label>
                <Input v-model="form.name" placeholder="Ej. Juan Perez" inputClass="text-3xl font-medium"
                    :error="form.errors.name" />
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label class="block text-sm font-medium text-gray-700">Tipo de Documento <span
                            class="text-red-500">*</span></Label>
                    <Input v-model="form.identity_id" :options="identityOptions" placeholder="Seleccione"
                        :error="form.errors.identity_id" />
                </div>

                <div>
                    <Label class="block text-sm font-medium text-gray-700">Número de Documento <span
                            class="text-red-500">*</span></Label>
                    <Input v-model="form.document_number" :error="form.errors.document_number"
                        placeholder="Ej. 75265532" />
                </div>

                <div class="md:col-span-2">
                    <Label class="block text-sm font-medium text-gray-700">Dirección</Label>
                    <Input v-model="form.address" :error="form.errors.address" placeholder="Ej. Av. Siempre Viva 123" />
                </div>

                <div>
                    <Label class="block text-sm font-medium text-gray-700">Email</Label>
                    <Input type="email" v-model="form.email" :error="form.errors.email"
                        placeholder="Ej. correo@dominio.com" />
                </div>

                <div>
                    <Label class="block text-sm font-medium text-gray-700">Teléfono</Label>
                    <Input v-model="form.phone" :error="form.errors.phone" placeholder="Ej. 987654321" />
                </div>
            </div>
        </Form>
    </ModuleLayout>
</template>
