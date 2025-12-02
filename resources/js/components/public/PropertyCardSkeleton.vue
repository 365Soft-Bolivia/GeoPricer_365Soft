<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: 'sm' | 'md' | 'lg';
    count?: number;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    count: 1
});

const skeletons = computed(() => Array.from({ length: props.count }, (_, i) => i));

const containerClasses = computed(() => {
    const baseClasses = 'bg-white dark:bg-gray-800 rounded-xl border overflow-hidden';
    switch (props.size) {
        case 'sm':
            return `${baseClasses} max-w-sm`;
        case 'lg':
            return `${baseClasses} max-w-lg`;
        default:
            return `${baseClasses} max-w-md`;
    }
});

const imageHeightClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-40';
        case 'lg':
            return 'h-64';
        default:
            return 'h-48';
    }
});
</script>

<template>
    <div
        v-for="skeleton in skeletons"
        :key="skeleton"
        :class="containerClasses"
    >
        <!-- Skeleton imagen -->
        <div class="relative overflow-hidden" :class="imageHeightClasses">
            <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700 animate-pulse" />

            <!-- Skeleton badges -->
            <div class="absolute top-3 left-3 flex gap-2">
                <div class="h-6 w-16 bg-gray-300 dark:bg-gray-600 rounded-full animate-pulse" />
                <div v-if="Math.random() > 0.5" class="h-6 w-20 bg-gray-300 dark:bg-gray-600 rounded-full animate-pulse" />
            </div>

            <!-- Skeleton botones de acción -->
            <div class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-lg animate-pulse" />
                <div class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-lg animate-pulse" />
            </div>
        </div>

        <!-- Skeleton contenido -->
        <div class="p-4 space-y-3">
            <!-- Skeleton título y precio -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded-md animate-pulse w-3/4" />
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-1/2" />
                </div>
                <div class="text-right space-y-1">
                    <div class="h-7 bg-gray-300 dark:bg-gray-600 rounded-md animate-pulse w-20" />
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-8" />
                </div>
            </div>

            <!-- Skeleton características -->
            <div class="grid grid-cols-2 gap-2">
                <div v-for="i in 4" :key="i" class="flex items-center gap-1">
                    <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded-sm animate-pulse" />
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-12" />
                </div>
            </div>

            <!-- Skeleton ubicación y superficie -->
            <div class="space-y-2">
                <div class="flex items-center gap-1">
                    <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded-sm animate-pulse" />
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-2/3" />
                </div>
                <div class="flex items-center gap-1">
                    <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded-sm animate-pulse" />
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-1/3" />
                </div>
            </div>

            <!-- Skeleton descripción -->
            <div class="space-y-1">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse" />
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse w-4/5" />
            </div>
        </div>

        <!-- Skeleton botones -->
        <div class="p-4 pt-0 flex gap-2">
            <div class="h-9 flex-1 bg-gray-300 dark:bg-gray-600 rounded-md animate-pulse" />
            <div class="h-9 w-9 bg-gray-300 dark:bg-gray-600 rounded-md animate-pulse" />
        </div>
    </div>
</template>

<style scoped>
/* Animación personalizada para skeleton */
@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>