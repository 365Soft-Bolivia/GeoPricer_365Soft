<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { MapPin, User, LogOut, AlertTriangle } from 'lucide-vue-next';
import publicRoutes from '@/routes/public';
import { useAppearance } from '@/composables/useAppearance';
import { router } from '@inertiajs/vue3';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogTrigger from '@/components/ui/alert-dialog/AlertDialogTrigger.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';

const mobileMenuOpen = ref(false);
const logoutDialogOpen = ref(false);
const { appearance } = useAppearance();
const page = usePage();

// Ya importamos publicHome directamente desde @/routes

// Información del usuario actual
const user = computed(() => page.props.auth?.user);

// Manejo de logout
const handleLogout = () => {
    // Cerrar el menú móvil si está abierto
    mobileMenuOpen.value = false;
    // Ejecutar el logout usando rutas públicas
    router.visit(publicRoutes.logout(), {
        method: 'post',
    });
};

// Abrir el diálogo de confirmación de logout
const showLogoutConfirmation = () => {
    logoutDialogOpen.value = true;
};

// Logo fijo siempre el mismo (sin cambio por tema)
const logoSrc = '/Recurso 1Analytics 1.png';
</script>

<template>
    <header class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] shadow-2xl sticky top-0 z-50 border-b-4 border-[#FAB90E]">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 justify-between items-center">
                <!-- Logo fijo (sin cambio por tema) -->
                <div class="flex items-center">
                    <Link :href="publicRoutes.home()" class="flex items-center group">
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
                    <!-- Información de usuario autenticado -->
                    <div v-if="user" class="ml-4 flex items-center gap-3 bg-white/10 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/20">
                        <div class="flex items-center gap-2">
                            <div class="bg-[#FAB90E] p-2 rounded-lg">
                                <User :size="16" class="text-[#212121]" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-white">
                                    {{ user?.name || 'Usuario' }}
                                </span>
                                <span class="text-xs text-[#FAB90E]">
                                    Sesión activa
                                </span>
                            </div>
                        </div>

                        <AlertDialog v-model:open="logoutDialogOpen">
                            <AlertDialogTrigger as-child>
                                <button
                                    class="flex items-center gap-2 bg-red-500/20 hover:bg-red-500/40 text-red-300 hover:text-red-200 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-300 border border-red-500/30 hover:border-red-500/50"
                                    title="Cerrar sesión"
                                >
                                    <LogOut :size="14" />
                                    <span>Salir</span>
                                </button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="bg-red-100 dark:bg-red-900/30 p-2 rounded-lg">
                                            <AlertTriangle :size="24" class="text-red-600 dark:text-red-400" />
                                        </div>
                                        <AlertDialogTitle>¿Cerrar sesión?</AlertDialogTitle>
                                    </div>
                                    <AlertDialogDescription>
                                        ¿Estás seguro que deseas cerrar tu sesión actual? Deberás ingresar tus credenciales nuevamente para acceder al sistema.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter class="gap-2">
                                    <AlertDialogCancel
                                        @click="logoutDialogOpen = false"
                                        class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                                    >
                                        Cancelar
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        @click="handleLogout"
                                        class="bg-red-600 hover:bg-red-700 text-white"
                                    >
                                        Cerrar Sesión
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>

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
                    <!-- Información de usuario autenticado móvil -->
                    <div v-if="user" class="mt-4 pt-4 border-t border-white/20">
                        <div class="flex items-center gap-3 bg-[#FAB90E]/20 px-4 py-3 rounded-xl border border-[#FAB90E]/30">
                            <div class="bg-[#FAB90E] p-2 rounded-lg">
                                <User :size="18" class="text-[#212121]" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-white">
                                    {{ user?.name || 'Usuario' }}
                                </span>
                                <span class="text-xs text-[#FAB90E]">
                                    Sesión activa
                                </span>
                            </div>
                        </div>

                        <AlertDialog v-model:open="logoutDialogOpen">
                            <AlertDialogTrigger as-child>
                                <button
                                    class="flex items-center justify-center gap-2 bg-red-500/20 hover:bg-red-500/40 text-red-300 hover:text-red-200 px-4 py-3 rounded-xl text-sm font-bold transition-all mt-2 border border-red-500/30 hover:border-red-500/50"
                                >
                                    <LogOut :size="18" />
                                    <span>Cerrar Sesión</span>
                                </button>
                            </AlertDialogTrigger>
                        </AlertDialog>
                    </div>

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