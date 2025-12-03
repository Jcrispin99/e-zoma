<script setup lang="ts">
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layouts/ModuleLayout.vue';
import inventarioIcon from '@/assets/images/iconos-modulos/inventario-koodi.png';
import { inventoryNavigation } from '@/config/inventoryNavigation';
import QrGenerator from '@/components/inventory/QrGenerator.vue';
import Form from '@/components/ui/Form.vue';
import Button from '@/components/ui/Button.vue';
import { Printer } from 'lucide-vue-next';

const props = defineProps<{
    variants: any[];
    styles: any[];
    context: 'products' | 'variants';
}>();

const navigationItems = inventoryNavigation;
const qrGeneratorRef = ref<InstanceType<typeof QrGenerator> | null>(null);

const handlePrint = () => {
    qrGeneratorRef.value?.handlePrint();
};

const breadcrumbs = computed(() => {
    const baseRoute = props.context === 'products'
        ? '/finanzas/inventario/productos'
        : '/finanzas/inventario/variantes';

    const label = props.context === 'products' ? 'Productos' : 'Variantes';

    return [
        { label: label, route: baseRoute },
        { label: 'Generar QR Masivo' }
    ];
});

const pageTitle = computed(() => props.context === 'products' ? 'Productos' : 'Variantes');
</script>

<template>
    <ModuleLayout :title="pageTitle" :icon="inventarioIcon" :navigation-items="navigationItems">
        <Form title="Generar QR Masivo" :breadcrumbs="breadcrumbs" :hide-default-actions="true">
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
