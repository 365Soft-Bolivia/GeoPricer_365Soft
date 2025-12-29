<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Search, MapPin, Home, DollarSign, Building, FileCheck, Calculator, TrendingUp, Award, Clock, CheckCircle, ArrowRight } from 'lucide-vue-next';

interface Props {
    stats: {
        total_avaluos: number;
        años_experiencia: number;
        clientes_corporativos: number;
        precisión: number;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: PublicLayout
});

const avaluoForm = ref({
    tipo_propiedad: '',
    ubicacion: '',
    contacto: '',
    email: '',
    telefono: ''
});

const formatPrice = (price: string | number) => {
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(num);
};

const submitAvaluo = () => {
    // Aquí iría la lógica para enviar el formulario
    alert('Gracias por tu solicitud. Nos pondremos en contacto pronto.');
    avaluoForm.value = {
        tipo_propiedad: '',
        ubicacion: '',
        contacto: '',
        email: '',
        telefono: ''
    };
};
</script>

<template>
    <div>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 text-white overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-blue-400 rounded-full filter blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-400 rounded-full filter blur-3xl translate-x-1/2 translate-y-1/2"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Hero Content -->
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
                            <Award :size="20" class="text-yellow-300" />
                            <span class="text-sm font-medium">Avalúos Certificados desde 2005</span>
                        </div>

                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                            Avalúos de Propiedades
                            <span class="block text-yellow-300">Profesionales y Confiables</span>
                        </h1>

                        <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                            Obtén el valor real de tu propiedad con nuestros servicios de avalúo certificados.
                            Más de {{ stats.años_experiencia }} años de experiencia en el mercado boliviano.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <Link
                                href="#contacto"
                                class="inline-flex items-center justify-center px-8 py-4 bg-yellow-500 text-gray-900 font-bold rounded-lg hover:bg-yellow-400 transition-all transform hover:scale-105 shadow-xl"
                            >
                                <Calculator :size="20" class="mr-2" />
                                Solicitar Avalúo
                                <ArrowRight :size="20" class="ml-2" />
                            </Link>

                            <Link
                                href="/mapa-propiedades"
                                class="inline-flex items-center justify-center px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg hover:bg-white/20 transition-all border border-white/30"
                            >
                                <MapPin :size="20" class="mr-2" />
                                Ver Mapa de Avalúos
                            </Link>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20">
                        <h3 class="text-2xl font-bold mb-6 text-center">Nuestros Logros</h3>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="text-center p-4 bg-white/5 rounded-xl">
                                <div class="text-4xl font-bold text-yellow-300 mb-2">{{ stats.total_avaluos }}+</div>
                                <div class="text-sm text-blue-100">Avalúos Realizados</div>
                            </div>
                            <div class="text-center p-4 bg-white/5 rounded-xl">
                                <div class="text-4xl font-bold text-yellow-300 mb-2">{{ stats.años_experiencia }}</div>
                                <div class="text-sm text-blue-100">Años de Experiencia</div>
                            </div>
                            <div class="text-center p-4 bg-white/5 rounded-xl">
                                <div class="text-4xl font-bold text-yellow-300 mb-2">{{ stats.clientes_corporativos }}+</div>
                                <div class="text-sm text-blue-100">Clientes Corporativos</div>
                            </div>
                            <div class="text-center p-4 bg-white/5 rounded-xl">
                                <div class="text-4xl font-bold text-yellow-300 mb-2">{{ stats.precisión }}%</div>
                                <div class="text-sm text-blue-100">Precisión</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Servicios de Avalúo -->
        <section class="py-20 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Nuestros Servicios de Avalúo
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Ofrecemos servicios profesionales de avalúo para todo tipo de propiedades
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Avalúo Residencial -->
                    <div class="group bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <Home :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo Residencial
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúo de casas, departamentos y condominios para compra, venta o hipoteca.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-blue-600" />
                                Casas y villas
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-blue-600" />
                                Departamentos
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-blue-600" />
                                Condominios
                            </li>
                        </ul>
                    </div>

                    <!-- Avalúo Comercial -->
                    <div class="group bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <Building :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo Comercial
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúo de oficinas, locales comerciales y galpones para empresas y negocios.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-green-600" />
                                Oficinas
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-green-600" />
                                Locales comerciales
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-green-600" />
                                Galpones y bodegas
                            </li>
                        </ul>
                    </div>

                    <!-- Avalúo de Terrenos -->
                    <div class="group bg-gradient-to-br from-purple-50 to-purple-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <MapPin :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo de Terrenos
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúo de terrenos urbanos, rurales y lotes para desarrollo inmobiliario.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-purple-600" />
                                Terrenos urbanos
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-purple-600" />
                                Terrenos rurales
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-purple-600" />
                                Lotes en desarrollo
                            </li>
                        </ul>
                    </div>

                    <!-- Avalúo para Hipoteca -->
                    <div class="group bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <FileCheck :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo Hipotecario
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúos certificados para trámites hipotecarios y garantías bancarias.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-yellow-600" />
                                Créditos hipotecarios
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-yellow-600" />
                                Garantías bancarias
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-yellow-600" />
                                Refinanciamiento
                            </li>
                        </ul>
                    </div>

                    <!-- Avalúo Fiscal -->
                    <div class="group bg-gradient-to-br from-red-50 to-red-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <TrendingUp :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo Fiscal
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúos para impuestos, declaraciones y fines fiscales certificados.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-red-600" />
                                Impuestos prediales
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-red-600" />
                                Declaraciones juradas
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-red-600" />
                                Litigios fiscales
                            </li>
                        </ul>
                    </div>

                    <!-- Avalúo Judicial -->
                    <div class="group bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <Award :size="32" class="text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Avalúo Judicial
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Avalúos periciales para procesos judiciales, divorcios y herencias.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-indigo-600" />
                                Divisiones de bienes
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-indigo-600" />
                                Herencias
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle :size="16" class="text-indigo-600" />
                                Litigios
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proceso -->
        <section class="py-20 bg-gray-50 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Nuestro Proceso de Avalúo
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Un proceso simple y transparente en 4 pasos
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Paso 1 -->
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg relative z-10">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl mb-6">
                                1
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                                Solicitud
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Completa el formulario con los datos de tu propiedad y recibe respuesta en 24 horas.
                            </p>
                        </div>
                        <div class="hidden lg:block absolute top-1/2 -right-4 w-8 h-0.5 bg-blue-600 z-0"></div>
                    </div>

                    <!-- Paso 2 -->
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg relative z-10">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl mb-6">
                                2
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                                Inspección
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Nuestro perito visita la propiedad para evaluar estado, ubicación y características.
                            </p>
                        </div>
                        <div class="hidden lg:block absolute top-1/2 -right-4 w-8 h-0.5 bg-blue-600 z-0"></div>
                    </div>

                    <!-- Paso 3 -->
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg relative z-10">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl mb-6">
                                3
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                                Análisis
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Analizamos el mercado y comparamos con propiedades similares para determinar el valor.
                            </p>
                        </div>
                        <div class="hidden lg:block absolute top-1/2 -right-4 w-8 h-0.5 bg-blue-600 z-0"></div>
                    </div>

                    <!-- Paso 4 -->
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg relative z-10">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl mb-6">
                                4
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                                Informe
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Recibe tu avalúo certificado con respaldo legal y validez oficial.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Formulario de Contacto -->
        <section id="contacto" class="py-20 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Info -->
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                            Solicita tu Avalúo
                        </h2>
                        <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                            Completa el formulario y nos pondremos en contacto contigo en menos de 24 horas hábiles.
                        </p>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <Clock :size="24" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">Respuesta Rápida</h3>
                                    <p class="text-gray-600 dark:text-gray-300">Respuesta en menos de 24 horas hábiles</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <Award :size="24" class="text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">Peritos Certificados</h3>
                                    <p class="text-gray-600 dark:text-gray-300">Equipo con certificación oficial</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <FileCheck :size="24" class="text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">Informe Certificado</h3>
                                    <p class="text-gray-600 dark:text-gray-300">Documentación con respaldo legal</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 shadow-xl">
                        <form @submit.prevent="submitAvaluo" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Tipo de Propiedad *
                                </label>
                                <select
                                    v-model="avaluoForm.tipo_propiedad"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                                >
                                    <option value="">Selecciona una opción</option>
                                    <option value="residencial">Residencial (Casa/Depto)</option>
                                    <option value="comercial">Comercial (Oficina/Local)</option>
                                    <option value="terreno">Terreno</option>
                                    <option value="industrial">Industrial (Galpón/Bodega)</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Ubicación de la Propiedad *
                                </label>
                                <input
                                    v-model="avaluoForm.ubicacion"
                                    type="text"
                                    required
                                    placeholder="Ej: Calle, Número, Zona, Ciudad"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Nombre Completo *
                                </label>
                                <input
                                    v-model="avaluoForm.contacto"
                                    type="text"
                                    required
                                    placeholder="Tu nombre completo"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Email *
                                </label>
                                <input
                                    v-model="avaluoForm.email"
                                    type="email"
                                    required
                                    placeholder="tu@email.com"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    Teléfono *
                                </label>
                                <input
                                    v-model="avaluoForm.telefono"
                                    type="tel"
                                    required
                                    placeholder="+591 70000000"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400"
                                >
                            </div>

                            <button
                                type="submit"
                                class="w-full inline-flex items-center justify-center px-8 py-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg"
                            >
                                <Calculator :size="20" class="mr-2" />
                                Solicitar Avalúo Ahora
                            </button>
                        </form>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4 text-center">
                            Al enviar el formulario, aceptas nuestra política de privacidad.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="py-20 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl font-bold mb-6">
                    ¿Listo para conocer el valor de tu propiedad?
                </h2>
                <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto">
                    Obtén un avalúo profesional con más de {{ stats.años_experiencia }} años de experiencia y certificación oficial.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a
                        href="#contacto"
                        class="inline-flex items-center justify-center px-8 py-4 bg-yellow-500 text-gray-900 font-bold rounded-lg hover:bg-yellow-400 transition-all transform hover:scale-105 shadow-xl"
                    >
                        <Calculator :size="20" class="mr-2" />
                        Solicitar Avalúo Gratis
                    </a>
                    <Link
                        href="/mapa-propiedades"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg hover:bg-white/20 transition-all border border-white/30"
                    >
                        <MapPin :size="20" class="mr-2" />
                        Ver Mapa Interactivo
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
