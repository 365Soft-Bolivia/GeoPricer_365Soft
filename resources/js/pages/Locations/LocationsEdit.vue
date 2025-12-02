<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { admin } from '@/routes-custom';

const { ubicaciones } = admin;
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, onUnmounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Search, MapPin, Save, Trash2, Power, Edit3, X } from 'lucide-vue-next';
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
    { title: 'Editar Ubicaciones', href: '/ubicaciones/editar' },
];

const toast = useToast();

// Estados
const mapContainer = ref<HTMLElement | null>(null);
const selectedProduct = ref<Product | null>(null);
const searchQuery = ref('');
const editMode = ref(false);
const address = ref('');
const coordinates = ref<{ lat: number; lng: number } | null>(null);
const isSaving = ref(false);
const isDeleting = ref(false);
const isToggling = ref(false);
const showDeleteConfirm = ref(false);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
const allMarkers: L.Marker[] = [];

// Computed
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.productsConUbicacion;
    const query = searchQuery.value.toLowerCase();
    return props.productsConUbicacion.filter(p =>
        p.name.toLowerCase().includes(query) ||
        p.codigo_inmueble.toLowerCase().includes(query) ||
        p.category?.toLowerCase().includes(query)
    );
});
const formattedCoordinates = computed(() => {
    if (!coordinates.value || typeof coordinates.value.lat !== 'number' || typeof coordinates.value.lng !== 'number') {
        return { lat: 'N/A', lng: 'N/A' };
    }
    return {
        lat: coordinates.value.lat.toFixed(6),
        lng: coordinates.value.lng.toFixed(6)
    };
});
// Mapa
const initMap = () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView([-16.5000, -68.1500], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Agregar marcadores
    props.productsConUbicacion.forEach((product) => {
        addProductMarker(product);
    });

    // Ajustar vista
    if (allMarkers.length > 0) {
        const group = L.featureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

const addProductMarker = (product: Product) => {
    if (!map) return;

    const markerIcon = L.icon({
        iconUrl: product.location.is_active
            ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png'
            : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
        shadowUrl: iconShadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const productMarker = L.marker(
        [product.location.latitude, product.location.longitude],
        { icon: markerIcon }
    ).addTo(map);

    const popupContent = `
        <div class="p-2">
            <p class="font-bold text-sm">${product.name}</p>
            <p class="text-xs text-gray-600">${product.codigo_inmueble}</p>
            <button onclick="window.selectProductFromMap(${product.id})" 
                    class="mt-2 bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700">
                Editar
            </button>
        </div>
    `;

    productMarker.bindPopup(popupContent);
    allMarkers.push(productMarker);
};

// Exponer función global para el popup
(window as any).selectProductFromMap = (productId: number) => {
    const product = props.productsConUbicacion.find(p => p.id === productId);
    if (product) {
        selectProduct(product);
    }
};

// Funciones principales
const selectProduct = (product: Product) => {
    selectedProduct.value = product;
    editMode.value = false;
    address.value = product.location.address || '';
    coordinates.value = {
        lat: product.location.latitude,
        lng: product.location.longitude
    };

    // Centrar mapa
    if (map) {
        map.setView([product.location.latitude, product.location.longitude], 16);
    }

    // Limpiar marcador de edición
    if (marker) {
        map?.removeLayer(marker);
        marker = null;
    }
};

const startEdit = () => {
    if (!selectedProduct.value || !map) return;

    editMode.value = true;

    // Limpiar marcador anterior
    if (marker) {
        map.removeLayer(marker);
    }

    // Crear marcador editable
    marker = L.marker([coordinates.value!.lat, coordinates.value!.lng], {
        draggable: true,
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
            shadowUrl: iconShadow,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);

    marker.bindPopup('🔧 Arrastra para cambiar ubicación').openPopup();

    marker.on('dragend', (e: L.DragEndEvent) => {
        const position = e.target.getLatLng();
        coordinates.value = { lat: position.lat, lng: position.lng };
    });
};

const cancelEdit = () => {
    editMode.value = false;

    if (marker) {
        map?.removeLayer(marker);
        marker = null;
    }

    // Restaurar valores originales
    if (selectedProduct.value) {
        coordinates.value = {
            lat: selectedProduct.value.location.latitude,
            lng: selectedProduct.value.location.longitude
        };
        address.value = selectedProduct.value.location.address || '';
    }
};

const saveChanges = async () => {
    if (!selectedProduct.value || !coordinates.value) return;

    isSaving.value = true;

    try {
        const response = await axios.post(admin.ubicaciones.api.store(selectedProduct.value.id).url, {
            latitude: coordinates.value.lat,
            longitude: coordinates.value.lng,
            address: address.value || null,
            is_active: selectedProduct.value.location.is_active,
        });

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: '✅ Ubicación actualizada',
                detail: `Cambios guardados para "${selectedProduct.value.name}"`,
                life: 3000
            });

            editMode.value = false;
            
            if (marker) {
                map?.removeLayer(marker);
                marker = null;
            }

            router.reload();
        }
    } catch (error: any) {
        console.error('Error actualizando ubicación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo actualizar la ubicación',
            life: 4000
        });
    } finally {
        isSaving.value = false;
    }
};

const toggleActive = async () => {
    if (!selectedProduct.value) return;

    isToggling.value = true;

    try {
        const response = await axios.post(`/ubicaciones/api/${selectedProduct.value.id}/toggle-status`);

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: response.data.data.is_active ? '✅ Ubicación activada' : '⚠️ Ubicación desactivada',
                detail: response.data.message,
                life: 3000
            });

            router.reload();
        }
    } catch (error: any) {
        console.error('Error cambiando estado:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo cambiar el estado',
            life: 4000
        });
    } finally {
        isToggling.value = false;
    }
};

const deleteLocation = async () => {
    if (!selectedProduct.value) return;

    isDeleting.value = true;

    try {
        const response = await axios.delete(`/ubicaciones/api/${selectedProduct.value.id}`);

        if (response.data.success) {
            toast.add({
                severity: 'success',
                summary: '🗑️ Ubicación eliminada',
                detail: response.data.message,
                life: 3000
            });

            showDeleteConfirm.value = false;
            selectedProduct.value = null;
            router.reload();
        }
    } catch (error: any) {
        console.error('Error eliminando ubicación:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo eliminar la ubicación',
            life: 4000
        });
    } finally {
        isDeleting.value = false;
    }
};

const goBack = () => {
    router.visit(ubicaciones().url);
};

const clearSelection = () => {
    selectedProduct.value = null;
    editMode.value = false;
    if (marker) {
        map?.removeLayer(marker);
        marker = null;
    }
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Editar Ubicaciones" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-140px)] relative">
            <!-- MAPA -->
            <div class="flex-1 relative">
                <div ref="mapContainer" class="w-full h-full"></div>

                <!-- Barra superior FIXED -->
                <div class="absolute top-4 left-4 right-4 z-[1000] flex items-center justify-between">
                    <!-- Lado izquierdo: Botones -->
                    <div class="flex items-center gap-3">
                        <!-- Botón Volver -->
                        <button
                            @click="goBack"
                            class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all font-semibold border border-gray-300"
                        >
                            <ArrowLeft :size="18" />
                            <span>Volver</span>
                        </button>

                        <!-- Botón Editar - SIEMPRE VISIBLE cuando hay producto seleccionado -->
                        <button
                            v-if="selectedProduct && !editMode"
                            @click="startEdit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all font-semibold"
                        >
                            <Edit3 :size="18" />
                            <span>Editar Ubicación</span>
                        </button>

                        <!-- Botones Guardar/Cancelar - MODO EDICIÓN -->
                        <div v-if="editMode" class="flex gap-2">
                            <button
                                @click="saveChanges"
                                :disabled="isSaving"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save :size="18" />
                                <span>{{ isSaving ? 'Guardando...' : 'Guardar' }}</span>
                            </button>

                            <button
                                @click="cancelEdit"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all font-semibold"
                            >
                                <X :size="18" />
                                <span>Cancelar</span>
                            </button>
                        </div>
                    </div>

                    <!-- Centro: Nombre del producto seleccionado -->
                    <div v-if="selectedProduct" class="flex-1 mx-4 text-center">
                        <div class="inline-block bg-white/95 backdrop-blur-sm px-6 py-3 rounded-lg shadow-lg border border-gray-300">
                            <p class="font-bold text-gray-900 text-lg">{{ selectedProduct.name }}</p>
                            <p class="text-sm text-gray-600">{{ selectedProduct.codigo_inmueble }}</p>
                            <div class="flex items-center justify-center gap-2 mt-1">
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full',
                                        selectedProduct.location.is_active ? 'bg-green-500' : 'bg-gray-400'
                                    ]"
                                ></div>
                                <span class="text-xs" :class="selectedProduct.location.is_active ? 'text-green-600' : 'text-gray-500'">
                                    {{ selectedProduct.location.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Lado derecho: Espacio para balance -->
                    <div v-if="!selectedProduct" class="flex-1"></div>
                </div>

                <!-- Panel de instrucciones -->
                <!-- <div class="absolute bottom-4 left-4 z-[999] bg-white/95 backdrop-blur-sm rounded-lg shadow-xl p-4 max-w-xs border border-gray-300">
                    <h3 class="font-bold text-sm mb-2 text-gray-800 flex items-center gap-2">
                        <MapPin :size="16" class="text-purple-600" />
                        Instrucciones
                    </h3>
                    <div class="space-y-1 text-xs text-gray-600">
                        <p>1. Selecciona un producto del panel o mapa</p>
                        <p>2. Haz clic en "Editar Ubicación"</p>
                        <p>3. Arrastra el marcador naranja</p>
                        <p>4. Guarda los cambios</p>
                    </div>
                    <div v-if="editMode" class="mt-3 pt-3 border-t border-orange-200">
                        <p class="text-xs font-semibold text-orange-600 flex items-center gap-1">
                            <Edit3 :size="12" />
                            Modo edición activo - Arrastra el marcador naranja
                        </p>
                    </div>
                </div> -->
            </div>

            <!-- PANEL LATERAL -->
            <div class="w-[400px] bg-white shadow-xl border-l border-gray-300 flex flex-col">
                <!-- Header del panel -->
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-6">
                    <h2 class="text-xl font-bold mb-2">Editar Ubicaciones</h2>
                    <p class="text-sm text-purple-100">
                        {{ selectedProduct ? 'Editando ubicación' : `Total: ${filteredProducts.length} propiedades` }}
                    </p>
                </div>

                <!-- Contenido del panel -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
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
                                placeholder="Buscar por nombre, código o categoría..."
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <!-- Lista de productos o detalles -->
                    <div v-if="!selectedProduct">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">
                            Selecciona un Producto para Editar
                        </h3>

                        <div v-if="filteredProducts.length === 0" class="text-center py-8 text-gray-500">
                            <p class="text-sm">No se encontraron productos</p>
                        </div>

                        <div v-else class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                @click="selectProduct(product)"
                                class="w-full text-left p-4 rounded-lg border-2 border-gray-200 hover:border-purple-400 hover:bg-purple-50 transition-all duration-200 hover:shadow-md"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- Imagen -->
                                    <div class="flex-shrink-0">
                                        <img
                                            v-if="product.default_image"
                                            :src="product.default_image"
                                            :alt="product.name"
                                            class="w-16 h-16 object-cover rounded-lg border border-gray-300"
                                        />
                                        <div v-else class="w-16 h-16 bg-gray-200 rounded-lg border border-gray-300 flex items-center justify-center">
                                            <span class="text-gray-400 text-xs text-center">Sin imagen</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Información -->
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 truncate text-sm mb-1">
                                            {{ product.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 mb-1">{{ product.codigo_inmueble }}</p>
                                        <p class="text-xs text-gray-600 mb-2">{{ product.category || 'Sin categoría' }}</p>
                                        
                                        <!-- Estado -->
                                        <div class="flex items-center gap-2">
                                            <div
                                                :class="[
                                                    'w-2 h-2 rounded-full',
                                                    product.location.is_active ? 'bg-green-500' : 'bg-gray-400'
                                                ]"
                                            ></div>
                                            <span class="text-xs font-medium" :class="product.location.is_active ? 'text-green-600' : 'text-gray-500'">
                                                {{ product.location.is_active ? 'Ubicación activa' : 'Ubicación inactiva' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Detalles del producto seleccionado -->
                    <div v-else class="space-y-6">
                        <!-- Header de detalles -->
                        <div class="flex items-center justify-between mb-2">
                            <button
                                @click="clearSelection"
                                class="text-purple-600 hover:text-purple-700 flex items-center gap-2 font-semibold text-sm"
                            >
                                <ArrowLeft :size="16" />
                                Volver a la lista
                            </button>
                        </div>

                        <!-- Tarjeta de producto -->
                        <div class="bg-gradient-to-br from-purple-50 to-blue-50 p-4 rounded-xl border-2 border-purple-200">
                            <div class="flex items-start gap-4">
                                <img
                                    v-if="selectedProduct.default_image"
                                    :src="selectedProduct.default_image"
                                    :alt="selectedProduct.name"
                                    class="w-20 h-20 object-cover rounded-lg border border-purple-300"
                                />
                                <div v-else class="w-20 h-20 bg-gray-200 rounded-lg border border-purple-300 flex items-center justify-center">
                                    <span class="text-gray-400 text-xs text-center">Sin imagen</span>
                                </div>
                                
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-900 text-lg mb-1">
                                        {{ selectedProduct.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-1">{{ selectedProduct.codigo_inmueble }}</p>
                                    <p class="text-sm text-gray-600 mb-2">{{ selectedProduct.category || 'Sin categoría' }}</p>
                                    <p class="text-lg font-bold text-green-600">
                                        Bs. {{ selectedProduct.price.toLocaleString() }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de ubicación -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-700">Estado de la Ubicación</span>
                                <div class="flex items-center gap-2">
                                    <div
                                        :class="[
                                            'w-3 h-3 rounded-full',
                                            selectedProduct.location.is_active ? 'bg-green-500' : 'bg-gray-400'
                                        ]"
                                    ></div>
                                    <span class="text-sm font-medium" :class="selectedProduct.location.is_active ? 'text-green-600' : 'text-gray-500'">
                                        {{ selectedProduct.location.is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                            </div>
                            <button
                                @click="toggleActive"
                                :disabled="isToggling || editMode"
                                :class="[
                                    'w-full py-3 rounded-lg font-semibold transition-all flex items-center justify-center gap-2',
                                    editMode ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                    selectedProduct.location.is_active
                                        ? 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300'
                                        : 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-300'
                                ]"
                            >
                                <Power :size="18" />
                                <span>{{ isToggling ? 'Procesando...' : (selectedProduct.location.is_active ? 'Desactivar Ubicación' : 'Activar Ubicación') }}</span>
                            </button>
                        </div>

<!-- Coordenadas -->
<div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
    <h4 class="text-sm font-semibold text-blue-800 mb-3 flex items-center gap-2">
        <MapPin :size="16" />
        Coordenadas Actuales
    </h4>
    <div class="space-y-1">
        <p class="text-sm text-gray-700">
            <span class="font-medium">Latitud:</span> {{ formattedCoordinates.lat }}
        </p>
        <p class="text-sm text-gray-700">
            <span class="font-medium">Longitud:</span> {{ formattedCoordinates.lng }}
        </p>
    </div>
</div>

                        <!-- Dirección -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Dirección {{ editMode ? '(Editable)' : '' }}
                            </label>
                            <textarea
                                v-model="address"
                                :disabled="!editMode"
                                placeholder="Ingresa la dirección completa..."
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none disabled:bg-gray-100 disabled:text-gray-600"
                            ></textarea>
                            <p v-if="!editMode" class="text-xs text-gray-500 mt-1">
                                Activa el modo edición para modificar la dirección
                            </p>
                        </div>

                        <!-- Eliminar ubicación -->
                        <div class="pt-4 border-t border-gray-200">
                            <div v-if="!showDeleteConfirm">
                                <button
                                    @click="showDeleteConfirm = true"
                                    :disabled="editMode"
                                    class="w-full py-3 rounded-lg font-semibold bg-red-100 hover:bg-red-200 text-red-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed border border-red-300"
                                >
                                    <Trash2 :size="18" />
                                    <span>Eliminar Ubicación</span>
                                </button>
                            </div>

                            <div v-else class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
                                <p class="text-sm font-semibold text-red-800 mb-2">
                                    ⚠️ ¿Estás seguro de eliminar esta ubicación?
                                </p>
                                <p class="text-xs text-red-600 mb-3">
                                    Esta acción no se puede deshacer y eliminará la ubicación del producto.
                                </p>
                                <div class="space-y-2">
                                    <button
                                        @click="deleteLocation"
                                        :disabled="isDeleting"
                                        class="w-full py-2 rounded-lg font-semibold bg-red-600 hover:bg-red-700 text-white transition-all disabled:bg-gray-300 disabled:cursor-not-allowed"
                                    >
                                        {{ isDeleting ? 'Eliminando...' : 'Sí, eliminar ubicación' }}
                                    </button>
                                    <button
                                        @click="showDeleteConfirm = false"
                                        class="w-full py-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-all border border-gray-300"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Scrollbar personalizado */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c4b5fd;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a78bfa;
}
</style>