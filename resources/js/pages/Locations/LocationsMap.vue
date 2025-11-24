<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ubicaciones } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Navigation, X, MapPin } from 'lucide-vue-next';

// Fix para iconos de Leaflet
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';
import iconRetina from 'leaflet/dist/images/marker-icon-2x.png';

delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: iconRetina,
    iconUrl: icon,
    shadowUrl: iconShadow,
});

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
    default_image: string | null;
    category: string | null;
    images?: ProductImage[];
    location: {
        id: number;
        latitude: number;
        longitude: number;
        address: string | null;
        is_active: boolean;
    };
}

window.addEventListener("open-product", (e: any) => {
    const product = props.productsConUbicacion.find(p => p.id === e.detail);
    if (product) {
        selectedProduct.value = product;
        currentImageIndex.value = 0;
    }
});



const props = defineProps<{
    productsConUbicacion: Product[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ubicaciones', href: ubicaciones().url },
    { title: 'Ver Mapa', href: '/ubicaciones/mapa' },
];

const mapContainer = ref<HTMLElement | null>(null);
const selectedProduct = ref<Product | null>(null);
const isLocatingUser = ref(false);
const currentImageIndex = ref(0);
let map: L.Map | null = null;
const markers: L.Marker[] = [];
let userLocationMarker: L.Marker | null = null;
let userLocationCircle: L.Circle | null = null;

const defaultCenter = { lat: -16.5000, lng: -68.1500 }; // La Paz, Bolivia

// ✅ FIXED: Computed para obtener imágenes ordenadas
const sortedImages = computed(() => {
    if (!selectedProduct.value?.images?.length) return [];
    
    // Ordenar por el campo 'order' y luego poner primarias primero
    return [...selectedProduct.value.images].sort((a, b) => {
        if (a.is_primary && !b.is_primary) return -1;
        if (!a.is_primary && b.is_primary) return 1;
        return a.order - b.order;
    });
});

// ✅ Helper para obtener URL de imagen
const getImageUrl = (imagePath: string) => {
    return `/storage/${imagePath}`;
};

// ✅ FIXED: Navegación de imágenes usando sortedImages
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

const initMap = () => {
    if (!mapContainer.value) return;

    // Crear mapa centrado en La Paz
    map = L.map(mapContainer.value).setView([defaultCenter.lat, defaultCenter.lng], 13);

    // Agregar capa de tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Agregar marcadores de productos
    props.productsConUbicacion.forEach((product) => {
        if (!product.location.is_active) return;

        const marker = L.marker([product.location.latitude, product.location.longitude])
            .addTo(map!);

        // Popup con información del producto
        const popupContent = (product: Product) => {
    const images = product.images
        ? product.images.sort((a, b) => (a.is_primary ? -1 : 1))
        : [];

    const imageUrl = (path: string) => `/storage/${path}`;

    const imagesJson = JSON.stringify(images.map(i => imageUrl(i.image_path)));

    return `
        <div class="popup-card" 
             style="width:260px;border-radius:12px;overflow:hidden;
                    box-shadow:0 4px 12px rgba(0,0,0,0.2);font-family:sans-serif;
                    background:white;">

            <!-- CARRUSEL -->
            <div class="carousel-container" 
                 data-images='${imagesJson}'
                 style="position:relative;width:100%;height:160px;overflow:hidden;background:#000;">

                <!-- Imagen principal -->
                <img class="carousel-image"
                    src="${images.length ? imageUrl(images[0].image_path) : ''}"
                    style="width:100%;height:160px;object-fit:cover;"
                />

                <!-- Flechas -->
                <button class="carousel-prev" 
                    style="position:absolute;top:50%;left:6px;transform:translateY(-50%);
                           background:rgba(0,0,0,0.6);color:white;border:none;
                           padding:4px 8px;border-radius:6px;cursor:pointer;">
                    ‹
                </button>

                <button class="carousel-next" 
                    style="position:absolute;top:50%;right:6px;transform:translateY(-50%);
                           background:rgba(0,0,0,0.6);color:white;border:none;
                           padding:4px 8px;border-radius:6px;cursor:pointer;">
                    ›
                </button>
            </div>

            <!-- CONTENIDO -->
            <div style="padding:12px;background:white;">
                <h3 style="font-size:16px;font-weight:bold;margin-bottom:4px;color:#333;">
                    ${product.name}
                </h3>

                <p style="font-size:12px;color:#666;margin:2px 0;">
                    Código: <strong>${product.codigo_inmueble}</strong>
                </p>

                <p style="font-size:12px;color:#666;margin:2px 0;">
                    Categoría: ${product.category ?? 'N/A'}
                </p>

                <p style="font-size:16px;font-weight:bold;color:#0a8a0a;margin-top:8px;">
                    ${product.price.toLocaleString()} USD
                </p>

                <button 
                    onclick="window.dispatchEvent(new CustomEvent('open-product', { detail: ${product.id} }))"
                    style="
                        width:100%;margin-top:12px;background:#caa86f;
                        color:white;padding:8px 0;border-radius:6px;border:none;
                        font-weight:bold;cursor:pointer;
                    ">
                    Ver Detalle
                </button>
            </div>
        </div>
    `;
};


        
        marker.bindPopup(popupContent(product));

        marker.on("popupopen", (e) => {
    const popup = e.popup.getElement();

    if (!popup) return;

    const carouselContainer = popup.querySelector(".carousel-container");
    if (!carouselContainer) return;

    const imgEl = popup.querySelector(".carousel-image");
    const prevBtn = popup.querySelector(".carousel-prev");
    const nextBtn = popup.querySelector(".carousel-next");

    const images = JSON.parse(carouselContainer.dataset.images || "[]");
    let index = 0;

    const updateImage = () => {
        if (imgEl) {
            imgEl.src = images[index];
        }
    };

    prevBtn?.addEventListener("click", () => {
        index = (index - 1 + images.length) % images.length;
        updateImage();
    });

    nextBtn?.addEventListener("click", () => {
        index = (index + 1) % images.length;
        updateImage();
    });
});


        // Click en marcador para mostrar detalles
        marker.on('click', () => {
            selectedProduct.value = product;
        });

        markers.push(marker);
    });

    // Ajustar vista para mostrar todos los marcadores
    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

const centerOnMyLocation = () => {
    if (!map) return;

    if ('geolocation' in navigator) {
        isLocatingUser.value = true;

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                
                map!.setView([latitude, longitude], 17, {
                    animate: true,
                    duration: 1.5
                });

                if (userLocationMarker) {
                    map!.removeLayer(userLocationMarker);
                }
                if (userLocationCircle) {
                    map!.removeLayer(userLocationCircle);
                }

                userLocationCircle = L.circle([latitude, longitude], {
                    radius: accuracy,
                    color: '#3B82F6',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.1,
                    weight: 2
                }).addTo(map!);

                const pulsingIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div style="position: relative;">
                            <div style="
                                width: 20px;
                                height: 20px;
                                background: #3B82F6;
                                border: 3px solid white;
                                border-radius: 50%;
                                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
                                animation: pulse 2s infinite;
                                position: relative;
                                z-index: 1000;
                            "></div>
                            <style>
                                @keyframes pulse {
                                    0% {
                                        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
                                    }
                                    70% {
                                        box-shadow: 0 0 0 20px rgba(59, 130, 246, 0);
                                    }
                                    100% {
                                        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
                                    }
                                }
                            </style>
                        </div>
                    `,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13]
                });

                userLocationMarker = L.marker([latitude, longitude], {
                    icon: pulsingIcon
                }).addTo(map!)
                    .bindPopup(`
                        <div class="p-3">
                            <p class="font-bold text-blue-600 mb-2">📍 Tu ubicación actual</p>
                            <p class="text-xs text-gray-600">Precisión: ±${Math.round(accuracy)}m</p>
                        </div>
                    `)
                    .openPopup();

                isLocatingUser.value = false;
            },
            (error) => {
                isLocatingUser.value = false;
                console.error('Error obteniendo ubicación:', error);
                
                let errorMessage = 'No se pudo obtener tu ubicación.';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Permiso de ubicación denegado. Por favor, habilita el acceso a tu ubicación en la configuración del navegador.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'La información de ubicación no está disponible.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'La solicitud de ubicación ha expirado. Intenta nuevamente.';
                        break;
                }
                
                alert(errorMessage);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Tu navegador no soporta geolocalización');
    }
};

const resetView = () => {
    if (!map || markers.length === 0) return;

    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
        userLocationMarker = null;
    }
    if (userLocationCircle) {
        map.removeLayer(userLocationCircle);
        userLocationCircle = null;
    }

    const group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.1), {
        animate: true,
        duration: 1
    });
};

const closeDetails = () => {
    selectedProduct.value = null;
    currentImageIndex.value = 0;
};

const goBack = () => {
    router.visit(ubicaciones().url);
};

onMounted(() => {
    initMap();
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Mapa de Ubicaciones" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative h-[calc(100vh-140px)]">
            <!-- Mapa -->
            <div ref="mapContainer" class="w-full h-full"></div>

            <!-- Controles superiores -->
            <div class="absolute top-4 left-4 right-4 z-[1000] flex items-center justify-between gap-3">
                <!-- Botón Volver -->
                <button
                    @click="goBack"
                    class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all"
                >
                    <ArrowLeft :size="20" />
                    <span class="font-semibold">Volver</span>
                </button>

                <!-- Grupo de botones de mapa -->
                <div class="flex items-center gap-2">
                    <!-- Botón Reiniciar Vista -->
                    <button
                        @click="resetView"
                        class="bg-white hover:bg-gray-50 text-gray-700 p-3 rounded-lg shadow-lg transition-all"
                        title="Ver todas las propiedades"
                    >
                        <MapPin :size="24" />
                    </button>

                    <!-- Botón Mi Ubicación con estado de carga -->
                    <button
                        @click="centerOnMyLocation"
                        :disabled="isLocatingUser"
                        :class="[
                            'p-3 rounded-lg shadow-lg transition-all flex items-center gap-2',
                            isLocatingUser 
                                ? 'bg-blue-400 cursor-not-allowed' 
                                : 'bg-blue-600 hover:bg-blue-700'
                        ]"
                        class="text-white"
                        title="Centrar en mi ubicación"
                    >
                        <Navigation :size="24" :class="isLocatingUser ? 'animate-pulse' : ''" />
                        <span v-if="isLocatingUser" class="text-sm font-medium">Ubicando...</span>
                    </button>
                </div>
            </div>

            <!-- Info Box con estadísticas -->
            <div class="absolute bottom-4 left-4 z-[1000] bg-white rounded-lg shadow-xl p-4 max-w-xs">
                <div class="flex items-center gap-2 mb-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <MapPin :size="20" class="text-blue-600" />
                    </div>
                    <h3 class="font-bold text-lg text-gray-800">Mapa de Propiedades</h3>
                </div>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <p>Total de propiedades: <span class="font-semibold text-gray-900">{{ productsConUbicacion.length }}</span></p>
                    </div>
                    <p class="text-xs text-gray-500">• Haz clic en los marcadores para detalles</p>
                    <p class="text-xs text-gray-500">• Usa scroll para hacer zoom</p>
                    <p class="text-xs text-gray-500">• <Navigation :size="12" class="inline" /> = Tu ubicación</p>
                </div>
            </div>

            <!-- Panel de Detalles del Producto -->
            <Transition
                enter-active-class="transition-transform duration-300"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-300"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div
                    v-if="selectedProduct"
                    class="absolute top-0 right-0 h-full w-full md:w-96 bg-white shadow-2xl z-[1001] overflow-y-auto"
                >
                    <!-- Header -->
                    <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-white">Detalles de la Propiedad</h3>
                        <button
                            @click="closeDetails"
                            class="text-white hover:bg-white/20 rounded-full p-1 transition-colors"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- ✅ FIXED: Galería usando sortedImages -->
                        <div v-if="sortedImages.length > 0" class="mb-6 -mx-6 -mt-6">
                            <!-- Imagen principal -->
                            <div class="relative group">
                                <img
                                    :src="getImageUrl(sortedImages[currentImageIndex].image_path)"
                                    :alt="sortedImages[currentImageIndex].original_name"
                                    class="w-full h-64 object-cover"
                                />
                                
                                <!-- Overlay con gradiente -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                                <!-- Navegación de imágenes (si hay más de 1) -->
                                <template v-if="sortedImages.length > 1">
                                    <!-- Botón anterior -->
                                    <button
                                        @click="prevImage"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>

                                    <!-- Botón siguiente -->
                                    <button
                                        @click="nextImage"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <!-- Contador de imágenes -->
                                    <div class="absolute bottom-3 right-3 bg-black/70 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                                        {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                                    </div>
                                </template>

                                <!-- Badge imagen principal -->
                                <div v-if="sortedImages[currentImageIndex].is_primary" class="absolute top-3 left-3">
                                    <span class="inline-flex items-center gap-1 bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-bold shadow-lg">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Principal
                                    </span>
                                </div>
                            </div>

                            <!-- Thumbnails (miniaturas) -->
                            <div v-if="sortedImages.length > 1" class="px-4 py-3 bg-gray-50 dark:bg-gray-900">
                                <div class="flex gap-2 overflow-x-auto pb-2">
                                    <button
                                        v-for="(image, index) in sortedImages"
                                        :key="image.id"
                                        @click="goToImage(index)"
                                        :class="[
                                            'flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all border-2',
                                            currentImageIndex === index 
                                                ? 'border-blue-500 ring-2 ring-blue-200 scale-110' 
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

                        <!-- Sin imágenes -->
                        <div v-else class="mb-4 w-full h-56 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center -mx-6 -mt-6">
                            <div class="text-center">
                                <MapPin :size="48" class="text-gray-400 mx-auto mb-2" />
                                <span class="text-gray-400 text-sm">Sin imágenes disponibles</span>
                            </div>
                        </div>

                        <!-- Información -->
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">
                                    {{ selectedProduct.name }}
                                </h4>
                                <div class="inline-flex items-center gap-2 bg-green-50 px-3 py-2 rounded-lg">
                                    <span class="text-sm text-green-600 font-medium">Precio:</span>
                                    <span class="text-2xl font-bold text-green-600">
                                        Bs. {{ selectedProduct.price.toLocaleString() }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Código</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.codigo_inmueble }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Categoría</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.category || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <MapPin :size="20" class="text-blue-600 mt-0.5 flex-shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Ubicación</p>
                                        <p class="text-sm text-gray-700 mb-2">
                                            {{ selectedProduct.location.address || 'Sin dirección especificada' }}
                                        </p>
                                        <p class="text-xs text-gray-500 font-mono">
                                            {{ selectedProduct.location.latitude.toFixed(6) }}, 
                                            {{ selectedProduct.location.longitude.toFixed(6) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 bg-gray-50 p-3 rounded-lg">
                                <div
                                    :class="[
                                        'w-3 h-3 rounded-full',
                                        selectedProduct.location.is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400'
                                    ]"
                                ></div>
                                <span class="text-sm font-medium" :class="selectedProduct.location.is_active ? 'text-green-600' : 'text-gray-500'">
                                    {{ selectedProduct.location.is_active ? 'Ubicación Activa' : 'Ubicación Inactiva' }}
                                </span>
                            </div>

                            <!-- Botón ver en Google Maps -->
                            <a
                                :href="`https://www.google.com/maps/search/?api=1&query=${selectedProduct.location.latitude},${selectedProduct.location.longitude}`"
                                target="_blank"
                                class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors"
                            >
                                <Navigation :size="20" />
                                Abrir en Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>