<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { publicRoutes } from '@/routes-custom';
import { useAppearance } from '@/composables/useAppearance';
import { computed } from 'vue';

const { home } = publicRoutes;
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

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-gradient-to-br from-[#212121] via-[#233C7A] to-[#1e2d4d] text-white">
        <!-- Cinta decorativa superior -->
        <div class="h-2 bg-gradient-to-r from-[#FAB90E] via-[#E0081D] to-[#FAB90E]"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <!-- Sección Branding -->
                <div class="space-y-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white p-2 rounded-xl shadow-lg">
                            <img :src="logoSrc" alt="Alfa Inmobiliaria Bolivia" class="h-20 w-auto object-contain" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Alfa Inmobiliaria</h3>
                            <p class="text-[#FAB90E] font-semibold text-sm tracking-wider" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">BOLIVIA</p>
                        </div>
                    </div>
                    <p class="text-[#F5F5F5]/80 leading-relaxed subtitle-elegant">
                        Tu mejor opción para encontrar la propiedad de tus sueños. Avalúos profesionales, mapas interactivos y el mejor servicio inmobiliario.
                    </p>
                    <!-- Badge de confianza -->
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg p-3 border border-white/20">
                        <span class="text-2xl">⭐</span>
                        <span class="font-semibold" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">Líder en Avalúos</span>
                    </div>
                </div>

                <!-- Enlaces Rápidos -->
                <div>
                    <h3 class="text-lg font-bold mb-6 flex items-center" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                        <span class="w-1 h-6 bg-[#FAB90E] rounded mr-3"></span>
                        Enlaces Rápidos
                    </h3>
                    <ul class="space-y-4">
                        <li>
                            <Link :href="home()" class="flex items-center group text-[#F5F5F5]/90 hover:text-[#FAB90E] transition-all duration-300">
                                <span class="w-2 h-2 bg-[#233C7A] rounded-full mr-3 group-hover:bg-[#FAB90E] group-hover:scale-125 transition-all"></span>
                                Inicio
                            </Link>
                        </li>
                        <li>
                            <Link href="/mapa-propiedades" class="flex items-center group text-[#F5F5F5]/90 hover:text-[#FAB90E] transition-all duration-300">
                                <span class="w-2 h-2 bg-[#233C7A] rounded-full mr-3 group-hover:bg-[#FAB90E] group-hover:scale-125 transition-all"></span>
                                Mapa Interactivo
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contacto -->
                <div>
                    <h3 class="text-lg font-bold mb-6 flex items-center" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                        <span class="w-1 h-6 bg-[#E0081D] rounded mr-3"></span>
                        Contáctanos
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start space-x-3 text-[#F5F5F5]/90">
                            <span class="text-xl mt-1">📍</span>
                            <span>La Paz, Bolivia</span>
                        </li>
                        <li class="flex items-start space-x-3 text-[#F5F5F5]/90">
                            <span class="text-xl mt-1">📞</span>
                            <span>+591 123 4567</span>
                        </li>
                        <li class="flex items-start space-x-3 text-[#F5F5F5]/90">
                            <span class="text-xl mt-1">✉️</span>
                            <span>info@alfainmobiliaria.bo</span>
                        </li>
                        <li class="flex items-start space-x-3 text-[#F5F5F5]/90">
                            <span class="text-xl mt-1">🕐</span>
                            <span>Lunes - Viernes: 8:00 - 17:00</span>
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    <Link href="/mapa-propiedades" class="mt-6 inline-flex items-center justify-center gap-2 bg-[#FAB90E] hover:bg-[#ffc932] text-[#212121] px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 w-full" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                        <span>Ver Mapa Interactivo</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-12 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>

            <!-- Copyright -->
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 text-sm text-[#F5F5F5]/80">
                <p>&copy; {{ currentYear }} (365 Soft Bolivia). Todos los derechos reservados.</p>
                <div class="flex items-center space-x-6">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FAB90E]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 9.67-1.736 0-3.356-.52-4.701-1.413l2.143-2.143a5.976 5.976 0 012.558.572c3.15 0 5.704-2.554 5.704-5.704 0-.345-.031-.683-.09-1.011L15 14l-3.898-3.898a5.976 5.976 0 01-.572-2.558c0-3.15 2.554-5.704 5.704-5.704.345 0 .683.031 1.011.09L10 5 6.002 1.002a5.976 5.976 0 012.558-.572c3.15 0 5.704 2.554 5.704 5.704 0 .345-.031.683-.09 1.011L15 14l-3.898-3.898z" clip-rule="evenodd"></path>
                        </svg>
                        Hecho por 365soft en Bolivia
                    </span>
                </div>
            </div>
        </div>

        <!-- Cinta decorativa inferior -->
        <div class="h-2 bg-gradient-to-r from-[#E0081D] via-[#FAB90E] to-[#E0081D]"></div>
    </footer>
</template>