<script setup lang="ts">
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const { appearance } = useAppearance();

// Determinar si el tema es oscuro
const isDarkMode = computed(() => {
    if (appearance.value === 'dark') return true;
    if (appearance.value === 'light') return false;
    // appearance === 'system', detectar preferencia del sistema
    if (typeof window !== 'undefined') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    return false;
});

// Seleccionar el icono adecuado según el tema
const logoSrc = computed(() => {
    return isDarkMode.value
        ? '/Recurso 2Analytics 2.png' // Icono para modo oscuro
        : '/Recurso 1Analytics 1.png'; // Icono para modo claro
});
</script>

<template>
    <img
        :src="logoSrc"
        alt="Alfa Inmobiliaria Bolivia"
        class="h-full w-full object-contain"
        style="max-width: none; max-height: none;"
        v-bind="$attrs"
    />
</template>
