<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicAuthenticatedLayout from '@/layouts/PublicAuthenticatedLayout.vue';
import { MapPin, Award, CheckCircle } from 'lucide-vue-next';
import PropertySearchFilters from '@/components/public/PropertySearchFilters.vue';

interface Props {
    stats: {
        total_avaluos: number;
        años_experiencia: number;
        clientes_corporativos: number;
        precisión: number;
    };
    filter_options?: {
        categorias: Record<number, string>;
        operaciones: Record<string, string>;
        rango_precios: Record<string, string>;
    };
}

const props = withDefaults(defineProps<Props>(), {
    filter_options: () => ({
        categorias: {},
        operaciones: {
            'venta': 'Venta',
            // 'alquiler': 'Alquiler',
            // 'anticretico': 'Anticrético'
        },
        rango_precios: {}
    })
});

defineOptions({
    layout: PublicAuthenticatedLayout
});

const formatPrice = (price: string | number) => {
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(num);
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#233C7A] via-[#1a2e5f] to-[#233C7A] relative overflow-hidden">
        <!-- Decorative elements animados -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#FAB90E] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute top-40 right-10 w-72 h-72 bg-[#E0081D] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-[#233C7A] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>

        <!-- Contenido principal -->
        <div class="relative z-10">
            <!-- Header -->
            <div class="pt-16 pb-8 px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto text-center">
                    <!-- Logo/Brand -->
                    <div class="mb-6">
                        <div class="flex flex-col items-center gap-4">
                            <!-- Logo grande y responsivo con efectos de luz blanca -->
                            <div class="relative group">
                                <!-- Glow blanco animado detrás del logo -->
                                <div class="absolute -inset-2 bg-white rounded-2xl blur-2xl opacity-20 group-hover:opacity-50 animate-white-glow transition-all duration-700"></div>

                                <!-- Contenedor principal con efectos de luz blanca -->
                                <div class="relative bg-gradient-to-br from-white/20 via-white/10 to-white/5 backdrop-blur-md p-4 md:p-6 lg:p-8 rounded-2xl border border-white/20 shadow-2xl transition-all duration-500 group-hover:scale-105 group-hover:border-white/40 group-hover:shadow-[0_0_60px_rgba(255,255,255,0.3)]">
                                    <!-- Border pulsante blanco -->
                                    <div class="absolute inset-0 rounded-2xl bg-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10 blur-sm animate-pulse-border"></div>

                                    <!-- Logo con efectos de luz blanca -->
                                    <img
                                        src="/Recurso 2Analytics 2.png"
                                        alt="ALFA Analytics"
                                        class="relative h-20 md:h-32 lg:h-40 w-auto object-contain transition-all duration-500 group-hover:brightness-125 group-hover:drop-shadow-[0_0_30px_rgba(255,255,255,0.6)] filter drop-shadow-xl"
                                    />
                                </div>
                            </div>

                            <!-- Texto del brand con efecto de luz blanca -->
                            <!-- <div class="text-center">
                                <h1 class="text-white font-bold text-2xl md:text-3xl lg:text-4xl tracking-wide transition-all duration-300 group-hover:text-white/90 group-hover:drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]">ALFA</h1>
                                <p class="text-blue-200 text-sm md:text-base lg:text-lg font-semibold transition-all duration-300 group-hover:text-blue-100 group-hover:drop-shadow-[0_0_8px_rgba(255,255,255,0.4)]">Analytics</p>
                            </div> -->
                        </div>
                    </div>

                    <!-- Título principal -->
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight">
                        Encuentra tu
                        <span class="block text-[#FAB90E]">Propiedad Ideal</span>
                    </h1>

                    <p class="text-lg md:text-xl text-blue-100 max-w-2xl mx-auto leading-relaxed">
                        Explora propiedades en toda Bolivia con nuestro buscador avanzado. Casas, departamentos, terrenos y más.
                    </p>
                </div>
            </div>

            <!-- Módulo de Búsqueda -->
            <div class="px-4 sm:px-6 lg:px-8 py-12 flex items-center justify-center min-h-[400px]">
                <div class="w-full flex justify-center">
                    <PropertySearchFilters :options="filter_options" />
                </div>
            </div>

            <!-- Información adicional sutil -->
            <div class="px-4 sm:px-6 lg:px-8 pb-16">
                <div class="max-w-4xl mx-auto">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                            <div class="text-2xl md:text-3xl font-bold text-[#FAB90E] mb-1">+1000</div>
                            <div class="text-xs md:text-sm text-blue-100">Propiedades</div>
                        </div>
                        <div class="text-center p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                            <div class="text-2xl md:text-3xl font-bold text-[#FAB90E] mb-1">+20</div>
                            <div class="text-xs md:text-sm text-blue-100">Agencias</div>
                        </div>
                        <div class="text-center p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                            <div class="text-2xl md:text-3xl font-bold text-[#FAB90E] mb-1">24/7</div>
                            <div class="text-xs md:text-sm text-blue-100">Disponible</div>
                        </div>
                        <div class="text-center p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                            <div class="text-2xl md:text-3xl font-bold text-[#FAB90E] mb-1">Gratis</div>
                            <div class="text-xs md:text-sm text-blue-100">Sin costo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animación blob para los elementos decorativos */
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Animación de glow blanco para el logo */
@keyframes white-glow {
    0%, 100% {
        opacity: 0.2;
        transform: scale(1);
    }
    50% {
        opacity: 0.35;
        transform: scale(1.05);
    }
}

.animate-white-glow {
    animation: white-glow 4s ease-in-out infinite;
}

/* Animación del borde pulsante blanco */
@keyframes pulse-border {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 0.15;
    }
}

.animate-pulse-border {
    animation: pulse-border 0.5s ease-out forwards;
}

/* Efecto de glassmorphism mejorado */
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.backdrop-blur-md {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/* Transiciones suaves para elementos interactivos */
* {
    transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}
</style>
