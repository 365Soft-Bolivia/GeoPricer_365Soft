<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicAuthenticatedLayout from '@/layouts/PublicAuthenticatedLayout.vue';
import {
    Heart,
    Users,
    TrendingUp,
    Target,
    Award,
    Building,
    MapPin,
    Calendar,
    Eye,
    Handshake,
    Lightbulb,
    Globe
} from 'lucide-vue-next';
import publicRoutes from '@/routes/public';

defineOptions({
    layout: PublicAuthenticatedLayout
});

// Datos dinámicos que podrían venir del CMS en el futuro
const companyData = {
    historia: {
        titulo: 'Nuestra Historia',
        contenido: '365Soft nació en 2014 con una visión clara: revolucionar el mercado inmobiliario boliviano a través de la tecnología. Nuestro fundador, tras experimentar las dificultades y procesos manuales en la búsqueda de propiedades, decidió crear una solución que hiciera este proceso más eficiente, transparente y accesible para todos.',
        momentoClave: 'El momento "eureka" llegó durante una frustrante búsqueda de propiedad personal, donde se identificaron múltiples oportunidades de mejora en el sistema tradicional.',
        imagenFondo: '/Fondo%20Inmo.jpeg'
    },
    trayectoria: {
        titulo: 'Nuestra Trayectoria',
        equipo: [
            {
                nombre: 'Founder & CEO',
                descripcion: 'Líder con más de 15 años de experiencia en el sector inmobiliario y tecnológico, apasionado por innovar y mejorar la experiencia de los clientes.',
                icono: Award
            },
            {
                nombre: 'Equipo Técnico',
                descripcion: 'Desarrolladores y diseñadores especializados en crear soluciones digitales innovadoras que marcan la diferencia.',
                icono: Users
            },
            {
                nombre: 'Asesores Inmobiliarios',
                descripcion: 'Profesionales con amplia experiencia en el mercado local, comprometidos con encontrar la propiedad perfecta para cada cliente.',
                icono: Handshake
            }
        ],
        logros: [
            { numero: '10+', etiqueta: 'Años de Experiencia' },
            { numero: '1000+', etiqueta: 'Propiedades Gestionadas' },
            { numero: '5000+', etiqueta: 'Clientes Satisfechos' },
            { numero: '50+', etiqueta: 'Colaboradores' }
        ]
    },
    evolucion: {
        titulo: 'Nuestra Evolución',
        hitos: [
            {
                año: '2014',
                titulo: 'Inicios',
                descripcion: 'Comenzamos como una pequeña consultora inmobiliaria con un equipo de 3 personas.',
                icono: Calendar
            },
            {
                año: '2017',
                titulo: 'Primera Transformación Digital',
                descripcion: 'Lanzamos nuestra primera plataforma digital, modernizando el proceso de búsqueda de propiedades.',
                icono: TrendingUp
            },
            {
                año: '2020',
                titulo: 'Expansión y Tecnología',
                descripcion: 'Implementamos sistemas avanzados de gestión y expandimos nuestras operaciones a nivel nacional.',
                icono: Globe
            },
            {
                año: '2024',
                titulo: 'Innovación Continua',
                descripcion: 'Lanzamos nuestra plataforma más moderna con IA, mapas interactivos y experiencia de usuario premium.',
                icono: Lightbulb
            }
        ]
    },
    misionVision: {
        mision: {
            titulo: 'Nuestra Misión',
            contenido: 'Facilitar y transformar la experiencia inmobiliaria en Bolivia, conectando personas con sus hogares ideales a través de tecnología innovadora, transparencia total y un servicio excepcional que genere confianza y satisfacción duradera.',
            icono: Heart
        },
        vision: {
            titulo: 'Nuestra Visión',
            contenido: 'Convertirnos en la plataforma inmobiliaria líder y más confiable de Bolivia, reconocida por nuestra innovación tecnológica, integridad y compromiso con la satisfacción del cliente, expandiendo nuestro modelo de éxito a mercados internacionales.',
            icono: Target
        },
        valores: [
            {
                titulo: 'Integridad',
                descripcion: 'Actuamos con honestidad y transparencia en todas nuestras interacciones.',
                icono: Eye
            },
            {
                titulo: 'Innovación',
                descripcion: 'Buscamos constantemente nuevas formas de mejorar y servir mejor a nuestros clientes.',
                icono: Lightbulb
            },
            {
                titulo: 'Excelencia',
                descripcion: 'Nos comprometemos con los más altos estándares de calidad en todo lo que hacemos.',
                icono: Award
            },
            {
                titulo: 'Confianza',
                descripcion: 'Construimos relaciones duraderas basadas en la confiabilidad y el respeto mutuo.',
                icono: Handshake
            }
        ]
    }
};
</script>

<template>
    <div>
        <!-- Hero Section - Historia y Porqué de la Empresa -->
        <section class="relative bg-cover bg-center bg-no-repeat text-white" :style="{ backgroundImage: `url(${companyData.historia.imagenFondo})` }">
            <!-- Overlay para mejor legibilidad -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#233C7A]/90 via-[#233C7A]/80 to-black/50"></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="text-center max-w-4xl mx-auto">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-full mb-8">
                        <Heart class="w-10 h-10 text-[#FAB90E]" />
                    </div>
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-8 leading-tight" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                        {{ companyData.historia.titulo }}
                    </h1>
                    <div class="text-xl md:text-2xl text-blue-100 space-y-6 leading-relaxed">
                        <p>{{ companyData.historia.contenido }}</p>
                        <div class="bg-white/10 backdrop-blur-sm border-l-4 border-[#FAB90E] p-6 my-8 text-left">
                            <p class="italic text-lg">"{{ companyData.historia.momentoClave }}"</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección 2: Trayectoria y Equipo Fundador -->
        <section class="py-20 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-[#F5F5F5] dark:bg-[#233C7A]/30 rounded-full mb-6">
                        <Users class="w-8 h-8 text-[#233C7A] dark:text-[#FAB90E]" />
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-6" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                        {{ companyData.trayectoria.titulo }}
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto subtitle-elegant">
                        El talento y la experiencia que definen nuestro compromiso con la excelencia
                    </p>
                </div>

                <!-- Estadísticas de Logros -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                    <div
                        v-for="(logro, index) in companyData.trayectoria.logros"
                        :key="index"
                        class="text-center"
                    >
                        <div class="bg-gradient-to-br from-[#233C7A] to-[#E0081D] text-white rounded-2xl p-6 mb-4">
                            <div class="text-3xl md:text-4xl font-bold">{{ logro.numero }}</div>
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 font-medium">{{ logro.etiqueta }}</div>
                    </div>
                </div>

                <!-- Equipo -->
                <div class="grid md:grid-cols-3 gap-8">
                    <div
                        v-for="(miembro, index) in companyData.trayectoria.equipo"
                        :key="index"
                        class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 text-center hover:shadow-lg transition-all"
                    >
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-[#F5F5F5] dark:bg-[#233C7A]/30 rounded-full mb-6">
                            <component :is="miembro.icono" class="w-8 h-8 text-[#233C7A] dark:text-[#FAB90E]" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                            {{ miembro.nombre }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ miembro.descripcion }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección 3: Evolución de la Empresa -->
        <section class="py-20 bg-gray-50 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-[#F5F5F5] dark:bg-[#233C7A]/30 rounded-full mb-6">
                        <TrendingUp class="w-8 h-8 text-[#10b981] dark:text-[#FAB90E]" />
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-6" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                        {{ companyData.evolucion.titulo }}
                    </h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto subtitle-elegant">
                        Los hitos que han moldeado nuestro camino hacia la excelencia
                    </p>
                </div>

                <!-- Línea de Tiempo -->
                <div class="relative">
                    <!-- Línea central -->
                    <div class="hidden md:block absolute left-1/2 transform -translate-x-1/2 w-1 h-full bg-gradient-to-b from-[#233C7A] to-[#10b981]"></div>

                    <div class="space-y-12">
                        <div
                            v-for="(hito, index) in companyData.evolucion.hitos"
                            :key="index"
                            class="relative flex flex-col md:flex-row items-center"
                            :class="index % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'"
                        >
                            <!-- Círculo en la línea -->
                            <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 bg-white dark:bg-gray-900 border-4 border-[#233C7A] rounded-full z-10"></div>

                            <!-- Contenido -->
                            <div class="w-full md:w-5/12" :class="index % 2 === 0 ? 'md:pr-8 md:text-right' : 'md:ml-auto md:pl-8'">
                                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-all">
                                    <div class="flex items-center" :class="index % 2 === 0 ? 'md:justify-end' : ''">
                                        <div class="bg-[#F5F5F5] dark:bg-[#233C7A]/30 p-3 rounded-lg" :class="index % 2 === 0 ? 'md:order-2 md:ml-4' : 'mr-4'">
                                            <component :is="hito.icono" class="w-6 h-6 text-[#233C7A] dark:text-[#FAB90E]" />
                                        </div>
                                        <div :class="index % 2 === 0 ? 'md:order-1' : ''">
                                            <div class="text-2xl font-bold text-[#233C7A] dark:text-[#FAB90E] mb-2">
                                                {{ hito.año }}
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ hito.titulo }}
                                            </h3>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 mt-4 leading-relaxed" :class="index % 2 === 0 ? 'md:text-right' : ''">
                                        {{ hito.descripcion }}
                                    </p>
                                </div>
                            </div>

                            <!-- Espacio vacío -->
                            <div class="hidden md:block w-2/12"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección 4: Misión y Visión -->
        <section class="py-20 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 mb-16">
                    <!-- Misión -->
                    <div class="bg-gradient-to-br from-[#F5F5F5] to-[#233C7A]/10 dark:from-[#233C7A]/20 dark:to-[#E0081D]/20 rounded-3xl p-8 lg:p-12">
                        <div class="flex items-center mb-8">
                            <div class="bg-[#233C7A] text-white p-4 rounded-2xl mr-6">
                                <component :is="companyData.misionVision.mision.icono" class="w-8 h-8" />
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                                {{ companyData.misionVision.mision.titulo }}
                            </h2>
                        </div>
                        <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ companyData.misionVision.mision.contenido }}
                        </p>
                    </div>

                    <!-- Visión -->
                    <div class="bg-gradient-to-br from-[#F5F5F5] to-[#10b981]/10 dark:from-[#10b981]/20 dark:to-[#FAB90E]/20 rounded-3xl p-8 lg:p-12">
                        <div class="flex items-center mb-8">
                            <div class="bg-[#10b981] text-white p-4 rounded-2xl mr-6">
                                <component :is="companyData.misionVision.vision.icono" class="w-8 h-8" />
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                                {{ companyData.misionVision.vision.titulo }}
                            </h2>
                        </div>
                        <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ companyData.misionVision.vision.contenido }}
                        </p>
                    </div>
                </div>

                <!-- Valores -->
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-4" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Nuestros Valores</h3>
                    <p class="text-xl text-gray-600 dark:text-gray-300 subtitle-elegant">
                        Los principios que guían cada una de nuestras acciones
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div
                        v-for="(valor, index) in companyData.misionVision.valores"
                        :key="index"
                        class="text-center group"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all group-hover:bg-[#F5F5F5] dark:group-hover:bg-[#233C7A]/20">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#233C7A] to-[#E0081D] rounded-full mb-6 group-hover:scale-110 transition-transform">
                                <component :is="valor.icono" class="w-8 h-8 text-white" />
                            </div>
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                {{ valor.titulo }}
                            </h4>
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ valor.descripcion }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Final -->
        <section class="py-20 bg-gradient-to-r from-blue-600 to-green-600 text-white">
            <div class="mx-auto max-w-4xl text-center px-4 sm:px-6 lg:px-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-full mb-8">
                    <Building class="w-10 h-10 text-yellow-300" />
                </div>
                <h2 class="text-4xl font-bold mb-6">
                    ¿Listo para ser parte de nuestra historia?
                </h2>
                <p class="text-xl mb-8 text-blue-100">
                    Únete a miles de clientes que han confiado en nosotros para encontrar su hogar ideal
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        :href="publicRoutes.mapa.propiedades.url()"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all transform hover:scale-105 shadow-lg"
                    >
                        <Building class="w-5 h-5 mr-2" />
                        Explorar Propiedades
                    </Link>
                    <Link
                        :href="publicRoutes.contacto.url()"
                        class="inline-flex items-center justify-center px-8 py-4 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-blue-600 transition-all"
                    >
                        <Handshake class="w-5 h-5 mr-2" />
                        Contáctanos
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Animaciones personalizadas */
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

/* Animaciones para la línea de tiempo */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Efectos hover mejorados */
.hover-scale {
    transition: transform 0.3s ease;
}

.hover-scale:hover {
    transform: scale(1.05);
}

/* Gradientes personalizados */
.gradient-blue-green {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Scroll animations */
.scroll-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.scroll-reveal.active {
    opacity: 1;
    transform: translateY(0);
}
</style>