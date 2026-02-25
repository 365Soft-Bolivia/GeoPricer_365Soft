<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8 space-y-0.5">
        <h2 class="text-xl font-semibold tracking-tight" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">{{ title }}</h2>
        <p v-if="description" class="text-sm text-muted-foreground" style="font-family: 'Montserrat', sans-serif; font-weight: 400;">
            {{ description }}
        </p>
    </div>
</template>
