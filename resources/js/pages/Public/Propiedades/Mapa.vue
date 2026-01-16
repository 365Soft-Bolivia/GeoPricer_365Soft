<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import { ArrowLeft, Navigation, X, Search, Radar, MapPin, Home, Filter, ChevronDown, DollarSign, Building, Key, FileText } from 'lucide-vue-next';

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

/* ================= PROPS ================= */
interface Product {
  id: number;
  name: string;
  codigo_inmueble: string;
  price: number;
  operacion: string;
  category?: string;
  category_id?: number | null;
  superficie_util?: number; // metros cuadrados de superficie útil
  superficie_construida?: number; // metros cuadrados construidos
  location: {
    latitude: number;
    longitude: number;
    is_active: boolean;
  };
}

const props = defineProps<{
  productsConUbicacion: Product[];
  categoriasDisponibles: Record<number, string>;
  totalPropiedades: number;
  defaultCenter?: { lat: number; lng: number };
}>();

const defaultCenter = props.defaultCenter || { lat: -17.38, lng: -66.16 };

/* ================= MAP ================= */
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map;
const markers: L.Marker[] = [];
let markerClusterGroup: L.MarkerClusterGroup | null = null;

/* ================= USER LOCATION ================= */
let userLocationMarker: L.Marker | null = null;
let userLocationCircle: L.Circle | null = null;
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

/* ================= FILTROS ================= */
const categoriaSeleccionada = ref<number | null>(null);
const operacionSeleccionada = ref<string | null>(null);
const showCategoryDropdown = ref(false);
const showOperationDropdown = ref(false);

const operacionesDisponibles = [
  { value: 'venta', label: 'Venta', icon: DollarSign },
  { value: 'alquiler', label: 'Alquiler', icon: Key },
  { value: 'anticretico', label: 'Anticrético', icon: FileText },
];

const nombreCategoriaSeleccionada = computed(() => {
  if (!categoriaSeleccionada.value) return 'Todas';
  return props.categoriasDisponibles[categoriaSeleccionada.value] || 'Todas';
});

const nombreOperacionSeleccionada = computed(() => {
  if (!operacionSeleccionada.value) return 'Todas';
  return operacionesDisponibles.find(op => op.value === operacionSeleccionada.value)?.label || 'Todas';
});

const productosFiltrados = computed(() => {
  return props.productsConUbicacion.filter(product => {
    if (!product.location.is_active) return false;
    if (categoriaSeleccionada.value && product.category_id !== categoriaSeleccionada.value) return false;
    if (operacionSeleccionada.value && product.operacion !== operacionSeleccionada.value) return false;
    return true;
  });
});

const totalPropiedadesFiltradas = computed(() => {
  return productosFiltrados.value.length;
});

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

// Calcular precio por metro cuadrado de superficie útil
const getPricePerSqmUtil = (product: Product) => {
  if (!product.superficie_util || product.superficie_util <= 0) return null;
  return product.price / product.superficie_util;
};

// Calcular precio por metro cuadrado de superficie construida
const getPricePerSqmConstruida = (product: Product) => {
  if (!product.superficie_construida || product.superficie_construida <= 0) return null;
  return product.price / product.superficie_construida;
};

// Calcular promedio de precio por m² útil de la zona
const averagePricePerSqmUtil = computed(() => {
  const validProducts = filteredResults.value.filter(p => p.superficie_util && p.superficie_util > 0);

  if (validProducts.length === 0) return null;

  const sum = validProducts.reduce((acc, p) => {
    return acc + (p.price / p.superficie_util!);
  }, 0);

  return sum / validProducts.length;
});

// Calcular promedio de precio por m² construido de la zona
const averagePricePerSqmConstruida = computed(() => {
  const validProducts = filteredResults.value.filter(p => p.superficie_construida && p.superficie_construida > 0);

  if (validProducts.length === 0) return null;

  const sum = validProducts.reduce((acc, p) => {
    return acc + (p.price / p.superficie_construida!);
  }, 0);

  return sum / validProducts.length;
});

// Funciones de navegación
const goBack = () => {
  router.visit('/propiedades');
};

const goToHome = () => {
  router.visit('/');
};

const goToProperties = () => {
  router.visit('/propiedades');
};

const selectCategoria = (categoryId: number | null) => {
  categoriaSeleccionada.value = categoryId;
  showCategoryDropdown.value = false;
  updateMarkers();
};

const selectOperacion = (operacion: string | null) => {
  operacionSeleccionada.value = operacion;
  showOperationDropdown.value = false;
  updateMarkers();
};

const resetFilters = () => {
  categoriaSeleccionada.value = null;
  operacionSeleccionada.value = null;
  updateMarkers();
};

const resetView = () => {
  if (!map || markers.length === 0) return;

  if (markerClusterGroup) {
    map.fitBounds(markerClusterGroup.getBounds().pad(0.1), {
      animate: true,
      duration: 1
    });
  }
};

// Función para cerrar dropdowns
const closeDropdowns = () => {
  showCategoryDropdown.value = false;
  showOperationDropdown.value = false;
};

const initMap = () => {
  if (!mapContainer.value) return;

  map = L.map(mapContainer.value).setView([defaultCenter.lat, defaultCenter.lng], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map);

  // Inicializar el grupo de clustering con configuración optimizada
  markerClusterGroup = L.markerClusterGroup({
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    removeOutsideVisibleBounds: true,
    iconCreateFunction: function(cluster) {
      const count = cluster.getChildCount();
      let color = '#10B981';
      let size = 40;

      if (count >= 50) {
        color = '#DC2626';
        size = 60;
      } else if (count >= 20) {
        color = '#F59E0B';
        size = 50;
      } else if (count >= 10) {
        color = '#3B82F6';
        size = 45;
      }

      return L.divIcon({
        className: 'custom-cluster-icon',
        html: `
          <div style="
            background: ${color};
            border: 3px solid white;
            border-radius: 50%;
            width: ${size}px;
            height: ${size}px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: ${size >= 50 ? '16px' : '14px'};
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
          ">${count}</div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2]
      });
    },
    maxClusterRadius: function (zoom) {
      if (zoom <= 10) return 80;
      if (zoom <= 12) return 60;
      if (zoom <= 14) return 40;
      return 20;
    }
  });

  map.addLayer(markerClusterGroup);
  updateMarkers();

  // Solo escuchar clicks cuando el modo radar está activo
  map.on('click', (e) => {
    if (radarMode.value && !radarPlaced.value) {
      placeRadar(e);
    }
  });
};

const updateMarkers = () => {
  if (!map || !markerClusterGroup) return;

  markerClusterGroup.clearLayers();
  markers.length = 0;

  productosFiltrados.value.forEach((product) => {
    const operacionIcon = getOperacionIcon(product.operacion);
    const customIcon = L.divIcon({
      className: 'custom-div-icon',
      html: `
        <div style="
          background: ${getOperacionColor(product.operacion)};
          border: 3px solid white;
          border-radius: 50%;
          width: 36px;
          height: 36px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 16px;
          box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        ">${operacionIcon}</div>
      `,
      iconSize: [36, 36],
      iconAnchor: [18, 36],
      popupAnchor: [0, -36]
    });

    const marker = L.marker([product.location.latitude, product.location.longitude], {
      icon: customIcon
    });

    marker.bindPopup(`
      <div class="text-sm">
        <b>${product.name}</b><br>
        <span class="text-xs text-gray-600">${product.codigo_inmueble}</span><br>
        <span class="text-green-600 font-bold">$US ${product.price.toLocaleString()}</span><br>
        <span class="text-xs text-gray-500">${product.category || 'N/A'}</span>
      </div>
    `);

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });
};

const getOperacionIcon = (operacion: string) => {
  switch (operacion) {
    case 'venta': return '💰';
    case 'alquiler': return '🔑';
    case 'anticretico': return '📄';
    default: return '🏠';
  }
};

const getOperacionColor = (operacion: string) => {
  switch (operacion) {
    case 'venta': return '#10B981';
    case 'alquiler': return '#3B82F6';
    case 'anticretico': return '#8B5CF6';
    default: return '#6B7280';
  }
};

onMounted(() => {
  addFullScreenStyles();
  initMap();
});

onUnmounted(() => {
  removeFullScreenStyles();
  if (pulseInterval) clearInterval(pulseInterval);
  if (map) {
    if (markerClusterGroup) {
      map.removeLayer(markerClusterGroup);
    }
    map.remove();
    map = null;
  }
});

// Estilos para pantalla completa
const addFullScreenStyles = () => {
  const style = document.createElement('style');
  style.setAttribute('data-fullscreen-map', 'true');
  style.textContent = `
    body, html {
      margin: 0;
      padding: 0;
      overflow: hidden;
      height: 100%;
      width: 100%;
    }
    #app {
      height: 100vh !important;
      width: 100vw !important;
      overflow: hidden !important;
    }
  `;
  document.head.appendChild(style);
};

// Remover estilos al desmontar
const removeFullScreenStyles = () => {
  const styles = document.querySelectorAll('style[data-fullscreen-map]');
  styles.forEach(style => style.remove());
};


/* ================= USER LOCATION ================= */
const locateMe = () => {
  if (!map) return;

  if (!('geolocation' in navigator)) {
    alert('Tu navegador no soporta geolocalización');
    return;
  }

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
              border: 4px solid white;
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
        iconSize: [12, 12],
        iconAnchor: [12, 12]
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
      }, 2000);

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
    if (radarPulse) {
      radarPulse.setLatLng(pos);
      // Reiniciar la animación del pulso cuando se arrastra
      startRadarPulse(pos);
    }
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

  // Animación lenta cada 3 segundos (manteniendo estilo original)
  pulseInterval = window.setInterval(() => {
    if (!radarPulse || !radarCenter.value) return;

    // Reiniciar desde el centro
    radarPulse.setRadius(0);
    radarPulse.setStyle({
      opacity: 0.6,
      fillOpacity: 0
    });

    // Animación lenta y suave del radio
    let currentRadius = 0;
    const startTime = Date.now();
    const animationDuration = 2500; // 2.5 segundos para animación completa (más lento)
    const targetRadius = radarRadius.value;

    const animatePulse = () => {
      const elapsed = Date.now() - startTime;
      const progress = Math.min(elapsed / animationDuration, 1);

      // Easing suave para expansión natural
      const easeOutProgress = 1 - Math.pow(1 - progress, 2);
      currentRadius = targetRadius * easeOutProgress;

      // Desvanecer opacidad gradualmente
      const opacity = Math.max(0.1, 0.6 * (1 - progress * 0.8));

      if (radarPulse) {
        radarPulse.setRadius(currentRadius);
        radarPulse.setStyle({
          opacity: opacity,
          fillOpacity: 0.15 * (1 - progress) // Relleno ligero que aparece
        });
      }

      // Continuar animación si no ha terminado
      if (progress < 1) {
        requestAnimationFrame(animatePulse);
      }
    };

    requestAnimationFrame(animatePulse);
  }, 3000); // Pulso cada 3 segundos como el punto de ubicación
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

  // Actualizar el radio del pulso si está activo
  if (radarPulse) {
    radarPulse.setRadius(radarRadius.value);
  }
};

/* ================= SEARCH ================= */
const search = () => {
  if (!radarCenter.value) return;

  // Limpiar resultados anteriores
  results.value = [];

  // Obtener los productos ya filtrados por categoría y operación
  const productosFiltradosParaRadar = productosFiltrados.value;

  // Buscar propiedades dentro del radio del radar (solo de los productos filtrados)
  productosFiltradosParaRadar.forEach(p => {
    const pos = L.latLng(p.location.latitude, p.location.longitude);
    const distance = radarCenter.value!.distanceTo(pos);

    if (distance <= radarRadius.value) {
      results.value.push(p);
    }
  });

  // Mostrar el panel lateral con resultados
  showPanel.value = true;
  searchQuery.value = '';

  // Actualizar los marcadores para mostrar solo los resultados del radar
  updateRadarMarkers();
};

// Función para actualizar marcadores del radar
const updateRadarMarkers = () => {
  if (!map || !markerClusterGroup) return;

  // Limpiar marcadores existentes
  markerClusterGroup.clearLayers();
  markers.length = 0;

  // Obtener productos ya filtrados por categoría y operación
  const productosFiltradosParaMostrar = productosFiltrados.value;

  // Agregar marcadores de productos filtrados normales (fuera del radar)
  productosFiltradosParaMostrar.forEach((product) => {
    if (results.value.some(r => r.id === product.id)) return; // Omitir los encontrados por el radar

    const operacionIcon = getOperacionIcon(product.operacion);
    const customIcon = L.divIcon({
      className: 'custom-div-icon',
      html: `
        <div style="
          background: ${getOperacionColor(product.operacion)};
          border: 3px solid white;
          border-radius: 50%;
          width: 30px;
          height: 30px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 14px;
          box-shadow: 0 2px 6px rgba(0,0,0,0.3);
          opacity: 0.6;
        ">${operacionIcon}</div>
      `,
      iconSize: [30, 30],
      iconAnchor: [15, 30],
      popupAnchor: [0, -30]
    });

    const marker = L.marker([product.location.latitude, product.location.longitude], {
      icon: customIcon
    });

    marker.bindPopup(`
      <div class="text-sm">
        <b>${product.name}</b><br>
        <span class="text-xs text-gray-600">${product.codigo_inmueble}</span><br>
        <span class="text-green-600 font-bold">$US ${product.price.toLocaleString()}</span><br>
        <span class="text-xs text-gray-500">${product.category || 'N/A'}</span>
      </div>
    `);

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });

  // Agregar marcadores de resultados del radar (más prominentes)
  results.value.forEach((product) => {
    const customIcon = L.divIcon({
      className: 'radar-result-marker',
      html: `
        <div style="
          background: #10B981;
          border: 3px solid white;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
          animation: pulse-radar 2s infinite;
          z-index: 1000;
        ">🎯</div>
        <style>
          @keyframes pulse-radar {
            0% { transform: scale(1); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
            50% { transform: scale(1.1); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6); }
            100% { transform: scale(1); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
          }
        </style>
      `,
      iconSize: [40, 40],
      iconAnchor: [20, 40],
      popupAnchor: [0, -40]
    });

    const marker = L.marker([product.location.latitude, product.location.longitude], {
      icon: customIcon
    });

    const pos = L.latLng(product.location.latitude, product.location.longitude);
    const distance = radarCenter.value!.distanceTo(pos);

    marker.bindPopup(`
      <div class="text-sm">
        <b>${product.name}</b><br>
        <span class="text-xs text-gray-600">${product.codigo_inmueble}</span><br>
        <span class="text-green-600 font-bold">$US ${product.price.toLocaleString()}</span><br>
        <span class="text-xs text-blue-600">📏 ${distance.toFixed(0)}m del centro</span><br>
        <span class="text-xs text-gray-500">${product.category || 'N/A'}</span>
        ${product.operacion ? `<br><span class="text-xs text-purple-600">${getOperacionIcon(product.operacion)} ${product.operacion}</span>` : ''}
      </div>
    `);

    marker.on('click', () => {
      focusProperty(product);
    });

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });
};

/* ================= PANEL CLICK ================= */
const focusProperty = (p: Product) => {
  const pos = L.latLng(p.location.latitude, p.location.longitude);
  map.setView(pos, 18, { animate: true });

  // Buscar y abrir el popup del marcador correspondiente
  markers.forEach((marker: any) => {
    const markerPos = marker.getLatLng();
    if (markerPos.lat === pos.lat && markerPos.lng === pos.lng) {
      marker.openPopup();
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

  // Limpiar resultados y panel
  showPanel.value = false;
  results.value = [];
  searchQuery.value = '';

  // Restaurar marcadores normales
  updateMarkers();

  map.getContainer().style.cursor = '';
};
</script>

<template>
  <Head title="Mapa Interactivo de Propiedades" />

  <div class="relative w-screen h-screen overflow-hidden" @click="showCategoryDropdown = false; showOperationDropdown = false;">
    <!-- Mapa -->
    <div ref="mapContainer" class="absolute inset-0 w-full h-full"></div>

    <!-- CONTROLES DESKTOP -->
    <div class="absolute top-4 left-4 right-4 z-[1000] hidden lg:flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3" @click.stop>
      <!-- Controles de navegación y filtros (desktop) -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 flex-wrap">
        <!-- Inicio -->
        <button
          @click="goToHome"
          class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Inicio"
        >
          <Home :size="18" />
        </button>

        <!-- Volver -->
        <button
          @click="goBack"
          class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Volver"
        >
          <ArrowLeft :size="18" />
        </button>

        <!-- Filtros -->
        <div class="flex items-center gap-2">
          <!-- Dropdown de categorías -->
          <div class="relative">
            <button
              @click.stop="showCategoryDropdown = !showCategoryDropdown"
              class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm min-w-[140px]"
              title="Filtrar por categoría"
            >
              <Filter :size="16" />
              <span class="font-medium text-sm truncate">{{ nombreCategoriaSeleccionada }}</span>
              <ChevronDown :size="14" :class="{ 'rotate-180': showCategoryDropdown }" class="transition-transform flex-shrink-0" />
            </button>

            <div
              v-if="showCategoryDropdown"
              class="absolute top-full left-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-80 overflow-y-auto"
              @click.stop
            >
              <div class="p-2">
                <button
                  @click="selectCategoria(null)"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                    !categoriaSeleccionada ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-gray-50 text-gray-700'
                  ]"
                >
                  <span>Todas las categorías</span>
                  <span v-if="!categoriaSeleccionada" class="text-blue-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </button>

                <div class="border-t border-gray-100 my-1"></div>

                <button
                  v-for="(nombre, id) in categoriasDisponibles"
                  :key="id"
                  @click="selectCategoria(parseInt(id as string))"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                    categoriaSeleccionada === parseInt(id as string) ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-gray-50 text-gray-700'
                  ]"
                >
                  <span class="truncate">{{ nombre }}</span>
                  <span v-if="categoriaSeleccionada === parseInt(id as string)" class="text-blue-600 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </button>
              </div>
            </div>
          </div>

          <!-- Dropdown de operaciones -->
          <div class="relative">
            <button
              @click.stop="showOperationDropdown = !showOperationDropdown"
              class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm min-w-[120px]"
              title="Filtrar por operación"
            >
              <DollarSign :size="16" />
              <span class="font-medium text-sm truncate">{{ nombreOperacionSeleccionada }}</span>
              <ChevronDown :size="14" :class="{ 'rotate-180': showOperationDropdown }" class="transition-transform flex-shrink-0" />
            </button>

            <div
              v-if="showOperationDropdown"
              class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
              @click.stop
            >
              <div class="p-2">
                <button
                  @click="selectOperacion(null)"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                    !operacionSeleccionada ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-gray-50 text-gray-700'
                  ]"
                >
                  <span>Todas las operaciones</span>
                  <span v-if="!operacionSeleccionada" class="text-blue-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </button>

                <div class="border-t border-gray-100 my-1"></div>

                <button
                  v-for="op in operacionesDisponibles"
                  :key="op.value"
                  @click="selectOperacion(op.value)"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                    operacionSeleccionada === op.value ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-gray-50 text-gray-700'
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <component :is="op.icon" :size="16" />
                    <span>{{ op.label }}</span>
                  </div>
                  <span v-if="operacionSeleccionada === op.value" class="text-blue-600 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </button>
              </div>
            </div>
          </div>

          <!-- Limpiar filtros -->
          <button
            v-if="categoriaSeleccionada || operacionSeleccionada"
            @click="resetFilters"
            class="bg-red-500/90 hover:bg-red-600/90 text-white px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
            title="Limpiar filtros"
          >
            <X :size="16" />
          </button>
        </div>
      </div>

      <!-- Controles del mapa (desktop) -->
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Contador -->
        <div class="bg-white/95 px-3 py-2 rounded-lg shadow-lg backdrop-blur-sm">
          <p class="text-sm font-semibold text-gray-700">
            {{ totalPropiedadesFiltradas }} de {{ totalPropiedades }}
          </p>
        </div>

        <!-- Mi Ubicación -->
        <button
          @click="locateMe"
          :disabled="isLocatingUser"
          :class="[
            'p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm',
            isLocatingUser ? 'bg-blue-400/90 cursor-not-allowed' : 'bg-blue-600/90 hover:bg-blue-700/90 text-white'
          ]"
          class="text-white"
          title="Mi ubicación"
        >
          <Navigation :size="18" :class="isLocatingUser ? 'animate-pulse' : ''" />
        </button>

        <!-- Radar / Quitar Radar -->
        <button
          v-if="!radarMode"
          @click="activateRadarMode"
          class="bg-purple-600/90 hover:bg-purple-700/90 text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Activar radar"
        >
          <Radar :size="18" />
        </button>

        <button
          v-if="radarMode"
          @click="resetRadar"
          class="bg-red-600/90 hover:bg-red-700/90 text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm animate-pulse"
          title="Quitar radar"
        >
          <X :size="18" />
        </button>

        <!-- Ver todo -->
        <button
          @click="resetView"
          class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Ver todo"
        >
          <MapPin :size="18" />
        </button>
      </div>
    </div>

    <!-- CONTROLES MÓVIL SIMPLE -->
    <!-- Botón volver -->
    <div class="fixed top-2 left-2 z-[1200] lg:hidden">
      <button
        @click="goBack"
        class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Volver a la página anterior"
      >
        <ArrowLeft :size="18" />
      </button>
    </div>

    <!-- Dropdown filtros - ULTRA SIMPLE -->
    <div class="fixed top-2 right-2 z-[1200] lg:hidden flex gap-2" @click.stop>
      <!-- Dropdown categorías -->
      <div class="relative">
        <button
          @click="showCategoryDropdown = !showCategoryDropdown"
          class="bg-white/95 hover:bg-white text-gray-700 p-2 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Categorías"
        >
          <Filter :size="16" />
        </button>

        <div
          v-if="showCategoryDropdown"
          class="absolute top-12 right-0 w-40 bg-white rounded-lg shadow-xl border border-gray-200 z-[1100]"
        >
          <button
            @click="selectCategoria(null)"
            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b"
          >
            Todas
          </button>
          <button
            v-for="(nombre, id) in categoriasDisponibles"
            :key="id"
            @click="selectCategoria(parseInt(id as string))"
            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm"
          >
            {{ nombre }}
          </button>
        </div>
      </div>

      <!-- Dropdown operaciones -->
      <div class="relative">
        <button
          @click="showOperationDropdown = !showOperationDropdown"
          class="bg-white/95 hover:bg-white text-gray-700 p-2 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Operaciones"
        >
          <DollarSign :size="16" />
        </button>

        <div
          v-if="showOperationDropdown"
          class="absolute top-12 right-0 w-36 bg-white rounded-lg shadow-xl border border-gray-200 z-[1100]"
        >
          <button
            @click="selectOperacion(null)"
            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b"
          >
            Todas
          </button>
          <button
            v-for="op in operacionesDisponibles"
            :key="op.value"
            @click="selectOperacion(op.value)"
            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm"
          >
            {{ op.label }}
          </button>
        </div>
      </div>

      <!-- Limpiar filtros -->
      <button
        v-if="categoriaSeleccionada || operacionSeleccionada"
        @click="resetFilters"
        class="bg-red-500/90 hover:bg-red-600/90 text-white p-2 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Limpiar filtros"
      >
        <X :size="16" />
      </button>
    </div>

    <!-- Contador -->
    <div class="fixed top-16 right-2 z-[1200] lg:hidden">
      <div class="bg-white/95 px-3 py-1 rounded-lg shadow-lg backdrop-blur-sm">
        <p class="text-xs font-semibold text-gray-700">
          {{ totalPropiedadesFiltradas }}/{{ totalPropiedades }}
        </p>
      </div>
    </div>

    <!-- Botones inferiores -->
    <div class="fixed bottom-4 right-4 z-[1200] lg:hidden flex flex-col gap-3">
      <!-- Radar / Quitar Radar -->
      <button
        v-if="!radarMode"
        @click="activateRadarMode"
        class="bg-purple-600/90 hover:bg-purple-700/90 text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Activar modo radar para buscar propiedades"
        aria-label="Activar modo radar"
      >
        <Radar :size="18" />
      </button>

      <button
        v-if="radarMode"
        @click="resetRadar"
        class="bg-red-600/90 hover:bg-red-700/90 text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm animate-pulse"
        title="Desactivar modo radar"
        aria-label="Desactivar modo radar"
      >
        <X :size="18" />
      </button>

      <!-- Ver todo -->
      <button
        @click="resetView"
        class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Ver todas las propiedades en el mapa"
        aria-label="Ver todas las propiedades"
      >
        <MapPin :size="18" />
      </button>

      <!-- Mi ubicación -->
      <button
        @click="locateMe"
        :disabled="isLocatingUser"
        :class="[
          'p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm',
          isLocatingUser ? 'bg-blue-400/90 cursor-not-allowed' : 'bg-blue-600/90 hover:bg-blue-700/90 text-white'
        ]"
        title="Mi ubicación"
        aria-label="Centrar en mi ubicación actual"
      >
        <Navigation :size="18" :class="isLocatingUser ? 'animate-pulse' : ''" />
      </button>
    </div>

    <!-- MENSAJE DE INSTRUCCIÓN -->
    <div
      v-if="radarMode && !radarPlaced"
      class="absolute top-20 left-1/2 -translate-x-1/2 z-[1000] bg-purple-600 text-white px-4 py-2 rounded-lg shadow-xl"
    >
      <p class="text-sm font-medium flex items-center gap-2">
        <MapPin :size="16" />
        Selecciona en el mapa donde quieres fijar el punto del radar
      </p>
    </div>

    <!-- CONTROL DE RADIO (COMPACTO) -->
    <div
      v-if="radarPlaced && !showPanel"
      class="absolute bottom-20 left-1/2 -translate-x-1/2 z-[1000] bg-white p-3 rounded-xl shadow-2xl w-80"
    >
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <label class="font-semibold text-gray-800 text-sm">Radio de búsqueda</label>
          <span class="bg-blue-600 text-white px-3 py-1 rounded-full font-bold text-sm">
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
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
          :style="{
            background: `linear-gradient(to right, #3B82F6 0%, #3B82F6 ${((radarRadius - 100) / (5000 - 100) * 100)}%, #E5E7EB ${((radarRadius - 100) / (5000 - 100) * 100)}%, #E5E7EB 100%)`
          }"
        />

        <div class="flex justify-between text-xs text-gray-500">
          <span>100m</span>
          <span>2.5km</span>
          <span>5km</span>
        </div>

        <button
          @click="search"
          class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-2 rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all"
        >
          🎯 Buscar Propiedades
        </button>
      </div>
    </div>

      <!-- PANEL LATERAL DE RESULTADOS -->
    <transition name="slide">
      <div
        v-if="showPanel"
        class="absolute right-0 top-0 h-full w-full sm:w-80 md:w-96 bg-white shadow-2xl z-[1300] flex flex-col"
      >
        <!-- Header Compacto -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-3 sm:p-4 flex justify-between items-center flex-shrink-0">
          <div>
            <h3 class="font-bold text-sm sm:text-base">📍 Propiedades encontradas</h3>
            <p class="text-xs text-blue-100">{{ filteredResults.length }} resultados</p>
          </div>
          <button
            @click="showPanel = false"
            class="hover:bg-white/20 p-2 sm:p-1 rounded-lg transition-colors"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Búsqueda Compacta -->
        <div class="p-3 sm:p-2 border-b bg-gray-50 flex-shrink-0">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="16" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar en resultados..."
              class="w-full pl-10 pr-10 py-2 sm:py-1.5 text-sm sm:text-xs border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
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

        <!-- Lista de resultados compacta -->
        <div class="flex-1 overflow-y-auto p-2 sm:p-3 space-y-2">
          <div
            v-for="p in filteredResults"
            :key="p.id"
            @click="focusProperty(p)"
            class="cursor-pointer border border-gray-200 hover:border-blue-500 p-2 sm:p-3 rounded-lg hover:bg-blue-50 transition-all transform hover:scale-[1.01] hover:shadow-sm"
          >
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-2 gap-2">
              <h4 class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-2 flex-1">{{ p.name }}</h4>
              <span class="text-green-600 font-bold text-sm sm:text-base ml-0 sm:ml-2 flex-shrink-0">
                $US {{ ((p.price / 1000).toFixed(0)) }}k
              </span>
            </div>

            <p class="text-xs text-gray-500 mb-2">{{ p.codigo_inmueble }}</p>

            <!-- Superficie compacta -->
            <div class="bg-gray-50 rounded-lg p-2 mb-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <div>
                  <p class="text-blue-600 font-medium mb-1">📐 Útil</p>
                  <p class="font-bold text-blue-800 text-sm sm:text-xs">
                    {{ p.superficie_util ? p.superficie_util.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-blue-700 text-xs" v-if="getPricePerSqmUtil(p)">
                    {{ getPricePerSqmUtil(p)!.toFixed(0) }} $US/m²
                  </p>
                </div>
                <div>
                  <p class="text-orange-600 font-medium mb-1">🏢 Constr.</p>
                  <p class="font-bold text-orange-800 text-sm sm:text-xs">
                    {{ p.superficie_construida ? p.superficie_construida.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-orange-700 text-xs" v-if="getPricePerSqmConstruida(p)">
                    {{ getPricePerSqmConstruida(p)!.toFixed(0) }} $US/m²
                  </p>
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs gap-1">
              <span v-if="p.category" class="bg-gray-100 px-2 py-1 rounded text-gray-700 inline-block w-fit">
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

        <!-- Footer compacto con promedios -->
        <div
          v-if="averagePricePerSqmUtil || averagePricePerSqmConstruida"
          class="border-t bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 sm:p-3 flex-shrink-0"
        >
          <p class="text-center text-sm sm:text-xs font-bold text-blue-100 mb-3 sm:mb-2">📊 Precio promedio en la zona</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Promedio Útil -->
            <div
              v-if="averagePricePerSqmUtil"
              class="bg-white/10 rounded-lg p-4 sm:p-3 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-colors"
            >
              <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-lg sm:text-base">📐</span>
                <p class="text-xs sm:text-xs text-blue-100 font-medium">Superficie Útil</p>
              </div>
              <div class="mb-2">
                <p class="text-2xl sm:text-lg font-extrabold text-white leading-tight">
                  $US {{ averagePricePerSqmUtil.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                </p>
                <p class="text-xs text-blue-200 mt-1">por metro cuadrado</p>
              </div>
              <div class="flex items-center justify-center gap-1 text-xs text-blue-100">
                <span class="bg-white/20 px-2 py-0.5 rounded-full">
                  {{ filteredResults.filter(p => p.superficie_util && p.superficie_util > 0).length }} propiedades
                </span>
              </div>
            </div>

            <!-- Promedio Construida -->
            <div
              v-if="averagePricePerSqmConstruida"
              class="bg-white/10 rounded-lg p-4 sm:p-3 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-colors"
            >
              <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-lg sm:text-base">🏢</span>
                <p class="text-xs sm:text-xs text-blue-100 font-medium">Superficie Construida</p>
              </div>
              <div class="mb-2">
                <p class="text-2xl sm:text-lg font-extrabold text-white leading-tight">
                  $US {{ averagePricePerSqmConstruida.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                </p>
                <p class="text-xs text-blue-200 mt-1">por metro cuadrado</p>
              </div>
              <div class="flex items-center justify-center gap-1 text-xs text-blue-100">
                <span class="bg-white/20 px-2 py-0.5 rounded-full">
                  {{ filteredResults.filter(p => p.superficie_construida && p.superficie_construida > 0).length }} propiedades
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style>
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Estilo del slider */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}
</style>
