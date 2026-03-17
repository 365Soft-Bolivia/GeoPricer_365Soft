<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import publicRoutes from '@/routes/public';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center gap-6 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-6 md:p-10 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
    >
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <!-- Logo y encabezado -->
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="publicRoutes.login()"
                        class="flex flex-col items-center gap-2 font-medium group"
                    >
                        <div
                            class="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-lg transition-all group-hover:scale-105 dark:bg-gray-800"
                        >
                            <AppLogoIcon
                                class="size-8 fill-current text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ title || 'Bienvenido' }}
                        </h1>
                        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ description || 'Ingresa tus credenciales para acceder al sistema' }}
                        </p>
                    </div>
                </div>

                <!-- Contenido del formulario -->
                <div class="bg-white/70 backdrop-blur-sm p-6 rounded-xl shadow-xl border border-white/50 dark:bg-gray-800/70 dark:border-gray-700">
                    <slot />
                </div>

                <!-- Información adicional -->
                <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>ACM Analytics 365Soft © 2026</p>
                    <p class="text-xs mt-1">Sistema público de propiedades</p>
                </div>
            </div>
        </div>
    </div>
</template>