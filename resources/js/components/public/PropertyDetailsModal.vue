<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    X,
    BedDouble,
    Bath,
    Home,
    MapPin,
    DollarSign,
    Maximize2,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Share2,
    Heart,
    Phone,
    Mail,
    Check
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

interface Property {
    id: number;
    name: string;
    codigo_inmueble: string;
    price_usd?: number | null;
    price_bob?: number | null;
    operacion: string;
    category?: string;
    category_id?: number | null;
    superficie_util?: number;
    superficie_construida?: number;
    ambientes?: number;
    habitaciones?: number;
    banos?: number;
    cocheras?: number;
    ano_construccion?: number;
    descripcion?: string;
    default_image?: string | null;
    images?: ProductImage[];
    location?: {
        latitude: number;
        longitude: number;
        address?: string;
    };
}

const props = defineProps<{
    property: Property;
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const currentImageIndex = ref(0);
const isFavorite = ref(false);

// Imagen principal actual
const currentImage = computed(() => {
    if (!props.property.images || props.property.images.length === 0) {
        return null;
    }
    return props.property.images[currentImageIndex.value] || props.property.images[0];
});

// Todas las imágenes ordenadas
const sortedImages = computed(() => {
    if (!props.property.images) return [];
    return [...props.property.images].sort((a, b) => a.order - b.order);
});

// Formatear precio
const formatPrice = (price: number | null, currency: string = 'USD') => {
    if (!price) return null;
    const symbol = currency === 'USD' ? '$' : 'Bs.';
    return `${symbol}${price.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

// Obtener URL de imagen
const getImageUrl = (imagePath: string | null) => {
    if (!imagePath) return null;
    return `/storage/${imagePath}`;
};

// Navegación de imágenes
const nextImage = () => {
    if (sortedImages.value.length === 0) return;
    currentImageIndex.value = (currentImageIndex.value + 1) % sortedImages.value.length;
};

const prevImage = () => {
    if (sortedImages.value.length === 0) return;
    currentImageIndex.value = currentImageIndex.value === 0
        ? sortedImages.value.length - 1
        : currentImageIndex.value - 1;
};

// Obtener etiqueta de operación
const getOperacionLabel = (operacion: string) => {
    const labels: Record<string, { text: string; color: string }> = {
        'venta': { text: 'Venta', color: 'bg-green-600' },
        'alquiler': { text: 'Alquiler', color: 'bg-blue-600' },
        'anticretico': { text: 'Anticrético', color: 'bg-red-600' }
    };
    return labels[operacion] || { text: operacion, color: 'bg-gray-600' };
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="emit('close')"
                @keydown.esc="emit('close')"
            >
                <!-- Modal Container -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <Badge :class="['text-white font-bold px-4 py-1.5', getOperacionLabel(property.operacion).color]">
                                    {{ getOperacionLabel(property.operacion).text }}
                                </Badge>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white line-clamp-1">
                                    {{ property.name }}
                                </h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-gray-600 hover:text-red-600 dark:text-gray-300"
                                    @click="isFavorite = !isFavorite"
                                >
                                    <Heart :class="['w-5 h-5', isFavorite ? 'fill-red-500 text-red-500' : '']" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                                    @click="emit('close')"
                                >
                                    <X class="w-6 h-6" />
                                </Button>
                            </div>
                        </div>

                        <!-- Content Scrollable -->
                        <div class="flex-1 overflow-y-auto">
                            <div class="grid lg:grid-cols-2 gap-0">
                                <!-- Gallery Section -->
                                <div class="relative bg-gray-100 dark:bg-gray-800">
                                    <!-- Main Image -->
                                    <div class="relative aspect-[4/3] overflow-hidden">
                                        <img
                                            v-if="currentImage"
                                            :src="getImageUrl(currentImage.image_path)"
                                            :alt="property.name"
                                            class="w-full h-full object-cover"
                                        >
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center bg-gray-200 dark:bg-gray-700"
                                        >
                                            <Home class="w-24 h-24 text-gray-400 dark:text-gray-500" />
                                        </div>

                                        <!-- Navigation Arrows -->
                                        <template v-if="sortedImages.length > 1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 shadow-lg"
                                                @click="prevImage"
                                            >
                                                <ChevronLeft class="w-6 h-6" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 shadow-lg"
                                                @click="nextImage"
                                            >
                                                <ChevronRight class="w-6 h-6" />
                                            </Button>
                                        </template>

                                        <!-- Image Counter -->
                                        <div
                                            v-if="sortedImages.length > 1"
                                            class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 text-white px-3 py-1 rounded-full text-sm font-medium"
                                        >
                                            {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                                        </div>
                                    </div>

                                    <!-- Thumbnail Gallery -->
                                    <div
                                        v-if="sortedImages.length > 1"
                                        class="flex gap-2 p-4 overflow-x-auto"
                                    >
                                        <button
                                            v-for="(img, idx) in sortedImages.slice(0, 5)"
                                            :key="img.id"
                                            @click="currentImageIndex = idx"
                                            class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all"
                                            :class="currentImageIndex === idx ? 'border-[#FAB90E] scale-105' : 'border-transparent opacity-60 hover:opacity-100'"
                                        >
                                            <img
                                                :src="getImageUrl(img.image_path)"
                                                :alt="img.original_name"
                                                class="w-full h-full object-cover"
                                            >
                                        </button>
                                    </div>
                                </div>

                                <!-- Details Section -->
                                <div class="p-6 space-y-6">
                                    <!-- Code and Category -->
                                    <div class="space-y-2">
                                        <p class="text-sm font-semibold text-[#233C7A] dark:text-[#FAB90E] uppercase tracking-wider">
                                            Código: {{ property.codigo_inmueble }}
                                        </p>
                                        <p v-if="property.category" class="text-sm text-gray-600 dark:text-gray-400">
                                            Categoría: {{ property.category }}
                                        </p>
                                    </div>

                                    <!-- Prices -->
                                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 space-y-3">
                                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                            Precio
                                        </h3>
                                        <div v-if="property.price_usd" class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                                <DollarSign class="w-5 h-5 text-green-600 dark:text-green-400" />
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Precio USD</p>
                                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                                    {{ formatPrice(property.price_usd, 'USD') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-if="property.price_bob" class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                                <DollarSign class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Precio BOB</p>
                                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                    {{ formatPrice(property.price_bob, 'BOB') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Features Grid -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div v-if="property.ambientes" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <Home class="w-5 h-5 text-[#233C7A] dark:text-[#FAB90E]" />
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Ambientes</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.ambientes }}</p>
                                            </div>
                                        </div>
                                        <div v-if="property.habitaciones" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <BedDouble class="w-5 h-5 text-[#E0081D]" />
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Habitaciones</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.habitaciones }}</p>
                                            </div>
                                        </div>
                                        <div v-if="property.banos" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <Bath class="w-5 h-5 text-[#233C7A] dark:text-[#FAB90E]" />
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Baños</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.banos }}</p>
                                            </div>
                                        </div>
                                        <div v-if="property.cocheras" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                            <div class="w-5 h-5 rounded bg-[#FAB90E] flex items-center justify-center text-[10px] font-black text-[#212121]">C</div>
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Cocheras</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.cocheras }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Surface Area -->
                                    <div class="space-y-2">
                                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide flex items-center gap-2">
                                            <Maximize2 class="w-4 h-4" />
                                            Superficie
                                        </h4>
                                        <div class="flex gap-3">
                                            <div v-if="property.superficie_construida" class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Construida</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.superficie_construida }} m²</p>
                                            </div>
                                            <div v-if="property.superficie_util" class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Útil</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.superficie_util }} m²</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Year Built -->
                                    <div v-if="property.ano_construccion" class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                        <Calendar class="w-4 h-4" />
                                        <span>Año de construcción: <strong class="text-gray-900 dark:text-white">{{ property.ano_construccion }}</strong></span>
                                    </div>

                                    <!-- Description -->
                                    <div v-if="property.descripcion" class="space-y-2">
                                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                            Descripción
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                            {{ property.descripcion }}
                                        </p>
                                    </div>

                                    <!-- Location -->
                                    <div v-if="property.location?.address" class="flex items-start gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <MapPin class="w-5 h-5 text-[#E0081D] flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Dirección</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ property.location.address }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between gap-4 p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Share2 class="w-4 h-4" />
                                    Compartir
                                </Button>
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Heart class="w-4 h-4" />
                                    Guardar
                                </Button>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Phone class="w-4 h-4" />
                                    Llamar
                                </Button>
                                <Button size="sm" class="gap-2 bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] hover:from-[#E0081D] hover:to-[#233C7A]">
                                    <Mail class="w-4 h-4" />
                                    Contactar
                                </Button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: #374151;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
