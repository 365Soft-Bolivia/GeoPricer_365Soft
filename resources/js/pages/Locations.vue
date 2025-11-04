<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ubicaciones } from '@/routes';
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
        <div class="min-h-[calc(100vh-200px)] flex items-center justify-center p-6">
            <div class="w-full max-w-6xl">
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">
                        Gestión de Ubicaciones
                    </h1>
                    <p class="text-lg text-gray-600">
                        Administra las ubicaciones geográficas de tus propiedades
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Botón: Ver Mapa -->
                    <button
                        @click="navigateTo('/ubicaciones/mapa')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-8 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-blue-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        
                        <div class="relative z-10">
                            <div class="mb-6 flex justify-center">
                                <div class="rounded-full bg-white/20 p-6 backdrop-blur-sm">
                                    <MapPin :size="48" :stroke-width="2" />
                                </div>
                            </div>
                            
                            <h2 class="mb-3 text-2xl font-bold">Ver Mapa</h2>
                            <p class="text-blue-100">
                                Visualiza todas las propiedades en el mapa interactivo
                            </p>
                            
                            <div class="mt-6 flex items-center justify-center text-sm font-semibold">
                                <span>Explorar</span>
                                <svg class="ml-2 h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    <!-- Botón: Asignar Ubicación -->
                    <button
                        @click="navigateTo('/ubicaciones/asignar')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-green-600 p-8 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        
                        <div class="relative z-10">
                            <div class="mb-6 flex justify-center">
                                <div class="rounded-full bg-white/20 p-6 backdrop-blur-sm">
                                    <Plus :size="48" :stroke-width="2" />
                                </div>
                            </div>
                            
                            <h2 class="mb-3 text-2xl font-bold">Asignar Ubicación</h2>
                            <p class="text-green-100">
                                Asigna coordenadas geográficas a nuevas propiedades
                            </p>
                            
                            <div class="mt-6 flex items-center justify-center text-sm font-semibold">
                                <span>Agregar</span>
                                <svg class="ml-2 h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    <!-- Botón: Editar Ubicaciones -->
                    <button
                        @click="navigateTo('/ubicaciones/editar')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-8 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-400 to-purple-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        
                        <div class="relative z-10">
                            <div class="mb-6 flex justify-center">
                                <div class="rounded-full bg-white/20 p-6 backdrop-blur-sm">
                                    <Edit3 :size="48" :stroke-width="2" />
                                </div>
                            </div>
                            
                            <h2 class="mb-3 text-2xl font-bold">Editar Ubicaciones</h2>
                            <p class="text-purple-100">
                                Modifica o elimina ubicaciones existentes
                            </p>
                            
                            <div class="mt-6 flex items-center justify-center text-sm font-semibold">
                                <span>Administrar</span>
                                <svg class="ml-2 h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <div class="text-sm text-gray-600 mb-1">Total de Propiedades</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $page.props.productsConUbicacion?.length || 0 }}</div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <div class="text-sm text-gray-600 mb-1">Con Ubicación</div>
                        <div class="text-3xl font-bold text-green-600">{{ $page.props.productsConUbicacion?.length || 0 }}</div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <div class="text-sm text-gray-600 mb-1">Sin Ubicación</div>
                        <div class="text-3xl font-bold text-orange-600">{{ $page.props.productsSinUbicacion?.length || 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>