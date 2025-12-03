<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import MultiSelect from '@/components/ui/MultiSelect.vue';
import { Trash2, Plus } from 'lucide-vue-next';
import type { Attribute, AttributeLine, FormVariant, Product } from '@/types/product';

const props = defineProps<{
    attributes: Attribute[];
    product?: Product;
    formName: string;
    formSku: string;
    formPrice: string | number;
    formBarcode: string;
}>();

const emit = defineEmits(['update:attributeLines', 'update:generatedVariants', 'dirty', 'update:formSku', 'update:formBarcode', 'update:formPrice']);

const attributeLines = ref<AttributeLine[]>([]);
const generatedVariants = ref<FormVariant[]>([]);

const initialAttributeLines = ref<AttributeLine[]>([]);
const initialGeneratedVariants = ref<FormVariant[]>([]);

const availableAttributes = computed(() => props.attributes || []);

const getAttributeValues = (attributeId: string | number) => {
    const attribute = props.attributes.find(a => a.id === attributeId);
    return attribute?.attribute_values || [];
};

const initializeForm = () => {
    if (props.product?.variants) {
        props.product.variants.find(v => v.is_principal);
    }

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

    const sortedVariants = [...props.product.variants].sort((a, b) => {
        return (Number(b.is_principal || 0)) - (Number(a.is_principal || 0));
    });

    generatedVariants.value = sortedVariants.map((v) => {
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

    emit('update:attributeLines', attributeLines.value);
    emit('update:generatedVariants', generatedVariants.value);
};

watch(() => props.product, () => {
    initializeForm();
}, { immediate: true });

const addAttributeLine = () => {
    attributeLines.value.push({
        attribute_id: '',
        values: [],
    });
    emit('update:attributeLines', attributeLines.value);
};

const removeAttributeLine = (index: number) => {
    attributeLines.value.splice(index, 1);
    generateVariants();
    emit('update:attributeLines', attributeLines.value);
};

const handleAttributeChange = () => {
    generateVariants();
    emit('update:attributeLines', attributeLines.value);
};

const generateVariants = () => {
    const linesWithValues = attributeLines.value.filter(l => l.attribute_id && l.values.length > 0);

    if (linesWithValues.length === 0) {
        const name = props.formName;
        const existing = generatedVariants.value.find(v => v.name === name);

        generatedVariants.value = [{
            name: name,
            sku: existing?.sku || props.formSku,
            price: existing?.price || props.formPrice,
            barcode: existing?.barcode || props.formBarcode,
            stock: existing?.stock || 0,
            attributes: {}
        }];
        emit('update:generatedVariants', generatedVariants.value);
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

    generatedVariants.value = combinations.map((combo, index) => {
        const name = `${props.formName} - ${combo.join(', ')}`;
        const existing = existingVariantsMap.get(name);

        const attributesMap: Record<string, string> = {};
        linesWithValues.forEach((line, idx) => {
            attributesMap[line.attribute_id.toString()] = combo[idx];
        });

        if (index === 0) {
            return {
                name: name,
                sku: props.formSku || existing?.sku || '',
                price: props.formPrice || existing?.price || 0,
                barcode: props.formBarcode || existing?.barcode || '',
                stock: existing?.stock || 0,
                attributes: attributesMap
            };
        }

        return {
            name: name,
            sku: existing ? (existing.sku || '') : (props.formSku || ''),
            price: existing ? (existing.price || 0) : (props.formPrice || 0),
            barcode: existing ? (existing.barcode || '') : (props.formBarcode || ''),
            stock: existing?.stock || 0,
            attributes: attributesMap
        };
    });
    emit('update:generatedVariants', generatedVariants.value);
};

watch(() => [props.formSku, props.formBarcode, props.formPrice], ([newSku, newBarcode, newPrice]) => {
    if (generatedVariants.value.length > 0) {
        let changed = false;
        if (generatedVariants.value[0].sku !== String(newSku || '')) {
            generatedVariants.value[0].sku = String(newSku || '');
            changed = true;
        }
        if (generatedVariants.value[0].barcode !== String(newBarcode || '')) {
            generatedVariants.value[0].barcode = String(newBarcode || '');
            changed = true;
        }
        if (generatedVariants.value[0].price != newPrice) {
            generatedVariants.value[0].price = newPrice;
            changed = true;
        }
        if (changed) {
            emit('update:generatedVariants', generatedVariants.value);
        }
    }
}, { immediate: true });

const handlePrincipalChange = (field: 'sku' | 'barcode' | 'price', value: string | number) => {
    if (field === 'sku') emit('update:formSku', value);
    if (field === 'barcode') emit('update:formBarcode', value);
    if (field === 'price') emit('update:formPrice', value);

    emit('update:generatedVariants', generatedVariants.value);
};

const checkDirty = () => {
    const isDirty = JSON.stringify(attributeLines.value) !== JSON.stringify(initialAttributeLines.value) ||
        JSON.stringify(generatedVariants.value) !== JSON.stringify(initialGeneratedVariants.value);
    emit('dirty', isDirty);
};

watch([attributeLines, generatedVariants], checkDirty, { deep: true });

const reset = () => {
    attributeLines.value = JSON.parse(JSON.stringify(initialAttributeLines.value));
    generatedVariants.value = JSON.parse(JSON.stringify(initialGeneratedVariants.value));
    emit('update:attributeLines', attributeLines.value);
    emit('update:generatedVariants', generatedVariants.value);
};

defineExpose({ reset });
</script>

<template>
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
                                placeholder="Seleccionar atributo..." @update:modelValue="handleAttributeChange" />
                        </div>
                        <div>
                            <MultiSelect v-model="line.values"
                                :options="getAttributeValues(line.attribute_id).map((v: any) => ({ value: v.value, label: v.value }))"
                                :allow-custom="true" placeholder="Escribe o selecciona"
                                @update:modelValue="generateVariants" />
                        </div>
                        <div class="flex justify-center pt-2">
                            <button type="button" @click="removeAttributeLine(index)"
                                class="text-gray-400 hover:text-red-500 transition-colors" title="Eliminar atributo">
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
                            <Label class="text-xs text-gray-500 mb-1 block">Referencia interna</Label>
                            <Input v-model="variant.sku" class="h-8 text-sm"
                                @update:modelValue="index === 0 ? handlePrincipalChange('sku', $event) : null" />
                        </div>
                        <div>
                            <Label class="text-xs text-gray-500 mb-1 block">Precio</Label>
                            <Input v-model="variant.price" type="number" class="h-8 text-sm"
                                @update:modelValue="index === 0 ? handlePrincipalChange('price', $event) : null" />
                        </div>
                        <div>
                            <Label class="text-xs text-gray-500 mb-1 block">Código de Barras</Label>
                            <Input v-model="variant.barcode" class="h-8 text-sm"
                                @update:modelValue="index === 0 ? handlePrincipalChange('barcode', $event) : null" />
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
