<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { publicRoutes } from '@/routes-custom'; // Importamos desde el directorio seguro - fixed

const mobileMenuOpen = ref(false);

const { home } = publicRoutes;

const navigation = [
    { name: 'Inicio', href: home().url },
    { name: 'Propiedades', href: '/propiedades' },
    { name: 'Sobre Nosotros', href: '/sobre-nosotros' },
    { name: 'Contacto', href: '/contacto' },
];
</script>

<template>
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between items-center">
                <div class="flex items-center">
                    <Link :href="home()" class="flex items-center">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            🏠 Inmobiliaria
                        </span>
                    </Link>
                </div>

                <div class="hidden md:flex md:items-center md:space-x-8">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Botón especial para el mapa interactivo -->
                    <Link
                        href="/mapa-propiedades"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-md hover:shadow-lg"
                        title="Ver mapa interactivo de propiedades"
                    >
                        <MapPin :size="18" />
                        <span>Mapa Interactivo</span>
                    </Link>
                </div>

                <div class="md:hidden">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="text-gray-700 dark:text-gray-300 hover:text-blue-600"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div v-if="mobileMenuOpen" class="md:hidden pb-4">
                <div class="flex flex-col space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-gray-700 dark:text-gray-300 hover:text-blue-600 px-3 py-2 text-base font-medium"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Botón del mapa en versión móvil -->
                    <Link
                        href="/mapa-propiedades"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-base font-medium transition-colors shadow-md hover:shadow-lg"
                        @click="mobileMenuOpen = false"
                    >
                        <MapPin :size="20" />
                        <span>Mapa Interactivo</span>
                    </Link>
                </div>
            </div>
        </nav>
    </header>
</template>