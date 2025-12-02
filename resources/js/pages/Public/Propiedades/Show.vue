<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { 
    ChevronLeft, ChevronRight, MapPin, Bed, Bath, Car, Home, 
    Maximize2, Calendar, Phone, MessageCircle, Share2, X,
    Building, Grid3x3, Ruler, Clock
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface ProductImage {
    id: number;
    image_path: string;
    original_name: string;
    is_primary: boolean;
    order: number;
}

interface Propiedad {
    id: number;
    name: string;
    codigo_inmueble: string;
    price: number;
    descripcion?: string;
    direccion?: string;
    superficie_util?: number;
    superficie_construida?: number;
    superficie_terreno?: number;
    ambientes?: number;
    habitaciones?: number;
    banos?: number;
    cocheras?: number;
    ano_construccion?: number;
    antiguedad?: number;
    operacion?: string;
    category?: string;
    estado?: string;
    orientacion?: string;
    images?: ProductImage[];
    location?: {
        latitude: number;
        longitude: number;
        address?: string;
    };
}

interface Props {
    propiedad: Propiedad;
    relacionadas?: Propiedad[];
}

const props = defineProps<Props>();

defineOptions({
    layout: PublicLayout
});

// Estado de la galería
const currentImageIndex = ref(0);
const showLightbox = ref(false);

// Imágenes ordenadas
const sortedImages = computed(() => {
    if (!props.propiedad.images?.length) return [];
    
    return [...props.propiedad.images].sort((a, b) => {
        if (a.is_primary && !b.is_primary) return -1;
        if (!a.is_primary && b.is_primary) return 1;
        return a.order - b.order;
    });
});

const getImageUrl = (imagePath: string) => `/storage/${imagePath}`;

const nextImage = () => {
    if (!sortedImages.value.length) return;
    currentImageIndex.value = (currentImageIndex.value + 1) % sortedImages.value.length;
};

const prevImage = () => {
    if (!sortedImages.value.length) return;
    currentImageIndex.value = (currentImageIndex.value - 1 + sortedImages.value.length) % sortedImages.value.length;
};

const goToImage = (index: number) => {
    currentImageIndex.value = index;
};

const openLightbox = (index: number) => {
    currentImageIndex.value = index;
    showLightbox.value = true;
};

const closeLightbox = () => {
    showLightbox.value = false;
};

// Formatear precio
const formatPrice = (price: number) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0
    }).format(price);
};

// Formulario de contacto
const form = ref({
    nombre: '',
    telefono: '',
    email: '',
    mensaje: `Hola, me interesa esta propiedad ID: ${props.propiedad.codigo_inmueble} - ${props.propiedad.name}`
});

const submitContact = () => {
    console.log('Enviando formulario:', form.value);
    // Aquí iría la lógica de envío
};

// Acciones sociales
const handleCall = () => {
    window.location.href = 'tel:+59166666666'; // Reemplazar con número real
};

const handleWhatsApp = () => {
    const message = encodeURIComponent(`Hola, me interesa la propiedad: ${props.propiedad.name} (${props.propiedad.codigo_inmueble})`);
    window.open(`https://wa.me/59166666666?text=${message}`, '_blank');
};

const handleShare = () => {
    if (navigator.share) {
        navigator.share({
            title: props.propiedad.name,
            text: props.propiedad.descripcion,
            url: window.location.href
        });
    } else {
        // Fallback: copiar al portapapeles
        navigator.clipboard.writeText(window.location.href);
        alert('Enlace copiado al portapapeles');
    }
};

const breadcrumbs = computed(() => [
    { label: 'Inicio', href: '/' },
    { label: 'Propiedades', href: '/propiedades' },
    { label: props.propiedad.name, current: true }
]);
</script>

<template>
    <Head :title="`${propiedad.name} - ${formatPrice(propiedad.price)}`" />

    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Breadcrumbs -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <nav class="flex items-center gap-2 text-sm">
                    <Link v-for="(item, index) in breadcrumbs" :key="index"
                          :href="item.href || '#'"
                          :class="item.current ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                        {{ item.label }}
                        <span v-if="index < breadcrumbs.length - 1" class="mx-2 text-gray-400">/</span>
                    </Link>
                </nav>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header: Título y Precio -->
            <div class="mb-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <Badge v-if="propiedad.operacion" class="mb-2" variant="secondary">
                            {{ propiedad.operacion }}
                        </Badge>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ propiedad.name }}
                        </h1>
                        <div class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                            <MapPin :size="18" class="mt-1 flex-shrink-0" />
                            <span>{{ propiedad.direccion || 'Ubicación no especificada' }}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Código: <span class="font-mono font-semibold">{{ propiedad.codigo_inmueble }}</span>
                        </p>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Precio {{ propiedad.operacion }}</p>
                        <p class="text-3xl md:text-4xl font-bold text-blue-600 dark:text-blue-400">
                            {{ formatPrice(propiedad.price) }}
                        </p>
                        <p v-if="propiedad.operacion === 'alquiler'" class="text-sm text-gray-500 mt-1">/mes</p>
                    </div>
                </div>
            </div>

            <!-- Grid Principal: Galería + Sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Galería (2/3 del ancho) -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Imagen Principal -->
                    <div class="relative bg-gray-900 rounded-xl overflow-hidden" style="height: 500px;">
                        <div v-if="sortedImages.length > 0" class="relative h-full">
                            <img
                                :src="getImageUrl(sortedImages[currentImageIndex].image_path)"
                                :alt="sortedImages[currentImageIndex].original_name"
                                class="w-full h-full object-cover cursor-pointer"
                                @click="openLightbox(currentImageIndex)"
                            />

                            <!-- Overlay gradiente -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                            <!-- Controles de navegación -->
                            <template v-if="sortedImages.length > 1">
                                <button
                                    @click="prevImage"
                                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-3 transition-all backdrop-blur-sm"
                                >
                                    <ChevronLeft :size="24" />
                                </button>

                                <button
                                    @click="nextImage"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-3 transition-all backdrop-blur-sm"
                                >
                                    <ChevronRight :size="24" />
                                </button>

                                <!-- Contador -->
                                <div class="absolute bottom-4 right-4 bg-black/70 text-white px-4 py-2 rounded-full text-sm backdrop-blur-sm">
                                    {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                                </div>

                                <!-- Badge "Ver todas las fotos" -->
                                <button
                                    @click="openLightbox(0)"
                                    class="absolute bottom-4 left-4 bg-black/70 hover:bg-black/80 text-white px-4 py-2 rounded-full text-sm backdrop-blur-sm transition-all flex items-center gap-2"
                                >
                                    <Grid3x3 :size="16" />
                                    Ver todas las fotos
                                </button>
                            </template>

                            <!-- Badge imagen principal -->
                            <div v-if="sortedImages[currentImageIndex].is_primary" class="absolute top-4 left-4">
                                <Badge class="bg-yellow-400 text-yellow-900 font-bold">
                                    Foto Principal
                                </Badge>
                            </div>
                        </div>

                        <!-- Sin imágenes -->
                        <div v-else class="h-full flex items-center justify-center">
                            <div class="text-center text-white">
                                <Home :size="64" class="mx-auto mb-4 opacity-50" />
                                <p class="text-lg">Sin imágenes disponibles</p>
                            </div>
                        </div>
                    </div>

                    <!-- Miniaturas -->
                    <div v-if="sortedImages.length > 1" class="grid grid-cols-6 gap-2">
                        <button
                            v-for="(image, index) in sortedImages.slice(0, 6)"
                            :key="image.id"
                            @click="goToImage(index)"
                            :class="[
                                'relative h-20 rounded-lg overflow-hidden transition-all border-2',
                                currentImageIndex === index
                                    ? 'border-blue-500 ring-2 ring-blue-200'
                                    : 'border-transparent opacity-70 hover:opacity-100'
                            ]"
                        >
                            <img
                                :src="getImageUrl(image.image_path)"
                                :alt="`Miniatura ${index + 1}`"
                                class="w-full h-full object-cover"
                            />
                            <div v-if="index === 5 && sortedImages.length > 6" 
                                 class="absolute inset-0 bg-black/70 flex items-center justify-center text-white font-bold">
                                +{{ sortedImages.length - 6 }}
                            </div>
                        </button>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="grid grid-cols-3 gap-3">
                        <Button
                            @click="handleCall"
                            variant="outline"
                            class="w-full flex items-center justify-center gap-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 border-amber-300 text-amber-900 dark:text-amber-400"
                        >
                            <Phone :size="18" />
                            Llamar
                        </Button>

                        <Button
                            @click="handleWhatsApp"
                            variant="outline"
                            class="w-full flex items-center justify-center gap-2 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/40 border-green-300 text-green-900 dark:text-green-400"
                        >
                            <MessageCircle :size="18" />
                            WhatsApp
                        </Button>

                        <Button
                            @click="handleShare"
                            variant="outline"
                            class="w-full flex items-center justify-center gap-2"
                        >
                            <Share2 :size="18" />
                            Compartir
                        </Button>
                    </div>

                    <!-- Descripción -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Descripción
                        </h2>
                        <div class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                            {{ propiedad.descripcion || 'Sin descripción disponible' }}
                        </div>
                    </div>

                    <!-- Características Detalladas -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            Características
                        </h2>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div v-if="propiedad.superficie_terreno" class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <Ruler :size="20" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Terreno</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.superficie_terreno }} m²
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.superficie_construida" class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <Building :size="20" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Construcción</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.superficie_construida }} m²
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.superficie_util" class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <Maximize2 :size="20" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Útil</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.superficie_util }} m²
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.habitaciones" class="flex items-start gap-3">
                                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                    <Bed :size="20" class="text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Habitaciones</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.habitaciones }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.banos" class="flex items-start gap-3">
                                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                                    <Bath :size="20" class="text-cyan-600 dark:text-cyan-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Baños</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.banos }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.cocheras" class="flex items-start gap-3">
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                    <Car :size="20" class="text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Cocheras</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.cocheras }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.ambientes" class="flex items-start gap-3">
                                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                    <Home :size="20" class="text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Ambientes</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.ambientes }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.antiguedad" class="flex items-start gap-3">
                                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <Clock :size="20" class="text-gray-600 dark:text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Antigüedad</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.antiguedad }} años
                                    </p>
                                </div>
                            </div>

                            <div v-if="propiedad.ano_construccion" class="flex items-start gap-3">
                                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <Calendar :size="20" class="text-gray-600 dark:text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Año</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ propiedad.ano_construccion }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Formulario de Contacto (1/3 del ancho) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-8">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                ¿Necesitas más información?
                            </h3>

                            <!-- Agente info -->
                            <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                    CF
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Carlos Flores</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Agente Inmobiliario</p>
                                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">+591 66666666</p>
                                </div>
                            </div>

                            <!-- Formulario -->
                            <form @submit.prevent="submitContact" class="space-y-4">
                                <div>
                                    <input
                                        v-model="form.nombre"
                                        type="text"
                                        placeholder="Nombre"
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <input
                                        v-model="form.telefono"
                                        type="tel"
                                        placeholder="Teléfono"
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="Email"
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <textarea
                                        v-model="form.mensaje"
                                        placeholder="Mensaje"
                                        rows="4"
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                                    ></textarea>
                                </div>

                                <div class="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        id="terms"
                                        required
                                        class="mt-1"
                                    />
                                    <label for="terms" class="text-xs text-gray-600 dark:text-gray-400">
                                        Acepto Términos y Condiciones de Uso y la Política de Privacidad
                                    </label>
                                </div>

                                <Button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white">
                                    Contactar
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Propiedades Relacionadas -->
            <div v-if="relacionadas && relacionadas.length > 0" class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    Propiedades Relacionadas
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="relacionada in relacionadas"
                        :key="relacionada.id"
                        :href="`/propiedad/${relacionada.id}`"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all group"
                    >
                        <div class="relative h-48">
                            <img
                                v-if="relacionada.default_image"
                                :src="relacionada.default_image"
                                :alt="relacionada.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                            />
                            <div v-else class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <Home :size="32" class="text-gray-400" />
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2 line-clamp-2">
                                {{ relacionada.name }}
                            </h3>
                            <p class="text-blue-600 dark:text-blue-400 font-bold text-xl">
                                {{ formatPrice(relacionada.price) }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showLightbox"
                    class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center"
                    @click="closeLightbox"
                >
                    <button
                        @click.stop="closeLightbox"
                        class="absolute top-4 right-4 text-white hover:text-gray-300 p-2"
                    >
                        <X :size="32" />
                    </button>

                    <button
                        v-if="sortedImages.length > 1"
                        @click.stop="prevImage"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 p-2"
                    >
                        <ChevronLeft :size="48" />
                    </button>

                    <button
                        v-if="sortedImages.length > 1"
                        @click.stop="nextImage"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 p-2"
                    >
                        <ChevronRight :size="48" />
                    </button>

                    <img
                        v-if="sortedImages[currentImageIndex]"
                        :src="getImageUrl(sortedImages[currentImageIndex].image_path)"
                        :alt="sortedImages[currentImageIndex].original_name"
                        class="max-w-[90vw] max-h-[90vh] object-contain"
                        @click.stop
                    />

                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-lg">
                        {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>