<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import { ArrowLeft, Navigation, X, Search, Radar, MapPin } from 'lucide-vue-next';

/* ================= PROPS ================= */
interface Product {
  id: number;
  name: string;
  codigo_inmueble: string;
  price: number;
  category?: string;
  location: {
    latitude: number;
    longitude: number;
    is_active: boolean;
  };
}

const props = defineProps<{
  productsConUbicacion: Product[];
}>();

/* ================= MAP ================= */
const mapRef = ref<HTMLElement | null>(null);
let map: L.Map;

/* ================= CAPAS ================= */
let baseLayer = L.layerGroup();
let radarCluster = L.markerClusterGroup();

/* ================= USER LOCATION ================= */
let userMarker: L.CircleMarker | null = null;
let userAccuracy: L.Circle | null = null;
const isLocatingUser = ref(false);

/* ================= RADAR ================= */
let radarMarker: L.Marker | null = null;
let radarCircle: L.Circle | null = null;
let radarPulse: L.Circle | null = null;
let pulseInterval: number | null = null;

/* ================= STATE ================= */
const radarRadius = ref(800);
const radarCenter = ref<L.LatLng | null>(null);
const showPanel = ref(false);
const results = ref<Product[]>([]);
const searchQuery = ref('');
const radarMode = ref(false); // Modo de colocación de radar
const radarPlaced = ref(false); // Si el radar ya está colocado

/* ================= COMPUTED ================= */
const filteredResults = computed(() => {
  if (!searchQuery.value) return results.value;
  
  const query = searchQuery.value.toLowerCase();
  return results.value.filter(p =>
    p.name.toLowerCase().includes(query) ||
    p.codigo_inmueble.toLowerCase().includes(query) ||
    (p.category && p.category.toLowerCase().includes(query))
  );
});

/* ================= INIT ================= */
onMounted(() => {
  map = L.map(mapRef.value!).setView([-17.38, -66.16], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  baseLayer.addTo(map);
  radarCluster.addTo(map);

  drawAllProperties();
  
  // Solo escuchar clicks cuando el modo radar está activo
  map.on('click', (e) => {
    if (radarMode.value && !radarPlaced.value) {
      placeRadar(e);
    }
  });
});

onUnmounted(() => {
  if (pulseInterval) clearInterval(pulseInterval);
  map.remove();
});

/* ================= BASE PROPERTIES ================= */
const drawAllProperties = () => {
  baseLayer.clearLayers();

  props.productsConUbicacion.forEach(p => {
    if (!p.location.is_active) return;

    const marker = L.marker([p.location.latitude, p.location.longitude]);
    
    marker.bindPopup(`
      <div class="text-sm">
        <b>${p.name}</b><br>
        <span class="text-xs text-gray-600">${p.codigo_inmueble}</span>
      </div>
    `);
    
    marker.addTo(baseLayer);
  });
};

/* ================= USER LOCATION ================= */
const locateMe = () => {
  if (!('geolocation' in navigator)) {
    alert('Tu navegador no soporta geolocalización');
    return;
  }

  isLocatingUser.value = true;

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      const { latitude, longitude, accuracy } = pos.coords;

      if (userMarker) map.removeLayer(userMarker);
      if (userAccuracy) map.removeLayer(userAccuracy);

      userAccuracy = L.circle([latitude, longitude], {
        radius: accuracy,
        color: '#3B82F6',
        fillColor: '#3B82F6',
        fillOpacity: 0.1,
        weight: 2
      }).addTo(map);

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
                0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
                70% { box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); }
                100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
              }
            </style>
          </div>
        `,
        iconSize: [10, 10],
        iconAnchor: [10, 10]
      });

      userMarker = L.circleMarker([latitude, longitude], {
        icon: pulsingIcon
      } as any).addTo(map);

      map.setView([latitude, longitude], 16, {
        animate: true,
        duration: 1.5
      });

      isLocatingUser.value = false;
    },
    (error) => {
      isLocatingUser.value = false;
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
};

/* ================= ACTIVAR MODO RADAR ================= */
const activateRadarMode = () => {
  radarMode.value = true;
  map.getContainer().style.cursor = 'crosshair';
};

/* ================= RADAR ================= */
const placeRadar = (e: L.LeafletMouseEvent) => {
  radarCenter.value = e.latlng;
  radarPlaced.value = true;
  map.getContainer().style.cursor = '';

  // Círculo azul semi-transparente
  radarCircle = L.circle(e.latlng, {
    radius: radarRadius.value,
    color: '#3B82F6',
    fillColor: '#3B82F6',
    fillOpacity: 0.15,
    weight: 2
  }).addTo(map);

  // Marcador arrastrable
  radarMarker = L.marker(e.latlng, { 
    draggable: true,
    icon: L.divIcon({
      className: 'radar-marker',
      html: `
        <div style="
          width: 30px;
          height: 30px;
          background: #3B82F6;
          border: 3px solid white;
          border-radius: 50%;
          box-shadow: 0 2px 8px rgba(0,0,0,0.3);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: bold;
          font-size: 16px;
        ">📍</div>
      `,
      iconSize: [30, 30],
      iconAnchor: [15, 15]
    })
  }).addTo(map);

  radarMarker.bindPopup('🎯 Arrastra para ajustar posición').openPopup();

  radarMarker.on('drag', (ev: any) => {
    const pos = ev.target.getLatLng();
    radarCircle!.setLatLng(pos);
    if (radarPulse) radarPulse.setLatLng(pos);
    radarCenter.value = pos;
  });

  startRadarPulse(e.latlng);
};

/* ================= RADAR ANIMATION ================= */
const startRadarPulse = (center: L.LatLng) => {
  if (radarPulse) map.removeLayer(radarPulse);

  radarPulse = L.circle(center, {
    radius: 0,
    color: '#3B82F6',
    opacity: 0.6,
    fillOpacity: 0,
    weight: 3
  }).addTo(map);

  if (pulseInterval) clearInterval(pulseInterval);

  pulseInterval = window.setInterval(() => {
    if (!radarPulse || !radarCenter.value) return;
    
    let r = 0;
    const growInterval = setInterval(() => {
      r += 50;
      if (radarPulse) {
        radarPulse.setRadius(r);
        radarPulse.setStyle({ opacity: Math.max(0, 1 - r / radarRadius.value) });
      }
      if (r >= radarRadius.value) clearInterval(growInterval);
    }, 30);
  }, 2000);
};

/* ================= QUITAR PUNTO RADAR ================= */
const removeRadarPoint = () => {
  if (radarMarker) map.removeLayer(radarMarker);
  if (radarCircle) map.removeLayer(radarCircle);
  if (radarPulse) map.removeLayer(radarPulse);
  if (pulseInterval) clearInterval(pulseInterval);

  radarMarker = radarCircle = radarPulse = null;
  pulseInterval = null;
  radarCenter.value = null;
  radarPlaced.value = false;
  
  // Volver a activar el cursor de selección
  map.getContainer().style.cursor = 'crosshair';
};

/* ================= SLIDER ================= */
const updateRadius = () => {
  if (radarCircle) {
    radarCircle.setRadius(radarRadius.value);
  }
};

/* ================= SEARCH ================= */
const search = () => {
  if (!radarCenter.value) return;

  radarCluster.clearLayers();
  results.value = [];

  props.productsConUbicacion.forEach(p => {
    if (!p.location.is_active) return;

    const pos = L.latLng(p.location.latitude, p.location.longitude);
    const distance = radarCenter.value!.distanceTo(pos);
    
    if (distance <= radarRadius.value) {
      const marker = L.marker(pos, {
        icon: L.divIcon({
          className: 'result-marker',
          html: `
            <div style="
              width: 25px;
              height: 25px;
              background: #10B981;
              border: 3px solid white;
              border-radius: 50%;
              box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            "></div>
          `,
          iconSize: [25, 25],
          iconAnchor: [12, 12]
        })
      });

      marker.bindPopup(`
        <div class="text-sm">
          <b>${p.name}</b><br>
          <span class="text-xs text-gray-600">${p.codigo_inmueble}</span><br>
          <span class="text-green-600 font-bold">Bs. ${p.price.toLocaleString()}</span><br>
          <span class="text-xs text-gray-500">${distance.toFixed(0)}m del centro</span>
        </div>
      `);

      marker.addTo(radarCluster);
      results.value.push(p);
    }
  });

  showPanel.value = true;
  searchQuery.value = '';
};

/* ================= PANEL CLICK ================= */
const focusProperty = (p: Product) => {
  const pos = L.latLng(p.location.latitude, p.location.longitude);
  map.setView(pos, 18, { animate: true });
  
  radarCluster.eachLayer((layer: any) => {
    if (layer.getLatLng().equals(pos)) {
      layer.openPopup();
    }
  });
};

/* ================= RESET RADAR COMPLETO ================= */
const resetRadar = () => {
  if (radarMarker) map.removeLayer(radarMarker);
  if (radarCircle) map.removeLayer(radarCircle);
  if (radarPulse) map.removeLayer(radarPulse);
  if (pulseInterval) clearInterval(pulseInterval);

  radarMarker = radarCircle = radarPulse = null;
  pulseInterval = null;
  radarCenter.value = null;
  radarMode.value = false;
  radarPlaced.value = false;

  radarCluster.clearLayers();
  showPanel.value = false;
  results.value = [];
  searchQuery.value = '';
  
  map.getContainer().style.cursor = '';
};
</script>

<template>
  <Head title="Radar de Propiedades" />

  <AppLayout>
    <div class="relative h-[calc(100vh-140px)]">
      <div ref="mapRef" class="w-full h-full"></div>

      <!-- CONTROLES SUPERIORES -->
      <div class="absolute top-4 left-4 z-[1000] flex gap-2">
        <!-- Volver -->
        <button 
          @click="router.visit('/ubicaciones')" 
          class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 font-medium transition-all"
        >
          <ArrowLeft :size="18" />
          Volver
        </button>
        
        <!-- Mi Ubicación -->
        <button 
          @click="locateMe"
          :disabled="isLocatingUser"
          :class="[
            'px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 font-medium transition-all',
            isLocatingUser 
              ? 'bg-blue-400 cursor-not-allowed' 
              : 'bg-blue-600 hover:bg-blue-700 text-white'
          ]"
        >
          <Navigation :size="18" :class="isLocatingUser ? 'animate-pulse' : ''" />
          Mi Ubicación
        </button>
        
        <!-- Activar Radar (solo si no está en modo radar) -->
        <button 
          v-if="!radarMode"
          @click="activateRadarMode" 
          class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 font-medium transition-all"
        >
          <Radar :size="18" />
          Activar Radar
        </button>

        <!-- Quitar Punto (solo si el radar está colocado pero no hay resultados) -->
        <button 
          v-if="radarPlaced && !showPanel"
          @click="removeRadarPoint" 
          class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 font-medium transition-all"
        >
          <MapPin :size="18" />
          Quitar Punto
        </button>
        
        <!-- Quitar Radar Completo (cuando está en modo radar o hay resultados) -->
        <button 
          v-if="radarMode"
          @click="resetRadar" 
          class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 font-medium transition-all"
        >
          <X :size="18" />
          Quitar Radar
        </button>
      </div>

      <!-- MENSAJE DE INSTRUCCIÓN -->
      <div 
        v-if="radarMode && !radarPlaced"
        class="absolute top-20 left-1/2 -translate-x-1/2 z-[1000] bg-purple-600 text-white px-6 py-3 rounded-lg shadow-xl"
      >
        <p class="text-sm font-medium flex items-center gap-2">
          <MapPin :size="18" />
          Selecciona en el mapa donde quieres fijar el punto del radar
        </p>
      </div>

      <!-- CONTROL DE RADIO (BARRA INFERIOR) -->
      <div
        v-if="radarPlaced && !showPanel"
        class="absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000] bg-white p-5 rounded-xl shadow-2xl w-[500px]"
      >
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="font-bold text-gray-800 text-lg">Radio de búsqueda</label>
            <span class="bg-blue-600 text-white px-4 py-1.5 rounded-full font-bold text-lg">
              {{ radarRadius }}m
            </span>
          </div>
          
          <input
            type="range"
            min="100"
            max="5000"
            step="50"
            v-model.number="radarRadius"
            @input="updateRadius"
            class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
            style="
              background: linear-gradient(to right, #3B82F6 0%, #3B82F6 var(--value), #E5E7EB var(--value), #E5E7EB 100%);
            "
            :style="{
              '--value': ((radarRadius - 100) / (5000 - 100) * 100) + '%'
            }"
          />
          
          <div class="flex justify-between text-xs text-gray-500 font-medium">
            <span>100m</span>
            <span>2.5km</span>
            <span>5km</span>
          </div>

          <button 
            @click="search" 
            class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-3.5 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02]"
          >
            ✓ LISTO - BUSCAR PROPIEDADES
          </button>
        </div>
      </div>

      <!-- PANEL LATERAL DE RESULTADOS -->
      <transition name="slide">
        <div
          v-if="showPanel"
          class="absolute right-0 top-0 h-full w-[400px] bg-white shadow-2xl z-[1000] flex flex-col"
        >
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex justify-between items-center flex-shrink-0">
            <div>
              <h3 class="font-bold text-lg">📍 Propiedades encontradas</h3>
              <p class="text-xs text-blue-100">{{ filteredResults.length }} resultados</p>
            </div>
            <button 
              @click="showPanel = false"
              class="hover:bg-white/20 p-1.5 rounded-lg transition-colors"
            >
              <X :size="24" />
            </button>
          </div>

          <!-- Búsqueda -->
          <div class="p-3 border-b bg-gray-50 flex-shrink-0">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="16" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar en resultados..."
                class="w-full pl-10 pr-10 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <X :size="16" />
              </button>
            </div>
          </div>

          <!-- Lista de resultados -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <div
              v-for="p in filteredResults"
              :key="p.id"
              @click="focusProperty(p)"
              class="cursor-pointer border-2 border-gray-200 hover:border-blue-500 p-4 rounded-lg hover:bg-blue-50 transition-all transform hover:scale-[1.02] hover:shadow-md"
            >
              <div class="flex justify-between items-start mb-2">
                <h4 class="font-bold text-gray-800 text-sm line-clamp-2 flex-1">{{ p.name }}</h4>
                <span class="text-blue-600 font-bold text-lg ml-2 flex-shrink-0">
                  {{ (p.price / 1000).toFixed(0) }}k
                </span>
              </div>
              
              <p class="text-xs text-gray-600 mb-1">{{ p.codigo_inmueble }}</p>
              
              <div class="flex items-center justify-between text-xs">
                <span v-if="p.category" class="bg-gray-100 px-2 py-1 rounded text-gray-700">
                  {{ p.category }}
                </span>
                <span class="text-gray-500">
                  📏 {{ radarCenter ? radarCenter.distanceTo(L.latLng(p.location.latitude, p.location.longitude)).toFixed(0) : '0' }}m
                </span>
              </div>
            </div>

            <!-- Sin resultados -->
            <div 
              v-if="results.length === 0" 
              class="text-center text-gray-500 py-12"
            >
              <div class="text-6xl mb-3">🔍</div>
              <p class="font-medium">No se encontraron propiedades</p>
              <p class="text-sm mt-1">Intenta aumentar el radio de búsqueda</p>
            </div>

            <!-- Sin resultados filtrados -->
            <div 
              v-else-if="filteredResults.length === 0" 
              class="text-center text-gray-500 py-12"
            >
              <div class="text-6xl mb-3">🔍</div>
              <p class="font-medium">No hay coincidencias</p>
              <p class="text-sm mt-1">Intenta con otros términos de búsqueda</p>
            </div>
          </div>
        </div>
      </transition>
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

/* Scroll personalizado */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Animación del panel */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from {
  transform: translateX(100%);
}

.slide-leave-to {
  transform: translateX(100%);
}

/* Estilo del slider */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  border: 3px solid white;
}

input[type="range"]::-moz-range-thumb {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  border: 3px solid white;
}
</style>