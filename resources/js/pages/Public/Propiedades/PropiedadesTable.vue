<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Grid, List, MapPin, Bed, Bath, Car, Home, Maximize2, Calendar } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/public/Pagination.vue';
import publicRoutes from '@/routes/public';

interface ProductImage {
    id: number;
    image_path: string;
    original_name: string;
    is_primary: boolean;
    order: number;
}

interface Product {
    id: number;
    name: string;
    codigo_inmueble: string;
    price: number;
    descripcion?: string;
    direccion?: string;
    superficie_util?: number;
    superficie_construida?: number;
    ambientes?: number;
    habitaciones?: number;
    banos?: number;
    cocheras?: number;
    ano_construccion?: number;
    antiguedad?: number;
    operacion?: string;
    category?: string;
    default_image?: string | null;
    images?: ProductImage[];
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
}

interface Props {
    propiedades: Product[];
    pagination: Pagination;
    loading: boolean;
    viewMode: 'grid' | 'list';
}

const props = defineProps<Props>();

const emit = defineEmits<{
    pageChange: [page: number];
    perPageChange: [perPage: number];
}>();

// Ya no se usan favoritos

// Cargar favoritos desde localStorage

//loadFavorites();

// Computed para obtener imágenes ordenadas
const getSortedImages = (propiedad: Product) => {
    if (!propiedad.images?.length) return [];

    return [...propiedad.images].sort((a, b) => {
        if (a.is_primary && !b.is_primary) return -1;
        if (!a.is_primary && b.is_primary) return 1;
        return a.order - b.order;
    });
};

// Helper para obtener URL de imagen
const getImageUrl = (imagePath: string) => {
    return `/storage/${imagePath}`;
};



// Lógica de navegación de imágenes para cada propiedad
const favoriteProperties = ref<Set<number>>(new Set());
const currentImageIndexes = ref<Record<number, number>>({});

const nextImage = (propiedadId: number, images: ProductImage[]) => {
    if (!images.length) return;
    const currentIndex = currentImageIndexes.value[propiedadId] || 0;
    currentImageIndexes.value[propiedadId] = (currentIndex + 1) % images.length;
};

const prevImage = (propiedadId: number, images: ProductImage[]) => {
    if (!images.length) return;
    const currentIndex = currentImageIndexes.value[propiedadId] || 0;
    currentImageIndexes.value[propiedadId] = (currentIndex - 1 + images.length) % images.length;
};

const goToImage = (propiedadId: number, index: number) => {
    currentImageIndexes.value[propiedadId] = index;
};

const currentPage = computed(() => props.pagination?.current_page || 1);
const lastPage = computed(() => props.pagination?.last_page || 1);
const perPage = computed(() => props.pagination?.per_page || 12);
const total = computed(() => props.pagination?.total || 0);
</script>

<template>
    <div class="space-y-6">
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="propiedad in propiedades"
                :key="propiedad.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 group"
            >
                <!-- Galería de imágenes -->
                <div class="relative">
                    <div v-if="getSortedImages(propiedad).length > 0" class="relative h-48 overflow-hidden bg-gray-100 dark:bg-gray-700">
                        <!-- Imagen principal -->
                        <img
                            :src="getImageUrl(getSortedImages(propiedad)[currentImageIndexes[propiedad.id] || 0]?.image_path)"
                            :alt="getSortedImages(propiedad)[currentImageIndexes[propiedad.id] || 0]?.original_name"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />

                        <!-- Badge de imagen principal -->
                        <div v-if="getSortedImages(propiedad)[currentImageIndexes[propiedad.id] || 0]?.is_primary"
                             class="absolute top-2 left-2">
                            <Badge class="bg-yellow-400 text-yellow-900 text-xs font-bold">
                                Principal
                            </Badge>
                        </div>

                        <!-- Controles de navegación (solo si hay más de 1 imagen) -->
                        <template v-if="getSortedImages(propiedad).length > 1">
                            <!-- Botón anterior -->
                            <button
                                @click="prevImage(propiedad.id, getSortedImages(propiedad))"
                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-2 transition-all opacity-0 group-hover:opacity-100"
                            >
                                <ChevronLeft :size="16" />
                            </button>

                            <!-- Botón siguiente -->
                            <button
                                @click="nextImage(propiedad.id, getSortedImages(propiedad))"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-2 transition-all opacity-0 group-hover:opacity-100"
                            >
                                <ChevronRight :size="16" />
                            </button>

                            <!-- Contador de imágenes -->
                            <div class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs">
                                {{ (currentImageIndexes[propiedad.id] || 0) + 1 }} / {{ getSortedImages(propiedad).length }}
                            </div>
                        </template>

                      </div>

                    <!-- Estado sin imágenes -->
                    <div v-else class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                        <div class="text-center">
                            <Home :size="32" class="text-gray-400 mx-auto mb-2" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">Sin imágenes</span>
                        </div>
                    </div>

                    <!-- Miniaturas (thumbnails) -->
                    <div v-if="getSortedImages(propiedad).length > 1" class="px-2 py-2 bg-gray-50 dark:bg-gray-900">
                        <div class="flex gap-1 overflow-x-auto">
                            <button
                                v-for="(image, index) in getSortedImages(propiedad)"
                                :key="image.id"
                                @click="goToImage(propiedad.id, index)"
                                :class="[
                                    'flex-shrink-0 w-12 h-12 rounded overflow-hidden transition-all border-2',
                                    (currentImageIndexes[propiedad.id] || 0) === index
                                        ? 'border-blue-500 ring-1 ring-blue-200 scale-110'
                                        : 'border-transparent opacity-60 hover:opacity-100'
                                ]"
                            >
                                <img
                                    :src="getImageUrl(image.image_path)"
                                    :alt="`Thumbnail ${index + 1}`"
                                    class="w-full h-full object-cover"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de la propiedad -->
                <div class="p-4 space-y-3">
                    <!-- Header -->
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1 line-clamp-2">
                            {{ propiedad.name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Código: <span class="font-mono">{{ propiedad.codigo_inmueble }}</span>
                        </p>
                    </div>

                    <!-- Precio -->
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            ${{ Number(propiedad.price).toLocaleString() }}
                        </span>
                        <span v-if="propiedad.operacion === 'alquiler'" class="text-sm text-gray-500">
                            /mes
                        </span>
                        <Badge v-if="propiedad.operacion" class="ml-auto" variant="secondary">
                            {{ propiedad.operacion }}
                        </Badge>
                    </div>

                    <!-- Características principales -->
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div v-if="propiedad.ambientes" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                            <Home :size="14" />
                            <span>{{ propiedad.ambientes }} amb</span>
                        </div>
                        <div v-if="propiedad.habitaciones" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                            <Bed :size="14" />
                            <span>{{ propiedad.habitaciones }} hab</span>
                        </div>
                        <div v-if="propiedad.banos" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                            <Bath :size="14" />
                            <span>{{ propiedad.banos }} baños</span>
                        </div>
                        <div v-if="propiedad.cocheras" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                            <Car :size="14" />
                            <span>{{ propiedad.cocheras }} cocheras</span>
                        </div>
                    </div>

                    <!-- Superficies -->
                    <div v-if="propiedad.superficie_construida || propiedad.superficie_util" class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <div v-if="propiedad.superficie_construida" class="flex items-center gap-1">
                            <Maximize2 :size="14" />
                            <span>{{ propiedad.superficie_construida }}m²</span>
                        </div>
                        <div v-if="propiedad.superficie_util" class="text-xs">
                            <span class="text-gray-400">({{ propiedad.superficie_util }}m² útiles)</span>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div v-if="propiedad.direccion" class="flex items-start gap-2">
                        <MapPin :size="14" class="text-gray-400 mt-0.5 flex-shrink-0" />
                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ propiedad.direccion }}
                        </p>
                    </div>

                    <!-- Descripción corta -->
                    <p v-if="propiedad.descripcion" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                        {{ propiedad.descripcion }}
                    </p>

                    <!-- Acciones -->
                    <div class="flex gap-2 pt-2">
                        <Button
                            :as="Link"
                            :href="publicRoutes.propiedad.show.url(propiedad.id)"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm"
                            size="sm"
                        >
                            Ver Detalles
                        </Button>
                      </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="space-y-4">
            <div
                v-for="propiedad in propiedades"
                :key="propiedad.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300"
            >
                <div class="flex flex-col lg:flex-row">
                    <!-- Galería en modo lista -->
                    <div class="lg:w-80 flex-shrink-0">
                        <div class="relative h-48 lg:h-full">
                            <div v-if="getSortedImages(propiedad).length > 0" class="h-full bg-gray-100 dark:bg-gray-700">
                                <!-- Imagen principal -->
                                <img
                                    :src="getImageUrl(getSortedImages(propiedad)[currentImageIndexes[propiedad.id] || 0]?.image_path)"
                                    :alt="getSortedImages(propiedad)[currentImageIndexes[propiedad.id] || 0]?.original_name"
                                    class="w-full h-full object-cover"
                                />

                                <!-- Controles de navegación (solo si hay más de 1 imagen) -->
                                <template v-if="getSortedImages(propiedad).length > 1">
                                    <!-- Botón anterior -->
                                    <button
                                        @click="prevImage(propiedad.id, getSortedImages(propiedad))"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-2 transition-all"
                                    >
                                        <ChevronLeft :size="16" />
                                    </button>

                                    <!-- Botón siguiente -->
                                    <button
                                        @click="nextImage(propiedad.id, getSortedImages(propiedad))"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full p-2 transition-all"
                                    >
                                        <ChevronRight :size="16" />
                                    </button>

                                    <!-- Contador de imágenes -->
                                    <div class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs">
                                        {{ (currentImageIndexes[propiedad.id] || 0) + 1 }} / {{ getSortedImages(propiedad).length }}
                                    </div>
                                </template>

                                <!-- Botón de favorito -->
                                <button
                                    @click="handleFavorite(propiedad.id)"
                                    class="absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-700 rounded-full p-2 transition-all shadow-lg"
                                >
                                    <Heart
                                        :size="16"
                                        :class="favoriteProperties.has(propiedad.id) ? 'fill-red-500 text-red-500' : ''"
                                    />
                                </button>
                            </div>

                            <!-- Estado sin imágenes -->
                            <div v-else class="h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                <div class="text-center">
                                    <Home :size="32" class="text-gray-400 mx-auto mb-2" />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Sin imágenes</span>
                                </div>
                            </div>
                        </div>

                        <!-- Miniaturas (thumbnails) -->
                        <div v-if="getSortedImages(propiedad).length > 1" class="px-2 py-2 bg-gray-50 dark:bg-gray-900">
                            <div class="flex gap-1 overflow-x-auto">
                                <button
                                    v-for="(image, index) in getSortedImages(propiedad)"
                                    :key="image.id"
                                    @click="goToImage(propiedad.id, index)"
                                    :class="[
                                        'flex-shrink-0 w-12 h-12 rounded overflow-hidden transition-all border-2',
                                        (currentImageIndexes[propiedad.id] || 0) === index
                                            ? 'border-blue-500 ring-1 ring-blue-200 scale-110'
                                            : 'border-transparent opacity-60 hover:opacity-100'
                                    ]"
                                >
                                    <img
                                        :src="getImageUrl(image.image_path)"
                                        :alt="`Thumbnail ${index + 1}`"
                                        class="w-full h-full object-cover"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Información detallada -->
                    <div class="flex-1 p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ propiedad.name }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                    Código: <span class="font-mono">{{ propiedad.codigo_inmueble }}</span>
                                </p>
                                <div v-if="propiedad.direccion" class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <MapPin :size="14" class="mt-0.5 flex-shrink-0" />
                                    <span>{{ propiedad.direccion }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    ${{ Number(propiedad.price).toLocaleString() }}
                                </div>
                                <div v-if="propiedad.operacion === 'alquiler'" class="text-sm text-gray-500">
                                    /mes
                                </div>
                                <Badge v-if="propiedad.operacion" class="mt-2">
                                    {{ propiedad.operacion }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Características detalladas -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
                            <div v-if="propiedad.ambientes" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Ambientes:</span> {{ propiedad.ambientes }}
                            </div>
                            <div v-if="propiedad.habitaciones" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Habitaciones:</span> {{ propiedad.habitaciones }}
                            </div>
                            <div v-if="propiedad.banos" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Baños:</span> {{ propiedad.banos }}
                            </div>
                            <div v-if="propiedad.cocheras" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Cocheras:</span> {{ propiedad.cocheras }}
                            </div>
                            <div v-if="propiedad.superficie_construida" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Superficie:</span> {{ propiedad.superficie_construida }}m²
                            </div>
                            <div v-if="propiedad.superficie_util" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Útiles:</span> {{ propiedad.superficie_util }}m²
                            </div>
                            <div v-if="propiedad.antiguedad" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Antigüedad:</span> {{ propiedad.antiguedad }} años
                            </div>
                            <div v-if="propiedad.category" class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Categoría:</span> {{ propiedad.category }}
                            </div>
                        </div>

                        <!-- Descripción -->
                        <p
                            v-if="propiedad.descripcion"
                            class="text-gray-600 dark:text-gray-400 line-clamp-3"
                        >
                            {{ propiedad.descripcion }}
                        </p>

                        <!-- Acciones -->
                        <div class="flex gap-3">
                            <Button
                                :as="Link"
                                :href="publicRoutes.propiedad.show.url(propiedad.id)"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                Ver Detalles
                            </Button>
                          </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="mt-8">
            <Pagination
                :current-page="currentPage"
                :last-page="lastPage"
                :per-page="perPage"
                :total="total"
                :from="pagination?.from"
                :to="pagination?.to"
                :loading="loading"
                @page-change="emit('pageChange', $event)"
                @per-page-change="emit('perPageChange', $event)"
            />
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>