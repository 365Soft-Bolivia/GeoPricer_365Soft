<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { publicRoutes } from '@/routes-custom';
import { useAppearance } from '@/composables/useAppearance';

const mobileMenuOpen = ref(false);
const { appearance } = useAppearance();

const { home } = publicRoutes;

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

const navigation = [
    { name: 'Inicio', href: home().url },
];
</script>

<template>
    <header class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] shadow-2xl sticky top-0 z-50 border-b-4 border-[#FAB90E]">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link :href="home()" class="flex items-center group">
                        <div class="bg-white p-2 rounded-lg mr-3 shadow-lg group-hover:scale-110 transition-transform">
                            <img :src="logoSrc" alt="Alfa Inmobiliaria Bolivia" class="h-14 w-auto object-contain" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-bold text-white tracking-tight" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                                Alfa Inmobiliaria
                            </span>
                            <span class="text-xs text-[#FAB90E] font-semibold tracking-wider" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                                BOLIVIA
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Navegación Desktop -->
                <div class="hidden md:flex md:items-center md:space-x-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-white/90 hover:text-white hover:bg-white/10 px-5 py-2.5 rounded-lg text-sm font-semibold transition-all duration-300 relative group"
                    >
                        {{ item.name }}
                        <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-0 h-0.5 bg-[#FAB90E] group-hover:w-full transition-all duration-300"></span>
                    </Link>

                    <!-- Botón CTA Mapa -->
                    <!-- <Link
                        href="/mapa-propiedades"
                        class="flex items-center gap-2 bg-[#FAB90E] hover:bg-[#ffc932] text-[#212121] px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 ml-4"
                        title="Ver mapa interactivo de propiedades"
                    >
                        <MapPin :size="18" class="animate-pulse" />
                        <span>Mapa Interactivo</span>
                    </Link> -->
                </div>

                <!-- Botón Menú Móvil -->
                <div class="md:hidden">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="text-white hover:text-[#FAB90E] p-2 rounded-lg hover:bg-white/10 transition-all"
                    >
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Menú Móvil -->
            <div v-if="mobileMenuOpen" class="md:hidden pb-6 animate-fade-in">
                <div class="flex flex-col space-y-2 bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-white hover:text-[#FAB90E] hover:bg-white/10 px-4 py-3 rounded-lg text-base font-semibold transition-all"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Botón CTA móvil -->
                    <Link
                        href="/mapa-propiedades"
                        class="flex items-center justify-center gap-2 bg-[#FAB90E] hover:bg-[#ffc932] text-[#212121] px-4 py-3 rounded-xl text-base font-bold transition-all shadow-lg mt-2"
                        @click="mobileMenuOpen = false"
                    >
                        <MapPin :size="20" class="animate-pulse" />
                        <span>Mapa Interactivo</span>
                    </Link>
                </div>
            </div>
        </nav>
    </header>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>