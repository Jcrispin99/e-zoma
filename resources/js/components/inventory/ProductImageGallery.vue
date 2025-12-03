<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import { Trash2, Plus, Image as ImageIcon } from 'lucide-vue-next';
import type { AdditionalImage } from '@/types/product';

const props = defineProps<{
    images: AdditionalImage[];
}>();

const emit = defineEmits(['update:images']);

const additionalImagesInput = ref<HTMLInputElement | null>(null);

const handleAdditionalImagesClick = () => {
    additionalImagesInput.value?.click();
};

const handleAdditionalImagesChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    if (files) {
        const newImages = [...props.images];
        Array.from(files).forEach(file => {
            newImages.push({
                url: URL.createObjectURL(file),
                file: file,
            });
        });
        emit('update:images', newImages);
    }
};

const removeAdditionalImage = (index: number) => {
    const newImages = [...props.images];
    newImages.splice(index, 1);
    emit('update:images', newImages);
};
</script>

<template>
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

        <div v-if="images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div v-for="(image, index) in images" :key="index"
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
