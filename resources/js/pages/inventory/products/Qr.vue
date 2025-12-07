<script setup lang="ts">
import { ref } from 'vue';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';
import QrGenerator from '@/components/inventory/QrGenerator.vue';
import Form from '@/components/ui/Form.vue';
import Button from '@/components/ui/Button.vue';
import { Printer } from 'lucide-vue-next';
import type { Product, Variant } from '@/types/product';
import type { QrStyle } from '@/types/qr';

const props = defineProps<{
    product: Product;
    variants: Variant[];
    styles: QrStyle[];
}>();

const navigationItems = inventoryNavigation;
const qrGeneratorRef = ref<InstanceType<typeof QrGenerator> | null>(null);

const handlePrint = () => {
    qrGeneratorRef.value?.handlePrint();
};

const breadcrumbs = [
    { label: 'Productos', route: '/finanzas/inventario/productos' },
    { label: 'Editar', route: `/finanzas/inventario/productos/${props.product.id}/editar` },
    { label: 'Generar QR' }
];
</script>

<template>
    <ModuleLayout title="Inventario" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form title="Productos" :breadcrumbs="breadcrumbs" :hide-default-actions="true">
            <template #actions>
                <Button @click="handlePrint">
                    <Printer class="w-4 h-4 mr-2" />
                    Imprimir
                </Button>
            </template>
            <QrGenerator ref="qrGeneratorRef" :items="variants" :styles="styles" />
        </Form>
    </ModuleLayout>
</template>
