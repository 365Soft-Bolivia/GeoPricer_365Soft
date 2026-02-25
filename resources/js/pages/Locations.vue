<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { admin } from '@/routes-custom';

const { ubicaciones } = admin;
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { MapPin, Plus, Edit3 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ubicaciones',
        href: ubicaciones().url,
    },
];

const navigateTo = (route: string) => {
    router.visit(route);
};
</script>

<template>
    <Head title="Gestión de Ubicaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-[calc(100vh-200px)] bg-[#F5F5F5] p-8">
            <div class="w-full max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="bg-white rounded-2xl shadow-lg p-10 mb-10 border-l-4" style="border-color: #233C7A;">
                    <h1 class="text-5xl font-bold mb-4" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #212121;">
                        Gestión de Ubicaciones
                    </h1>
                    <p class="text-xl" style="color: #212121; opacity: 0.7;">
                        Administra las ubicaciones geográficas de tus propiedades
                    </p>
                </div>

                <!-- Acciones Principales -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-6" style="font-family: 'Montserrat', sans-serif; font-weight: 600; color: #212121;">
                        Acciones Disponibles
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Botón: Ver Mapa -->
                        <button
                            @click="navigateTo(ubicaciones.mapa().url)"
                            class="group relative overflow-hidden rounded-2xl p-10 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:-translate-y-1"
                            style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);"
                        >
                            <div class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: linear-gradient(135deg, #2d4a8f 0%, #233C7A 100%);"></div>

                            <div class="relative z-10">
                                <div class="mb-8 flex justify-center">
                                    <div class="rounded-full p-8 backdrop-blur-sm" style="background: rgba(250, 185, 14, 0.2);">
                                        <MapPin :size="56" :stroke-width="2.5" style="color: #FAB90E;" />
                                    </div>
                                </div>

                                <h2 class="mb-4 text-3xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Ver Mapa</h2>
                                <p class="text-lg mb-6" style="color: rgba(255, 255, 255, 0.9);">
                                    Visualiza todas las propiedades en el mapa interactivo
                                </p>

                                <div class="flex items-center justify-center text-base font-semibold transition-transform duration-300 group-hover:translate-x-2" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                                    <span>Explorar Mapa</span>
                                    <svg class="ml-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </button>

                        <!-- Botón: Asignar Ubicación -->
                        <button
                            @click="navigateTo(ubicaciones.asignar().url)"
                            class="group relative overflow-hidden rounded-2xl p-10 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:-translate-y-1"
                            style="background: linear-gradient(135deg, #E0081D 0%, #b30613 100%);"
                        >
                            <div class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: linear-gradient(135deg, #f01228 0%, #E0081D 100%);"></div>

                            <div class="relative z-10">
                                <div class="mb-8 flex justify-center">
                                    <div class="rounded-full p-8 backdrop-blur-sm" style="background: rgba(250, 185, 14, 0.2);">
                                        <Plus :size="56" :stroke-width="2.5" style="color: #FAB90E;" />
                                    </div>
                                </div>

                                <h2 class="mb-4 text-3xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Asignar Ubicación</h2>
                                <p class="text-lg mb-6" style="color: rgba(255, 255, 255, 0.9);">
                                    Asigna coordenadas geográficas a nuevas propiedades
                                </p>

                                <div class="flex items-center justify-center text-base font-semibold transition-transform duration-300 group-hover:translate-x-2" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                                    <span>Asignar</span>
                                    <svg class="ml-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </button>

                        <!-- Botón: Editar Ubicaciones -->
                        <button
                            @click="navigateTo(ubicaciones.editar().url)"
                            class="group relative overflow-hidden rounded-2xl p-10 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:-translate-y-1"
                            style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);"
                        >
                            <div class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: linear-gradient(135deg, #2d4a8f 0%, #233C7A 100%);"></div>

                            <div class="relative z-10">
                                <div class="mb-8 flex justify-center">
                                    <div class="rounded-full p-8 backdrop-blur-sm" style="background: rgba(250, 185, 14, 0.2);">
                                        <Edit3 :size="56" :stroke-width="2.5" style="color: #FAB90E;" />
                                    </div>
                                </div>

                                <h2 class="mb-4 text-3xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Editar Ubicaciones</h2>
                                <p class="text-lg mb-6" style="color: rgba(255, 255, 255, 0.9);">
                                    Modifica o elimina ubicaciones existentes
                                </p>

                                <div class="flex items-center justify-center text-base font-semibold transition-transform duration-300 group-hover:translate-x-2" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                                    <span>Administrar</span>
                                    <svg class="ml-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Estadísticas Section -->
                <div>
                    <h2 class="text-2xl font-bold mb-6" style="font-family: 'Montserrat', sans-serif; font-weight: 600; color: #212121;">
                        Estadísticas
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Total de Propiedades -->
                        <div class="bg-white rounded-2xl shadow-lg p-8 border-t-4 hover:shadow-xl transition-shadow" style="border-color: #233C7A;">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-base font-medium" style="color: #212121; opacity: 0.7;">Total de Propiedades</div>
                                <div class="rounded-full p-3" style="background: rgba(35, 60, 122, 0.1);">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #233C7A;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-5xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #233C7A;">
                                {{ $page.props.productsConUbicacion?.length || 0 }}
                            </div>
                        </div>

                        <!-- Con Ubicación -->
                        <div class="bg-white rounded-2xl shadow-lg p-8 border-t-4 hover:shadow-xl transition-shadow" style="border-color: #10b981;">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-base font-medium" style="color: #212121; opacity: 0.7;">Con Ubicación</div>
                                <div class="rounded-full p-3" style="background: rgba(16, 185, 129, 0.1);">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #10b981;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-5xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #10b981;">
                                {{ $page.props.productsConUbicacion?.length || 0 }}
                            </div>
                        </div>

                        <!-- Sin Ubicación -->
                        <div class="bg-white rounded-2xl shadow-lg p-8 border-t-4 hover:shadow-xl transition-shadow" style="border-color: #FAB90E;">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-base font-medium" style="color: #212121; opacity: 0.7;">Sin Ubicación</div>
                                <div class="rounded-full p-3" style="background: rgba(250, 185, 14, 0.1);">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #FAB90E;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-5xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #FAB90E;">
                                {{ $page.props.productsSinUbicacion?.length || 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>