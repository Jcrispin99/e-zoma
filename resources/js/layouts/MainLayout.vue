<script setup lang="ts">
import Topbar from '@/components/layouts/Topbar.vue';
import { Toaster } from 'vue-sonner';
</script>

<template>
    <div class="bg-white">
        <Topbar>
            <template #brand v-if="$slots.brand">
                <slot name="brand" />
            </template>
            <template #navigation v-if="$slots.navigation">
                <slot name="navigation" />
            </template>
        </Topbar>

        <main class="pt-10 pb-12 max-w-9xl mx-auto">
            <slot />
        </main>

        <Toaster position="bottom-right" richColors />
    </div>
</template>
