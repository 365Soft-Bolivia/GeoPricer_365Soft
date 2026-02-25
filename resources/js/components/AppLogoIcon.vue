<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/logoalfa.png"
        alt="Alfa Inmobiliaria Bolivia"
        class="h-full w-full object-contain"
        style="max-width: none; max-height: none;"
        v-bind="$attrs"
    />
</template>
