<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { admin } from '@/routes-custom';

const { ubicaciones } = admin;
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
import proyectos from '@/routes/admin/proyectos';
import ProyectosShow from '../Proyectos/ProyectosShow.vue';

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

const defaultCenter = { lat: -17.0000, lng: -64.0000 };

const sortedImages = computed(() => {
    if (!selectedProduct.value?.images?.length) return [];
    
    return [...selectedProduct.value.images].sort((a, b) => {
        if (a.is_primary && !b.is_primary) return -1;
        if (!a.is_primary && b.is_primary) return 1;
        return a.order - b.order;
    });
});

const getImageUrl = (imagePath: string) => {
    return `/storage/${imagePath}`;
};

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

    map = L.map(mapContainer.value).setView([defaultCenter.lat, defaultCenter.lng], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // ✅ CORREGIDO: Función del popup con manejo correcto de imágenes
    const popupContent = (product: Product) => {
        const images = product.images && product.images.length > 0
            ? product.images.sort((a, b) => (a.is_primary ? -1 : 1))
            : [];

        const imageUrl = (path: string) => `/storage/${path}`;
        const hasImages = images.length > 0;
        
        // Primera imagen o null
        const firstImage = hasImages ? imageUrl(images[0].image_path) : null;

        return `
            <div class="popup-card" 
                 style="width:260px;border-radius:12px;overflow:hidden;
                        position:relative;
                        box-shadow:0 4px 12px rgba(0,0,0,0.2);
                        font-family:sans-serif;background:white;">
                
                <!-- IMAGEN O ÍCONO -->
                <div class="image-container" 
                     data-images='${JSON.stringify(images.map(i => imageUrl(i.image_path)))}'
                     data-has-images="${hasImages}"
                     style="position:relative;width:100%;height:160px;overflow:hidden;">
                    
                    ${hasImages ? `
                        <!-- Imagen -->
                        <img class="carousel-image"
                            src="${firstImage}"
                            style="width:100%;height:160px;object-fit:cover;display:block;"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                        />
                        <!-- Fallback: Ícono de casa -->
                        <div class="fallback-icon" 
                             style="display:none;width:100%;height:100%;
                                    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    align-items:center;justify-content:center;color:white;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" 
                                 fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        
                        ${images.length > 1 ? `
                            <!-- Botones navegación -->
                            <button class="carousel-prev"
                                style="position:absolute;top:50%;left:8px;transform:translateY(-50%);
                                       background:rgba(0,0,0,0.7);color:white;border:none;
                                       width:32px;height:32px;border-radius:50%;cursor:pointer;
                                       font-size:20px;font-weight:bold;z-index:10;
                                       display:flex;align-items:center;justify-content:center;">
                                ‹
                            </button>
                            <button class="carousel-next"
                                style="position:absolute;top:50%;right:8px;transform:translateY(-50%);
                                       background:rgba(0,0,0,0.7);color:white;border:none;
                                       width:32px;height:32px;border-radius:50%;cursor:pointer;
                                       font-size:20px;font-weight:bold;z-index:10;
                                       display:flex;align-items:center;justify-content:center;">
                                ›
                            </button>
                            
                            <!-- Contador -->
                            <div class="image-counter" 
                                 style="position:absolute;bottom:8px;right:8px;
                                        background:rgba(0,0,0,0.7);color:white;
                                        padding:4px 10px;border-radius:12px;font-size:12px;z-index:10;">
                                <span class="current-index">1</span> / ${images.length}
                            </div>
                        ` : ''}
                    ` : `
                        <!-- No hay imágenes: Mostrar ícono -->
                        <div style="display:flex;width:100%;height:100%;
                                    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    align-items:center;justify-content:center;color:white;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" 
                                 fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                    `}
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

                    <a
                        href="/admin/proyectos/${product.id}"
                        target="_blank"
                        style="
                            display:inline-block;width:100%;margin-top:12px;background:#caa86f;
                            color:white;padding:8px 0;border-radius:6px;border:none;
                            font-weight:bold;cursor:pointer;text-align:center;text-decoration:none;
                        ">
                        Ver Detalle
                    </a>
                </div>
            </div>
        `;
    };

    // Agregar marcadores
    props.productsConUbicacion.forEach((product) => {
        if (!product.location.is_active) return;

        const marker = L.marker([product.location.latitude, product.location.longitude])
            .addTo(map!);

        marker.bindPopup(popupContent(product), {
            maxWidth: 280,
            className: 'custom-popup'
        });

        // ✅ CORREGIDO: Event listener del carrusel
        marker.on("popupopen", (e) => {
            const popup = e.popup.getElement();
            if (!popup) return;

            const container = popup.querySelector(".image-container");
            if (!container) return;

            const hasImages = container.getAttribute("data-has-images") === "true";
            if (!hasImages) return; // No hay imágenes, no hacer nada

            const imgEl = popup.querySelector(".carousel-image") as HTMLImageElement;
            const prevBtn = popup.querySelector(".carousel-prev");
            const nextBtn = popup.querySelector(".carousel-next");
            const counterEl = popup.querySelector(".current-index");

            const images = JSON.parse(container.getAttribute("data-images") || "[]");
            if (images.length === 0) return;

            let index = 0;

            const updateImage = () => {
                if (imgEl) {
                    imgEl.src = images[index];
                    // Asegurar que se muestre la imagen
                    imgEl.style.display = 'block';
                    const fallbackIcon = imgEl.nextElementSibling as HTMLElement;
                    if (fallbackIcon) fallbackIcon.style.display = 'none';
                }
                if (counterEl) {
                    counterEl.textContent = (index + 1).toString();
                }
            };

            prevBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                index = (index - 1 + images.length) % images.length;
                updateImage();
            });

            nextBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                index = (index + 1) % images.length;
                updateImage();
            });

            // Manejar error de carga de imagen
            imgEl?.addEventListener('error', () => {
                imgEl.style.display = 'none';
                const fallbackIcon = imgEl.nextElementSibling as HTMLElement;
                if (fallbackIcon) fallbackIcon.style.display = 'flex';
            });
        });

        marker.on('click', () => {
            selectedProduct.value = product;
        });

        markers.push(marker);
    });
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
                                width: 17px;
                                height: 17px;
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
                    iconSize: [10, 10],
                    iconAnchor: [10, 10]
                });

                userLocationMarker = L.marker([latitude, longitude], {
                    icon: pulsingIcon
                }).addTo(map!)
                    .bindPopup(`
                        <div class="w-max text-center text-xs">
                            <p class="font-bold text-blue-600">📍 Tu ubicación actual</p>
                        </div>
                    `)
                    .openPopup();

                setTimeout(() => {
                    if (userLocationMarker && map) {
                        userLocationMarker.closePopup();
                    }
                }, 1000);

                isLocatingUser.value = false;
            },
            (error) => {
                isLocatingUser.value = false;
                console.error('Error obteniendo ubicación:', error);
                
                let errorMessage = 'No se pudo obtener tu ubicación.';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Permiso de ubicación denegado.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'La información de ubicación no está disponible.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'La solicitud de ubicación ha expirado.';
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
                <button
                    @click="goBack"
                    class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all"
                >
                    <ArrowLeft :size="20" />
                    <span class="font-semibold">Volver</span>
                </button>

                <div class="flex items-center gap-2">
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
                        <div v-if="sortedImages.length > 0" class="mb-6 -mx-6 -mt-6">
                            <div class="relative group">
                                <img
                                    :src="getImageUrl(sortedImages[currentImageIndex].image_path)"
                                    :alt="sortedImages[currentImageIndex].original_name"
                                    class="w-full h-64 object-cover"
                                />
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                                <template v-if="sortedImages.length > 1">
                                    <button
                                        @click="prevImage"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="nextImage"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div class="absolute bottom-3 right-3 bg-black/70 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                                        {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                                    </div>
                                </template>

                                <div v-if="sortedImages[currentImageIndex].is_primary" class="absolute top-3 left-3">
                                    <span class="inline-flex items-center gap-1 bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-bold shadow-lg">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Principal
                                    </span>
                                </div>
                            </div>

                            <div v-if="sortedImages.length > 1" class="px-4 py-3 bg-gray-50">
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

                        <div v-else class="mb-4 w-full h-56 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center -mx-6 -mt-6">
                            <div class="text-center">
                                <MapPin :size="48" class="text-gray-400 mx-auto mb-2" />
                                <span class="text-gray-400 text-sm">Sin imágenes disponibles</span>
                            </div>
                        </div>

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