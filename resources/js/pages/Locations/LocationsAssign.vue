<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ubicaciones } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Search, MapPin, Save, Navigation } from 'lucide-vue-next';
// import 'leaflet/dist/leaflet.css';
// import { ArrowLeft, Navigation, X, MapPin } from 'lucide-vue-next';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';


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
const address = ref('');
const coordinates = ref<{ lat: number; lng: number } | null>(null);
const isSaving = ref(false);

let map: L.Map | null = null;
let marker: L.Marker | null = null;

// Productos filtrados
const filteredProducts = computed(() => {
    let products = props.productsSinUbicacion;

    // Filtrar por categoría
    if (selectedCategory.value) {
        const categoryName = props.categorias.find(c => c.id === selectedCategory.value)?.category_name;
        products = products.filter(p => p.category === categoryName);
    }

    // Filtrar por búsqueda
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        products = products.filter(p => 
            p.name.toLowerCase().includes(query) ||
            p.codigo_inmueble.toLowerCase().includes(query)
        );
    }

    return products;
});

// Validación para habilitar botón guardar
const canSave = computed(() => {
    return selectedProduct.value && coordinates.value;
});

// Inicializar mapa
const initMap = () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView(
        [props.defaultCenter.lat, props.defaultCenter.lng],
        13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Click en el mapa para colocar marcador
    map.on('click', (e: L.LeafletMouseEvent) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });
};

// Colocar marcador en el mapa
const placeMarker = (lat: number, lng: number) => {
    if (!map) return;

    // Remover marcador anterior
    if (marker) {
        map.removeLayer(marker);
    }

    // Crear nuevo marcador
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

    // Actualizar coordenadas
    coordinates.value = { lat, lng };

    // Evento cuando se arrastra el marcador
    marker.on('dragend', (e: L.DragEndEvent) => {
        const position = e.target.getLatLng();
        coordinates.value = { lat: position.lat, lng: position.lng };
    });
};

// Centrar en mi ubicación
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
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
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

// Seleccionar producto
const selectProduct = (product: Product) => {
    selectedProduct.value = product;
};

// Guardar ubicación
const saveLocation = async () => {
    if (!selectedProduct.value || !coordinates.value) return;

    isSaving.value = true;

    try {
        const response = await axios.post(`/ubicaciones/api/${selectedProduct.value.id}`, {
            latitude: coordinates.value.lat,
            longitude: coordinates.value.lng,
            address: address.value || null,
            is_active: true,
        });

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: '✅ Ubicación guardada',
                detail: `Ubicación asignada a "${selectedProduct.value.name}"`,
                life: 3000
            });

            // Resetear formulario
            selectedProduct.value = null;
            coordinates.value = null;
            address.value = '';
            
            // Remover marcador
            if (marker && map) {
                map.removeLayer(marker);
                marker = null;
            }

            // Recargar página para actualizar lista
            setTimeout(() => {
                router.reload();
            }, 1500);
        }
    } catch (error: any) {
        console.error('Error guardando ubicación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo guardar la ubicación',
            life: 4000
        });
    } finally {
        isSaving.value = false;
    }
};

// Limpiar selección
const clearSelection = () => {
    selectedProduct.value = null;
    coordinates.value = null;
    address.value = '';
    
    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
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
    <Head title="Asignar Ubicación" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-140px)] relative">
            <!-- MAPA (Izquierda - 70%) -->
            <div class="flex-1 relative">
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

                <!-- Info flotante -->
                <div class="absolute bottom-4 left-4 z-[1000] bg-white/95 backdrop-blur-sm rounded-lg shadow-xl p-4 max-w-sm">
                    <h3 class="font-bold text-sm mb-2 text-gray-800 flex items-center gap-2">
                        <MapPin :size="18" class="text-green-600" />
                        Instrucciones
                    </h3>
                    <div class="space-y-1 text-xs text-gray-600">
                        <p>1️⃣ Selecciona un producto del panel derecho</p>
                        <p>2️⃣ Haz clic en el mapa para marcar la ubicación</p>
                        <p>3️⃣ Arrastra el marcador para ajustar</p>
                        <p>4️⃣ Guarda la ubicación</p>
                    </div>
                </div>
            </div>

            <!-- PANEL LATERAL (Derecha - 30%) -->
            <div class="w-[400px] bg-white shadow-2xl overflow-y-auto border-l border-gray-200">
                <div class="sticky top-0 bg-gradient-to-r from-green-600 to-green-700 text-white p-6 z-10">
                    <h2 class="text-xl font-bold mb-2">Asignar Ubicación</h2>
                    <p class="text-sm text-green-100">
                        Productos disponibles: {{ filteredProducts.length }}
                    </p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Filtros -->
                    <div class="space-y-4">
                        <!-- Buscador -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Buscar Producto
                            </label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="18" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Nombre o código..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Filtro por categoría -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Filtrar por Categoría
                            </label>
                            <select
                                v-model="selectedCategory"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            >
                                <option :value="null">Todas las categorías</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.category_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-200" />

                    <!-- Lista de productos -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            Selecciona un Producto
                        </h3>

                        <div v-if="filteredProducts.length === 0" class="text-center py-8 text-gray-500">
                            <p class="text-sm">No hay productos disponibles</p>
                        </div>

                        <div v-else class="space-y-2 max-h-[300px] overflow-y-auto">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                @click="selectProduct(product)"
                                :class="[
                                    'w-full text-left p-3 rounded-lg border-2 transition-all',
                                    selectedProduct?.id === product.id
                                        ? 'border-green-500 bg-green-50'
                                        : 'border-gray-200 hover:border-green-300 hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-start gap-3">
                                    <img
                                        v-if="product.default_image"
                                        :src="product.default_image"
                                        :alt="product.name"
                                        class="w-16 h-16 object-cover rounded"
                                    />
                                    <div v-else class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">Sin img</span>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm text-gray-900 truncate">
                                            {{ product.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ product.codigo_inmueble }}</p>
                                        <p class="text-xs text-gray-600 mt-1">{{ product.category || 'Sin categoría' }}</p>
                                        <p class="text-sm font-semibold text-green-600 mt-1">
                                            Bs. {{ product.price.toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <hr class="border-gray-200" />

                    <!-- Producto seleccionado -->
                    <div v-if="selectedProduct" class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-green-800 mb-2">✅ Producto Seleccionado</h3>
                        <p class="text-sm font-medium text-gray-900">{{ selectedProduct.name }}</p>
                        <p class="text-xs text-gray-600">{{ selectedProduct.codigo_inmueble }}</p>
                    </div>

                    <!-- Coordenadas -->
                    <div v-if="coordinates" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-800 mb-2">📍 Coordenadas</h3>
                        <p class="text-xs text-gray-700">
                            <span class="font-medium">Lat:</span> {{ coordinates.lat.toFixed(6) }}
                        </p>
                        <p class="text-xs text-gray-700">
                            <span class="font-medium">Lng:</span> {{ coordinates.lng.toFixed(6) }}
                        </p>
                    </div>

                    <!-- Campo de dirección -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Dirección (opcional)
                        </label>
                        <textarea
                            v-model="address"
                            placeholder="Ej: Av. Arce #2081, Zona San Jorge"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                        ></textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div class="space-y-2">
                        <button
                            @click="saveLocation"
                            :disabled="!canSave || isSaving"
                            :class="[
                                'w-full py-3 rounded-lg font-semibold transition-all flex items-center justify-center gap-2',
                                canSave && !isSaving
                                    ? 'bg-green-600 hover:bg-green-700 text-white shadow-lg hover:shadow-xl'
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            ]"
                        >
                            <Save :size="20" />
                            <span>{{ isSaving ? 'Guardando...' : 'Guardar Ubicación' }}</span>
                        </button>

                        <button
                            v-if="selectedProduct"
                            @click="clearSelection"
                            class="w-full py-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-all"
                        >
                            Limpiar Selección
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>