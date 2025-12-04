<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Search, MapPin, Home, DollarSign, Building } from 'lucide-vue-next';

interface Props {
    featured_properties: any[];
    stats: {
        total_propiedades: number;
        total_categorias: number;
        ubicaciones: number;
        clientes_satisfechos: number;
    };
    filter_options: {
        categorias: Record<string, string>;
        operaciones: Record<string, string>;
        rango_precios: Record<string, string>;
        ubicaciones: string[];
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: PublicLayout
});

const filters = ref({
    categoria: '',
    operacion: '',
    rango_precio: '',
    ubicacion: '',
    codigo: ''
});

const formatPrice = (price: string | number) => {
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(num);
};

const searchProperties = () => {
    const params: Record<string, string> = {};

    // Depuración: mostrar los filtros actuales
    console.log('Filtros seleccionados:', filters.value);

    Object.entries(filters.value).forEach(([key, value]) => {
        if (value && value !== '') {
            params[key] = value.toString();
            console.log(`Agregando parámetro: ${key} = ${value}`);
        }
    });

    console.log('Parámetros finales:', params);

    // Construir URL manualmente para asegurar que funcione
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        searchParams.append(key, value);
    });

    const finalUrl = '/propiedades' + (searchParams.toString() ? '?' + searchParams.toString() : '');
    console.log('URL final:', finalUrl);

    // Usar la URL construida manualmente
    router.get(finalUrl);
};

const getPropertyImage = (imagePath: string | null) => {
    if (!imagePath) {
        // Placeholder simple con gradient - SVG más corto para evitar errores
        return 'data:image/svg+xml,%3Csvg width="800" height="600" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="800" height="600" fill="%23ddd"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" fill="%23999"%3ESin Imagen%3C/text%3E%3C/svg%3E';
    }
    return `/storage/${imagePath}`;
};

const getOperacionLabel = (operacion: string) => {
    return props.filter_options.operaciones[operacion] || operacion;
};
</script>

<template>
    <div>
        <!-- Hero Section con Filtro Integrado -->
        <section class="relative bg-cover bg-center bg-no-repeat text-white" :style="{ backgroundImage: 'url(/Fondo%20Inmo.jpeg)' }">
            <!-- Overlay para mejor legibilidad -->
            <div class="absolute inset-0 bg-gradient-to-br from-black/40 via-black/30 to-black/20"></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
                <!-- Hero Content -->
                <div class="text-center mb-12">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight">
                        Encuentra tu
                        <span class="text-yellow-300"> hogar ideal</span>
                    </h1>
                    <p class="text-xl md:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto leading-relaxed">
                        Las mejores propiedades en Bolivia con la confianza y experiencia que mereces
                    </p>
                </div>

                <!-- Formulario de Filtro Rápido -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 md:p-8 text-gray-800 dark:text-white">
                    <h3 class="text-2xl font-bold mb-6 text-center text-gray-800 dark:text-white">
                        Busca tu propiedad
                    </h3>

                    <form @submit.prevent="searchProperties" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Categoría -->
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Categoría
                                </label>
                                <select
                                    v-model="filters.categoria"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white transition-all"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="(name, id) in filter_options.categorias" :key="id" :value="id">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Operación -->
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Operación
                                </label>
                                <select
                                    v-model="filters.operacion"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white transition-all"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="(name, key) in filter_options.operaciones" :key="key" :value="key">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Rango de Precios -->
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Rango de Precios
                                </label>
                                <select
                                    v-model="filters.rango_precio"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white transition-all"
                                >
                                    <option value="">Cualquiera</option>
                                    <option v-for="(name, key) in filter_options.rango_precios" :key="key" :value="key">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Ubicación -->
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Ubicación
                                </label>
                                <select
                                    v-model="filters.ubicacion"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white transition-all"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="ubicacion in filter_options.ubicaciones" :key="ubicacion" :value="ubicacion">
                                        {{ ubicacion }}
                                    </option>
                                </select>
                            </div>

                            <!-- Código -->
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Código
                                </label>
                                <input
                                    v-model="filters.codigo"
                                    type="text"
                                    placeholder="Ej: PRO001"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400 transition-all"
                                >
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg"
                            >
                                <Search class="w-5 h-5 mr-2" />
                                Buscar Propiedades
                            </button>

                            <Link
                                href="/propiedades"
                                class="inline-flex items-center justify-center px-8 py-4 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-all transform hover:scale-105 shadow-lg"
                            >
                                <Home class="w-5 h-5 mr-2" />
                                Ver Todo el Catálogo
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-5xl font-bold text-blue-600 mb-2">{{ stats.total_propiedades }}+</div>
                        <div class="text-gray-600 dark:text-gray-400 font-medium">Propiedades</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold text-blue-600 mb-2">{{ stats.total_categorias }}+</div>
                        <div class="text-gray-600 dark:text-gray-400 font-medium">Categorías</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold text-blue-600 mb-2">{{ stats.ubicaciones }}+</div>
                        <div class="text-gray-600 dark:text-gray-400 font-medium">Ubicaciones</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold text-blue-600 mb-2">{{ stats.clientes_satisfechos }}+</div>
                        <div class="text-gray-600 dark:text-gray-400 font-medium">Clientes Satisfechos</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quiénes Somos -->
        <section class="py-20 bg-gray-50 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        Quiénes Somos
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Somos tu aliado de confianza en el mundo inmobiliario, conectando personas con sus hogares ideales
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                            Tu confianza es nuestro mejor activo
                        </h3>
                        <div class="space-y-4 text-gray-600 dark:text-gray-300 leading-relaxed">
                            <p>
                                Con más de una década de experiencia en el mercado boliviano, hemos ayudado a miles de familias
                                a encontrar el lugar perfecto para llamar hogar. Nuestra misión es simple: facilitar el proceso
                                de compra, venta y alquiler de propiedades con transparencia, profesionalismo y atención personalizada.
                            </p>
                            <p>
                                Contamos con un equipo de expertos dedicados que conocen profundamente cada localidad,
                                sus ventajas y potencial. Esto nos permite ofrecerte no solo una propiedad, sino un estilo de vida
                                que se adapte a tus necesidades y aspiraciones.
                            </p>
                            <div class="grid grid-cols-2 gap-6 mt-8">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                                        <Building class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">10+ Años</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Experiencia</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                                        <Home class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">1000+</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Propiedades</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="rounded-2xl shadow-2xl w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <div class="text-center">
                                <Building class="w-16 h-16 text-blue-600 mx-auto mb-4" />
                                <p class="text-gray-600 font-medium">Nuestra Oficina</p>
                            </div>
                        </div>
                        <div class="absolute -bottom-6 -left-6 bg-blue-600 text-white p-6 rounded-xl shadow-xl">
                            <div class="text-3xl font-bold">24/7</div>
                            <div class="text-sm">Soporte disponible</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Propiedades Destacadas -->
        <section v-if="featured_properties.length > 0" class="py-20 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        Propiedades Destacadas
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300">
                        Descubre nuestras selecciones especiales del momento
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="property in featured_properties"
                        :key="property.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all transform hover:scale-105 group"
                    >
                        <!-- Imagen -->
                        <div class="relative h-48 overflow-hidden">
                            <img
                                :src="getPropertyImage(property.imagen_principal)"
                                :alt="property.name"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                            >
                            <div class="absolute top-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                {{ getOperacionLabel(property.operacion) }}
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ property.name }}
                            </h3>
                            <div class="text-2xl font-bold text-blue-600 mb-4">
                                {{ formatPrice(property.price) }}
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                <div v-if="property.ambientes" class="flex items-center">
                                    <Home class="w-4 h-4 mr-1" />
                                    {{ property.ambientes }} ambientes
                                </div>
                                <div v-if="property.habitaciones" class="flex items-center">
                                    <Home class="w-4 h-4 mr-1" />
                                    {{ property.habitaciones }} hab.
                                </div>
                                <div v-if="property.superficie_construida" class="flex items-center">
                                    <MapPin class="w-4 h-4 mr-1" />
                                    {{ property.superficie_construida }}m²
                                </div>
                                <div v-if="property.direccion" class="flex items-center">
                                    <MapPin class="w-4 h-4 mr-1" />
                                    {{ property.direccion }}
                                </div>
                            </div>

                            <!-- <Link
                                :href="propiedad.show.url(property.id)"
                                class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                Ver Detalles
                            </Link> -->
                        </div>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <Link
                        href="/propiedades"
                        class="inline-flex items-center px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg"
                    >
                        Ver Todas las Propiedades
                        <Home class="w-5 h-5 ml-2" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="py-20 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
            <div class="mx-auto max-w-4xl text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold mb-6">
                    ¿Listo para encontrar tu próximo hogar?
                </h2>
                <p class="text-xl mb-8 text-blue-100">
                    Contáctanos hoy mismo y recibe asesoría personalizada sin compromiso
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        href="/propiedades"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all transform hover:scale-105"
                    >
                        <Search class="w-5 h-5 mr-2" />
                        Explorar Propiedades
                    </Link>
                    <Link
                        href="/contacto"
                        class="inline-flex items-center justify-center px-8 py-4 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-blue-600 transition-all"
                    >
                        Contactar Asesor
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Animaciones personalizadas si es necesario */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}
</style>