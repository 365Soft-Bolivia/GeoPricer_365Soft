<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { admin } from '@/routes-custom';

const { ubicaciones } = admin;
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Search, MapPin, Save, Navigation, X, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// Fix Leaflet icons
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
    operacion?: string;
}

interface Category {
    id: number;
    category_name: string;
}

interface DefaultCenter {
    lat: number;
    lng: number;
}

const props = defineProps<{
    productsSinUbicacion: Product[];
    categorias: Category[];
    defaultCenter: DefaultCenter;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ubicaciones', href: ubicaciones().url },
    { title: 'Asignar Ubicación', href: '/ubicaciones/asignar' },
];

const toast = useToast();

// Estados
const mapContainer = ref<HTMLElement | null>(null);
const selectedProduct = ref<Product | null>(null);
const selectedCategory = ref<number | null>(null);
const searchQuery = ref('');
const coordinates = ref<{ lat: number; lng: number } | null>(null);
const isSaving = ref(false);

// ✅ AGREGADO: Variables de ubicación que faltaban
const isLocatingUser = ref(false);
let userLocationMarker: L.Marker | null = null;
let userLocationCircle: L.Circle | null = null;

// Paginación
const currentPage = ref(1);
const itemsPerPage = 8;

let map: L.Map | null = null;
let marker: L.Marker | null = null;

// Filtros
const filteredProducts = computed(() => {
    let products = props.productsSinUbicacion;

    if (selectedCategory.value) {
        const categoryName = props.categorias.find(c => c.id === selectedCategory.value)?.category_name;
        products = products.filter(p => p.category === categoryName);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        products = products.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.codigo_inmueble.toLowerCase().includes(query)
        );
    }

    return products;
});

// Paginación
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage));

const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredProducts.value.slice(start, start + itemsPerPage);
});

const visiblePages = computed(() => {
    const pages: (number | string)[] = [];
    const total = totalPages.value;
    const current = currentPage.value;

    pages.push(1);

    if (total <= 7) {
        for (let i = 2; i <= total; i++) pages.push(i);
    } else {
        if (current <= 3) {
            for (let i = 2; i <= 4; i++) pages.push(i);
            pages.push('...');
            pages.push(total);
        } else if (current >= total - 2) {
            pages.push('...');
            for (let i = total - 3; i <= total; i++) pages.push(i);
        } else {
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) pages.push(i);
            pages.push('...');
            pages.push(total);
        }
    }

    return pages;
});

const resetPage = () => currentPage.value = 1;

const goToPage = (page: number | string) => {
    if (typeof page === 'number' && page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const nextPage = () => currentPage.value < totalPages.value && currentPage.value++;
const prevPage = () => currentPage.value > 1 && currentPage.value--;

// Guardar
const canSave = computed(() => selectedProduct.value && coordinates.value);

const initMap = () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView(
        [props.defaultCenter.lat, props.defaultCenter.lng], 13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    map.on('click', (e) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });
};

const placeMarker = (lat: number, lng: number) => {
    if (!map) return;

    if (marker) map.removeLayer(marker);

    marker = L.marker([lat, lng], {
        draggable: true,
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: iconShadow,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);

    marker.bindPopup('📍 Ubicación seleccionada<br>Arrastra para ajustar').openPopup();

    coordinates.value = { lat, lng };

    marker.on('dragend', (e: any) => {
        const pos = e.target.getLatLng();
        coordinates.value = { lat: pos.lat, lng: pos.lng };
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

const selectProduct = (product: Product) => {
    selectedProduct.value = product;
};

const saveLocation = async () => {
    if (!selectedProduct.value || !coordinates.value) return;
    isSaving.value = true;

    try {
        const url = admin.ubicaciones.api.store(selectedProduct.value.id).url;
        const response = await axios.post(url, {
            latitude: coordinates.value.lat,
            longitude: coordinates.value.lng,
            address: null,
            is_active: true,
        });

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: 'Ubicación guardada',
                detail: `Ubicación asignada a "${selectedProduct.value.name}"`,
                life: 3000
            });

            selectedProduct.value = null;
            coordinates.value = null;

            if (marker && map) {
                map.removeLayer(marker);
                marker = null;
            }

            setTimeout(() => router.reload(), 1500);
        }
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo guardar la ubicación',
            life: 3000
        });
    }

    isSaving.value = false;
};

const clearSelection = () => {
    selectedProduct.value = null;
    coordinates.value = null;

    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
};

const goBack = () => router.visit(ubicaciones().url);

onMounted(() => initMap());
onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Asignar Ubicación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-140px)] relative">
            <!-- MAPA -->
            <div class="flex-1 relative">
                <div ref="mapContainer" class="w-full h-full"></div>

                <!-- Controles superior -->
                <div class="absolute top-4 left-4 right-4 z-[1000] flex items-center justify-between">
                    <button
                        @click="goBack"
                        class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2"
                    >
                        <ArrowLeft :size="20" />
                        Volver
                    </button>

                    <button
                        @click="centerOnMyLocation"
                        :disabled="isLocatingUser"
                        :class="[
                            'p-3 rounded-lg shadow-lg transition-all',
                            isLocatingUser ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
                        ]"
                        class="text-white"
                        title="Centrar en mi ubicación"
                    >
                        <Navigation :size="24" :class="isLocatingUser ? 'animate-pulse' : ''" />
                    </button>
                </div>
            </div>

            <!-- PANEL -->
            <div class="w-[30%] min-w-[400px] bg-white shadow-xl flex flex-col">
                <!-- Header -->
                <div class="bg-green-600 text-white p-3 flex-shrink-0">
                    <h2 class="text-lg font-bold">Asignar Ubicación</h2>
                    <p class="text-xs text-green-100">{{ filteredProducts.length }} productos disponibles</p>
                </div>

                <!-- Filtros -->
                <div class="p-3 bg-gray-50 border-b flex-shrink-0">
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Buscar -->
                        <div>
                            <label class="text-[10px] font-semibold text-gray-700 mb-1 block">Buscar</label>
                            <div class="relative">
                                <Search class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" :size="12" />
                                <input
                                    v-model="searchQuery"
                                    @input="resetPage"
                                    type="text"
                                    placeholder="Nombre o código..."
                                    class="w-full pl-7 pr-6 py-1.5 text-[10px] border rounded focus:ring-2 focus:ring-green-500"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''; resetPage()"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X :size="12" />
                                </button>
                            </div>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="text-[10px] font-semibold text-gray-700 mb-1 block">Categoría</label>
                            <select
                                v-model="selectedCategory"
                                @change="resetPage"
                                class="w-full px-2 py-1.5 text-[10px] border rounded focus:ring-2 focus:ring-green-500"
                            >
                                <option :value="null">Todas</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.category_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ✅ LISTA CON SCROLL (SOLO AQUÍ) -->
                <!-- <div class="flex-1 overflow-y-auto p-3" style="max-height: calc(100vh - 500px);"> -->
                    <div class="flex-1 overflow-y-auto px-3 pb-3">
                    <div class="sticky top-0 z-30 bg-white border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-700 py-2 px-1">
                                    Propiedades ({{ filteredProducts.length }})
                        </h3>
                    </div>




                    <!-- No hay -->
                    <div v-if="filteredProducts.length === 0" class="text-center text-gray-500 text-xs py-8">
                        <MapPin :size="28" class="mx-auto mb-2 text-gray-300" />
                        No hay productos
                    </div>

                    <!-- Grid con scroll -->
                    <div v-else class="grid grid-cols-2 gap-1.5">
                        <button
                            v-for="product in paginatedProducts"
                            :key="product.id"
                            @click="selectProduct(product)"
                            :class="[
                                'p-2 border rounded relative text-left transition-all',
                                selectedProduct?.id === product.id
                                    ? 'border-green-500 bg-green-50 shadow ring-1 ring-green-200'
                                    : 'border-gray-200 hover:bg-gray-50 hover:border-green-300'
                            ]"
                        >
                            <!-- Badge -->
                            <div v-if="selectedProduct?.id === product.id" class="absolute top-1 right-1 bg-green-500 text-white rounded-full p-0.5">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                </svg>
                            </div>

                            <p class="font-semibold text-[10px] line-clamp-2 mb-0.5" :title="product.name">
                                {{ product.name }}
                            </p>
                            <p class="text-[9px] text-gray-500">{{ product.codigo_inmueble }}</p>
                            <div class="flex justify-between items-center text-[9px] text-gray-700 mt-1">
                                <span class="truncate">{{ product.category || 'N/A' }}</span>
                                <span class="text-green-600 font-bold ml-1 flex-shrink-0">
                                    {{ (product.price / 1000).toFixed(0) }}k
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- FOOTER (GUARDAR + PAGINACIÓN) -->
                <div class="p-2 border-t bg-gray-50 space-y-2 flex-shrink-0">
                    <!-- Producto -->
                    <div v-if="selectedProduct" class="bg-green-50 border border-green-200 p-2 rounded">
                        <div class="flex justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] font-semibold text-green-800 mb-0.5">✅ Seleccionado</p>
                                <p class="text-[11px] font-medium truncate">{{ selectedProduct.name }}</p>
                                <p class="text-[9px] text-gray-600">{{ selectedProduct.codigo_inmueble }}</p>
                            </div>
                            <button 
                                @click="clearSelection" 
                                class="text-green-700 hover:text-green-900 flex-shrink-0"
                                title="Limpiar selección"
                            >
                                <X :size="14" />
                            </button>
                        </div>
                    </div>

                    <!-- Coordenadas -->
                    <div v-if="coordinates" class="bg-blue-50 border border-blue-200 p-2 rounded">
                        <p class="text-[9px] font-semibold text-blue-800 mb-1">📍 Coordenadas</p>
                        <div class="grid grid-cols-2 gap-2 text-[9px] text-gray-700">
                            <div><span class="font-medium">Lat:</span> {{ coordinates.lat.toFixed(4) }}</div>
                            <div><span class="font-medium">Lng:</span> {{ coordinates.lng.toFixed(4) }}</div>
                        </div>
                    </div>

                    <!-- GUARDAR -->
                    <button
                        @click="saveLocation"
                        :disabled="!canSave || isSaving"
                        :class="[
                            'w-full py-2 rounded-lg font-semibold text-xs flex items-center justify-center gap-2 transition-all',
                            canSave && !isSaving
                                ? 'bg-green-600 hover:bg-green-700 text-white shadow-lg hover:shadow-xl'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        ]"
                    >
                        <Save :size="14" />
                        {{ isSaving ? 'Guardando...' : 'Guardar Ubicación' }}
                    </button>

                    <!-- PAGINACIÓN -->
                    <div v-if="totalPages > 1" class="pt-2 border-t">
                        <div class="flex items-center justify-center gap-1">
                            <!-- Anterior -->
                            <button
                                @click="prevPage"
                                :disabled="currentPage === 1"
                                class="text-[10px] px-2 py-1 rounded transition-colors"
                                :class="currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-blue-600 hover:bg-blue-50'"
                            >
                                <ChevronLeft :size="14" />
                            </button>

                            <!-- Números -->
                            <button
                                v-for="(page, index) in visiblePages"
                                :key="index"
                                @click="goToPage(page)"
                                :disabled="page === '...'"
                                :class="[
                                    'min-w-[24px] h-6 text-[10px] rounded transition-colors',
                                    page === '...' ? 'text-gray-400 cursor-default' :
                                    currentPage === page ? 'bg-blue-600 text-white font-bold' : 'hover:bg-gray-200 text-gray-700'
                                ]"
                            >
                                {{ page }}
                            </button>

                            <!-- Siguiente -->
                            <button
                                @click="nextPage"
                                :disabled="currentPage === totalPages"
                                class="text-[10px] px-2 py-1 rounded transition-colors"
                                :class="currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-blue-600 hover:bg-blue-50'"
                            >
                                <ChevronRight :size="14" />
                            </button>
                        </div>

                        <!-- Info página actual -->
                        <p class="text-center text-[9px] text-gray-500 mt-1">
                            Página {{ currentPage }} de {{ totalPages }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Scroll personalizado solo para la lista */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>