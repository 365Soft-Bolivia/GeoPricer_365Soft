<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { publicRoutes } from '@/routes-custom'; // Fixed import name

const { home } = publicRoutes;

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-gray-800 dark:bg-gray-950 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">🏠 Tu propiedad, su valor real</h3>
                    <p class="text-gray-400 text-sm">
                        Tu mejor opción para encontrar la propiedad de tus sueños.
                    </p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Enlaces rápidos</h3>
                    <ul class="space-y-2 text-sm">
                        <li><Link :href="home()" class="text-gray-400 hover:text-white">Inicio</Link></li>
                        <!-- <li><Link href="/propiedades" class="text-gray-400 hover:text-white">Propiedades</Link></li> -->
                        <li><Link href="/sobre-nosotros" class="text-gray-400 hover:text-white">Sobre Nosotros</Link></li>
                        <li><Link href="/contacto" class="text-gray-400 hover:text-white">Contacto</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Contacto</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>📍 La Paz, Bolivia</li>
                        <li>📞 +591 123 4567</li>
                        <li>✉️ info@Tu propiedad_su valor real.com</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ currentYear }} Tu propiedad_su valor real. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</template>