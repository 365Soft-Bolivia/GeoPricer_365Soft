<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ubicaciones } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Navigation, X } from 'lucide-vue-next';

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

interface Product {
    id: number;
    name: string;
    codigo_inmueble: string;
    price: number;
    default_image: string | null;
    category: string | null;
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
let map: L.Map | null = null;
const markers: L.Marker[] = [];

const defaultCenter = { lat: -16.5000, lng: -68.1500 }; // La Paz, Bolivia

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
        const popupContent = `
            <div class="p-2 min-w-[200px]">
                <h3 class="font-bold text-sm mb-1">${product.name}</h3>
                <p class="text-xs text-gray-600 mb-1">Código: ${product.codigo_inmueble}</p>
                <p class="text-xs text-gray-600 mb-1">Categoría: ${product.category || 'N/A'}</p>
                <p class="text-sm font-semibold text-green-600">Bs. ${product.price.toLocaleString()}</p>
            </div>
        `;
        
        marker.bindPopup(popupContent);

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
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                map!.setView([latitude, longitude], 15);
                
                // Agregar marcador temporal de ubicación actual
                L.marker([latitude, longitude], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                        shadowUrl: iconShadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map!)
                    .bindPopup('📍 Tu ubicación actual')
                    .openPopup();
            },
            (error) => {
                console.error('Error obteniendo ubicación:', error);
                alert('No se pudo obtener tu ubicación. Verifica los permisos del navegador.');
            }
        );
    } else {
        alert('Tu navegador no soporta geolocalización');
    }
};

const closeDetails = () => {
    selectedProduct.value = null;
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

            <!-- Botón Volver -->
            <button
                @click="goBack"
                class="absolute top-4 left-4 z-[1000] bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all"
            >
                <ArrowLeft :size="20" />
                <span class="font-semibold">Volver</span>
            </button>

            <!-- Botón Mi Ubicación -->
            <button
                @click="centerOnMyLocation"
                class="absolute top-4 right-4 z-[1000] bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-lg shadow-lg transition-all"
                title="Centrar en mi ubicación"
            >
                <Navigation :size="24" />
            </button>

            <!-- Info Box -->
            <div class="absolute bottom-4 left-4 z-[1000] bg-white rounded-lg shadow-xl p-4 max-w-xs">
                <h3 class="font-bold text-lg mb-2 text-gray-800">📍 Mapa de Propiedades</h3>
                <div class="space-y-1 text-sm text-gray-600">
                    <p>• Haz clic en los marcadores para ver detalles</p>
                    <p>• Total de propiedades: <span class="font-semibold text-gray-900">{{ productsConUbicacion.length }}</span></p>
                    <p>• Usa el scroll para hacer zoom</p>
                </div>
            </div>

            <!-- Panel de Detalles del Producto (Lateral Derecho) -->
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
                    <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-gray-800">Detalles de la Propiedad</h3>
                        <button
                            @click="closeDetails"
                            class="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Imagen -->
                        <div v-if="selectedProduct.default_image" class="mb-4">
                            <img
                                :src="selectedProduct.default_image"
                                :alt="selectedProduct.name"
                                class="w-full h-48 object-cover rounded-lg shadow-md"
                            />
                        </div>
                        <div v-else class="mb-4 w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400 text-sm">Sin imagen</span>
                        </div>

                        <!-- Información -->
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">
                                    {{ selectedProduct.name }}
                                </h4>
                                <p class="text-2xl font-bold text-green-600">
                                    Bs. {{ selectedProduct.price.toLocaleString() }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Código</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.codigo_inmueble }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Categoría</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.category || 'N/A' }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 mb-1">Ubicación</p>
                                <p class="text-sm text-gray-700">
                                    {{ selectedProduct.location.address || 'Sin dirección especificada' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Lat: {{ selectedProduct.location.latitude.toFixed(6) }}, 
                                    Lng: {{ selectedProduct.location.longitude.toFixed(6) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <div
                                    :class="[
                                        'w-3 h-3 rounded-full',
                                        selectedProduct.location.is_active ? 'bg-green-500' : 'bg-gray-400'
                                    ]"
                                ></div>
                                <span class="text-sm font-medium" :class="selectedProduct.location.is_active ? 'text-green-600' : 'text-gray-500'">
                                    {{ selectedProduct.location.is_active ? 'Ubicación Activa' : 'Ubicación Inactiva' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>