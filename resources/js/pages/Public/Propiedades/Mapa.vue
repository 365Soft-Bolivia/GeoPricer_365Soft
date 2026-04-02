<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import { ArrowLeft, Navigation, X, Search, Radar, MapPin, Home, Filter, ChevronDown, DollarSign, Building, Key, FileText, Check, Eye } from 'lucide-vue-next';
import PropertyDetailsModal from '@/components/public/PropertyDetailsModal.vue';
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

/* ================= PROPS ================= */
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
  price_usd?: number | null;
  price_bob?: number | null;
  operacion: string;
  category?: string;
  category_id?: number | null;
  superficie_util?: number; // metros cuadrados de superficie útil
  superficie_construida?: number; // metros cuadrados construidos
  ambientes?: number;
  habitaciones?: number;
  banos?: number;
  cocheras?: number;
  default_image?: string | null;
  images?: ProductImage[];
  descripcion?: string;
  location: {
    latitude: number;
    longitude: number;
    is_active: boolean;
    address?: string;
  };
}

const props = defineProps<{
  productsConUbicacion: Product[];
  categoriasDisponibles: Record<number, string>;
  totalPropiedades: number;
  defaultCenter?: { lat: number; lng: number };
  filtrosAplicados?: {
    categoria?: number | null;
    operacion?: string | null;
    precio_min?: number | null;
    precio_max?: number | null;
    habitaciones?: number | null;
    banos?: number | null;
    ubicaciones?: string[] | null;
  };
}>();

const defaultCenter = props.defaultCenter || { lat: -17.4167, lng: -66.1667 };

/* ================= MAP ================= */
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map;
const markers: L.Marker[] = [];
let markerClusterGroup: L.MarkerClusterGroup | null = null;

/* ================= USER LOCATION ================= */
let userLocationMarker: L.Marker | null = null;
const isLocatingUser = ref(false);

/* ================= LOCATION SEARCH ================= */
const searchLocationInput = ref('');
const searchResults = ref<any[]>([]);
const isSearchingLocation = ref(false);
let searchLocationMarker: L.Marker | null = null;
let searchTimeout: number | null = null;
const showSearchDropdown = ref(false);
const isSearchExpanded = ref(false); // Controla si el buscador está expandido

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

/* ================= PROGRESSIVE LOADING STATE ================= */
const loadingState = ref({
  loaded: 0,
  total: 0,
  isLoading: false,
  isComplete: false,
  cancelled: false,
  progress: 0
});
let currentCursor: string | null = null;
const loadedProperties = ref<Product[]>([]); // Propiedades cargadas progresivamente

// Verificar si hay filtros aplicados para decidir si mostrar pantalla de selección
const tieneFiltrosAplicados = props.filtrosAplicados && (
  props.filtrosAplicados.categoria !== null ||
  props.filtrosAplicados.operacion !== null
);
const showSelectionScreen = ref(!tieneFiltrosAplicados); // No mostrar si hay filtros
const showFilterPanel = ref(false); // Mostrar panel lateral de filtros en el mapa

/* ================= MODAL PREVIO DE SELECCIÓN DE PROPIEDADES ================= */
const showSelectionModal = ref(false); // Modal previo para seleccionar propiedades
const selectedPropertyIds = ref<Set<number>>(new Set()); // IDs de propiedades seleccionadas con checkboxes
const tempResults = ref<Product[]>([]); // Resultados temporales antes de confirmar selección

// Modal state
const selectedProperty = ref<Product | null>(null);
const showPropertyModal = ref(false);

/* ================= AUTO-HIDE SUCCESS MESSAGE ================= */
let successMessageTimeout: number | null = null;

// Watch para ocultar automáticamente el mensaje de éxito después de 5 segundos
watch(() => loadingState.value.isComplete, (isComplete) => {
  if (isComplete) {
    // Limpiar timeout anterior si existe
    if (successMessageTimeout) {
      clearTimeout(successMessageTimeout);
    }

    // Ocultar después de 5 segundos
    successMessageTimeout = window.setTimeout(() => {
      loadingState.value.isComplete = false;
      console.log('Mensaje de éxito ocultado automáticamente (5 segundos)');
    }, 5000);
  }
});

// Limpiar timeout al desmontar el componente
// NOTA: Hay otro onUnmounted más abajo que también maneja la limpieza
/* ================= FILTROS ================= */
// Inicializar con los filtros aplicados si existen
const categoriaSeleccionada = ref<number | null>(props.filtrosAplicados?.categoria ?? null);
const operacionSeleccionada = ref<string | null>(props.filtrosAplicados?.operacion ?? null);
const showCategoryDropdown = ref(false);
const showOperationDropdown = ref(false);

// Debug: Mostrar filtros recibidos
if (props.filtrosAplicados) {
  console.log('Filtros recibidos en el mapa:', props.filtrosAplicados);
  console.log('Categoría seleccionada:', categoriaSeleccionada.value);
  console.log('Operación seleccionada:', operacionSeleccionada.value);
}

// Validar que se haya seleccionado al menos una opción
const canLoadMap = computed(() => {
  return categoriaSeleccionada.value !== null || operacionSeleccionada.value !== null;
});

const operacionesDisponibles = [
  { value: 'venta', label: 'Venta', icon: DollarSign }
];

const nombreCategoriaSeleccionada = computed(() => {
  if (!categoriaSeleccionada.value) return 'Sin categoría';
  return props.categoriasDisponibles[categoriaSeleccionada.value] || 'Sin categoría';
});

const nombreOperacionSeleccionada = computed(() => {
  if (!operacionSeleccionada.value) return 'Sin operación';
  return operacionesDisponibles.find(op => op.value === operacionSeleccionada.value)?.label || 'Sin operación';
});

const productosFiltrados = computed(() => {
  // Usar las propiedades cargadas progresivamente en lugar de props.productsConUbicacion
  const source = loadedProperties.value.length > 0 ? loadedProperties.value : props.productsConUbicacion;

  // Si no hay filtros seleccionados, retornar las propiedades cargadas
  if (!categoriaSeleccionada.value && !operacionSeleccionada.value) {
    return source;
  }

  return source.filter(product => {
    if (!product.location.is_active) return false;

    // Filtro por categoría (si está seleccionada)
    if (categoriaSeleccionada.value && product.category_id !== categoriaSeleccionada.value) {
      return false;
    }

    // Filtro por operación (si está seleccionada)
    if (operacionSeleccionada.value && product.operacion !== operacionSeleccionada.value) {
      return false;
    }

    // Búsqueda por texto
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      return (
        product.name.toLowerCase().includes(query) ||
        product.codigo_inmueble.toLowerCase().includes(query)
      );
    }

    return true;
  });
});

/* ================= HELPER FUNCTIONS ================= */
// Obtener URL de imagen
const getPropertyImageUrl = (product: Product) => {
  if (product.default_image) {
    return `/storage/${product.default_image}`;
  }
  if (product.images && product.images.length > 0) {
    const primaryImage = product.images.find(img => img.is_primary);
    const imageToUse = primaryImage || product.images[0];
    return `/storage/${imageToUse.image_path}`;
  }
  return null;
};

// Abrir modal de detalles
const openPropertyModal = (product: Product) => {
  selectedProperty.value = product;
  showPropertyModal.value = true;
};

// Cerrar modal
const closePropertyModal = () => {
  showPropertyModal.value = false;
  selectedProperty.value = null;
};

const formatPrice = (price?: number | null, currency: string = '$') => {
  if (!price) return '';
  return `${currency}${price.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

const getPriceDisplay = (product: Product) => {
  const prices = [];
  if (product.price_usd) {
    prices.push(`<span class="text-[#233C7A] font-bold">${formatPrice(product.price_usd, '$')} USD</span>`);
  }
  if (product.price_bob) {
    prices.push(`<span class="text-[#E0081D] font-bold">${formatPrice(product.price_bob, 'Bs.')} BOB</span>`);
  }
  return prices.join('<br>') || '<span class="text-gray-400">Precio no disponible</span>';
};

// Generar popup HTML mejorado sin imagen y con botón
const getPopupContent = (product: Product, extraInfo: string = '') => {
  // Badge de operación con color
  const operacionColors = {
    'venta': 'bg-[#233C7A]'      /* Azul Alfa */
  };
  const operacionColor = operacionColors[product.operacion as keyof typeof operacionColors] || 'bg-gray-500';

  return `
    <div class="property-popup min-w-[280px] max-w-[320px]">
      <div class="p-3 space-y-2">
        <!-- Tipo de operación -->
        <div class="flex items-center justify-between">
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold text-white ${operacionColor}">
            ${product.operacion.charAt(0).toUpperCase() + product.operacion.slice(1)}
          </span>
          ${product.ambientes ? `<span class="text-xs text-gray-500">${product.ambientes} amb.</span>` : ''}
        </div>

        <!-- Título -->
        <div>
          <h3 class="font-bold text-sm text-gray-900 leading-tight line-clamp-2">${product.name}</h3>
          <p class="text-xs text-gray-500 mt-0.5">${product.codigo_inmueble}</p>
        </div>

        <!-- Precio -->
        <div class="py-1">
          ${getPriceDisplay(product)}
        </div>

        <!-- Características principales -->
        <div class="flex flex-wrap gap-1 text-xs text-gray-600">
          ${product.habitaciones ? `<span class="flex items-center gap-1">🛏️ ${product.habitaciones}</span>` : ''}
          ${product.banos ? `<span class="flex items-center gap-1">🚿 ${product.banos}</span>` : ''}
          ${product.cocheras ? `<span class="flex items-center gap-1">🚗 ${product.cocheras}</span>` : ''}
          ${product.superficie_construida ? `<span class="flex items-center gap-1">📐 ${product.superficie_construida}m²</span>` : ''}
        </div>

        <!-- Información extra (distancia, etc.) -->
        ${extraInfo ? `<div class="text-xs text-blue-600 font-medium">${extraInfo}</div>` : ''}

        <!-- Botón Ver Detalles -->
        <button
          data-property-id="${product.id}"
          class="view-property-btn w-full mt-2 py-2 px-3 bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] hover:from-[#E0081D] hover:to-[#233C7A] text-white text-xs font-bold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
          Ver Ficha Completa
        </button>
      </div>

      <style>
        .line-clamp-2 {
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }
      </style>
    </div>
  `;
};

// Función para agregar event listeners a los botones de los popups
const attachPopupButtonListeners = () => {
  // Usar event delegation para escuchar clicks en los botones
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const button = target.closest('.view-property-btn') as HTMLElement;

    if (button) {
      const propertyId = parseInt(button.getAttribute('data-property-id') || '0');
      if (propertyId) {
        const product = props.productsConUbicacion.find(p => p.id === propertyId);
        if (product) {
          openPropertyModal(product);
        }
      }
    }
  });
};

// Calcular precio por metro cuadrado de superficie útil
const getPricePerSqmUtil = (product: Product) => {
  if (!product.superficie_util || product.superficie_util <= 0) return null;
  // Priorizar precio USD para cálculos
  const price = product.price_usd || product.price_bob;
  if (!price) return null;
  return price / product.superficie_util;
};

// Calcular precio por metro cuadrado de superficie construida
const getPricePerSqmConstruida = (product: Product) => {
  if (!product.superficie_construida || product.superficie_construida <= 0) return null;
  // Priorizar precio USD para cálculos
  const price = product.price_usd || product.price_bob;
  if (!price) return null;
  return price / product.superficie_construida;
};

// Formatear precio para el panel de resultados (Opción 3)
const getResultPriceDisplay = (product: Product) => {
  const hasUSD = product.price_usd && product.price_usd > 0;
  const hasBOB = product.price_bob && product.price_bob > 0;

  if (!hasUSD && !hasBOB) {
    return '<span class="text-gray-400 text-sm">Precio no disponible</span>';
  }

  let display = '';
  if (hasUSD) {
    display += `<div class="text-green-600 font-bold text-sm">💰 $${product.price_usd!.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} USD</div>`;
  }
  if (hasBOB) {
    const spacing = hasUSD ? 'mt-1' : '';
    display += `<div class="text-blue-600 font-semibold text-xs ${spacing}">🔸 Bs. ${product.price_bob!.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} BOB</div>`;
  }

  return display;
};

// Obtener precio por m² formateado con AMBAS monedas
const getPricePerSqmUtilDisplay = (product: Product) => {
  const prices = [];

  // Calcular y mostrar precio en USD si está disponible
  if (product.price_usd && product.superficie_util && product.superficie_util > 0) {
    const usdPerSqm = product.price_usd / product.superficie_util;
    prices.push(`${usdPerSqm.toFixed(0)} $US/m²`);
  }

  // Calcular y mostrar precio en BOB si está disponible
  if (product.price_bob && product.superficie_util && product.superficie_util > 0) {
    const bobPerSqm = product.price_bob / product.superficie_util;
    prices.push(`${bobPerSqm.toFixed(0)} Bs./m²`);
  }

  return prices.length > 0 ? prices.join(' | ') : null;
};

const getPricePerSqmConstruidaDisplay = (product: Product) => {
  const prices = [];

  // Calcular y mostrar precio en USD si está disponible
  if (product.price_usd && product.superficie_construida && product.superficie_construida > 0) {
    const usdPerSqm = product.price_usd / product.superficie_construida;
    prices.push(`${usdPerSqm.toFixed(0)} $US/m²`);
  }

  // Calcular y mostrar precio en BOB si está disponible
  if (product.price_bob && product.superficie_construida && product.superficie_construida > 0) {
    const bobPerSqm = product.price_bob / product.superficie_construida;
    prices.push(`${bobPerSqm.toFixed(0)} Bs./m²`);
  }

  return prices.length > 0 ? prices.join(' | ') : null;
};

/* ================= FUNCIONES DE ANTICRÉTICO ================= */
const getAnticreticoMensual = (product: Product) => {
  const tasaAnual = 0.30; // 30% anual
  const resultado = {
    usd: null as number | null,
    bob: null as number | null,
  };

  // Calcular equivalencia mensual para USD
  if (product.price_usd) {
    resultado.usd = (product.price_usd * tasaAnual) / 12;
  }

  // Calcular equivalencia mensual para BOB
  if (product.price_bob) {
    resultado.bob = (product.price_bob * tasaAnual) / 12;
  }

  return resultado;
};

const getAnticreticoMensualDisplay = (product: Product) => {
  const mensual = getAnticreticoMensual(product);
  const partes = [];

  if (mensual.usd) {
    partes.push(`
      <div class="text-green-600 font-bold text-sm">
        💵 $${mensual.usd.toLocaleString('es-BO', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        })}/mes USD
      </div>
    `);
  }

  if (mensual.bob) {
    const spacing = mensual.usd ? 'mt-1' : '';
    partes.push(`
      <div class="text-blue-600 font-semibold text-xs ${spacing}">
        📊 Bs. ${mensual.bob.toLocaleString('es-BO', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        })}/mes BOB
      </div>
    `);
  }

  return partes.join('') || '<span class="text-gray-400 text-xs">No disponible</span>';
};

// Calcular promedio de equivalencia mensual en el área del radar
const averageAnticreticoMensual = computed(() => {
  const resultado = {
    usd: null as number | null,
    bob: null as number | null,
    count: 0,
  };

  const validProducts = filteredResults.value.filter(
    p => (p.operacion === 'anticretico') && (p.price_usd || p.price_bob)
  );

  if (validProducts.length === 0) return resultado;

  // Sumar equivalencias mensuales USD
  const usdProducts = validProducts.filter(p => p.price_usd);
  if (usdProducts.length > 0) {
    resultado.usd = usdProducts.reduce((sum, p) => {
      return sum + ((p.price_usd! * 0.30) / 12);
    }, 0) / usdProducts.length;
  }

  // Sumar equivalencias mensuales BOB
  const bobProducts = validProducts.filter(p => p.price_bob);
  if (bobProducts.length > 0) {
    resultado.bob = bobProducts.reduce((sum, p) => {
      return sum + ((p.price_bob! * 0.30) / 12);
    }, 0) / bobProducts.length;
  }

  resultado.count = validProducts.length;

  return resultado;
});

/* ================= FUNCIONES DE JUSTIFICACIÓN DE ALQUILER ================= */
interface ZonaConfig {
  nombre: string;
  base_alquiler: number;
  lat_center: number;
  lng_center: number;
  radio_km: number;
}

// Configuración de zonas (debe coincidir con config/alquiler.php)
const zonasConfig: ZonaConfig[] = [
  { nombre: 'Sopocachi', base_alquiler: 600, lat_center: -16.5150, lng_center: -68.1250, radio_km: 1.5 },
  { nombre: 'San Miguel', base_alquiler: 550, lat_center: -16.5250, lng_center: -68.1150, radio_km: 2.0 },
  { nombre: 'Calacoto', base_alquiler: 700, lat_center: -16.5350, lng_center: -68.0850, radio_km: 2.5 },
  { nombre: 'Miraflores', base_alquiler: 500, lat_center: -16.5050, lng_center: -68.1350, radio_km: 2.0 },
  { nombre: 'Centro', base_alquiler: 450, lat_center: -16.4950, lng_center: -68.1350, radio_km: 1.5 },
  { nombre: 'Obrajes', base_alquiler: 520, lat_center: -16.5450, lng_center: -68.0750, radio_km: 2.0 },
  { nombre: 'San Pedro', base_alquiler: 480, lat_center: -16.5050, lng_center: -68.1450, radio_km: 1.8 },
  { nombre: 'Zona Sur', base_alquiler: 400, lat_center: -16.5500, lng_center: -68.0500, radio_km: 5.0 },
];

const getZonaFromLocation = (lat: number, lng: number): ZonaConfig => {
  // Buscar la zona más cercana
  for (const zona of zonasConfig) {
    const distance = Math.sqrt(
      Math.pow(lat - zona.lat_center, 2) + Math.pow(lng - zona.lng_center, 2)
    ) * 111; // Convertir a km (aproximado)

    if (distance <= zona.radio_km) {
      return zona;
    }
  }

  // Si no encuentra zona, retornar "Otra Zona"
  return {
    nombre: 'Otra Zona',
    base_alquiler: 400,
    lat_center: -17.38,
    lng_center: -66.16,
    radio_km: 50,
  };
};

interface AlquilerJustificacion {
  base: number;
  habitaciones: number;
  banos: number;
  cocheras: number;
  superficie_extra: number;
  amenities: number;
  estado: number;
  total_calculado: number;
  precio_real: number;
  diferencia: number;
  porcentaje_diferencia: number;
  zona: string;
}

const getAlquilerJustificacion = (product: Product): AlquilerJustificacion => {
  const justificacion: AlquilerJustificacion = {
    base: 0,
    habitaciones: 0,
    banos: 0,
    cocheras: 0,
    superficie_extra: 0,
    amenities: 0,
    estado: 0,
    total_calculado: 0,
    precio_real: product.price_usd || product.price_bob || 0,
    diferencia: 0,
    porcentaje_diferencia: 0,
    zona: '',
  };

  // 1. Detectar zona y obtener base
  const zona = getZonaFromLocation(product.location.latitude, product.location.longitude);
  justificacion.base = zona.base_alquiler;
  justificacion.zona = zona.nombre;

  // 2. Calcular valor de habitaciones
  if (product.habitaciones) {
    justificacion.habitaciones = product.habitaciones * 100;
  }

  // 3. Calcular valor de baños
  if (product.banos) {
    justificacion.banos = product.banos * 50;
  }

  // 4. Calcular valor de cocheras
  if (product.cocheras) {
    justificacion.cocheras = product.cocheras * 80;
  }

  // 5. Calcular valor por superficie extra (sobre 80m²)
  const superficieBase = 80;
  const superficie = product.superficie_construida || product.superficie_util || 0;
  if (superficie > superficieBase) {
    justificacion.superficie_extra = (superficie - superficieBase) * 5;
  }

  // 6. Detectar amenities según superficie
  if (superficie >= 150) {
    justificacion.amenities += 60; // Piscina probable
  }
  if (superficie >= 120) {
    justificacion.amenities += 40; // Gimnasio probable
  }
  if (superficie >= 100) {
    justificacion.amenities += 30; // Seguridad 24h probable
  }

  // 7. Ajuste por estado (asumimos "bueno" por defecto)
  justificacion.estado = -50; // Descuento por estado bueno

  // 8. Calcular total
  justificacion.total_calculado = justificacion.base +
    justificacion.habitaciones +
    justificacion.banos +
    justificacion.cocheras +
    justificacion.superficie_extra +
    justificacion.amenities +
    justificacion.estado;

  // 9. Comparar con precio real
  justificacion.diferencia = justificacion.precio_real - justificacion.total_calculado;
  justificacion.porcentaje_diferencia = justificacion.total_calculado > 0
    ? (justificacion.diferencia / justificacion.total_calculado) * 100
    : 0;

  return justificacion;
};

const getAlquilerJustificacionDisplay = (product: Product): string => {
  const justificacion = getAlquilerJustificacion(product);
  const partes = [];

  // Título
  partes.push(`
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mt-2">
      <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">
        📊 Justificación del Precio:
      </p>
      <div class="space-y-1 text-xs">
        <div class="flex justify-between">
          <span class="text-gray-600 dark:text-gray-300">📍 Base ${justificacion.zona}:</span>
          <span class="font-semibold text-gray-800 dark:text-white">$${justificacion.base}</span>
        </div>
  `);

  // Habitaciones
  if (justificacion.habitaciones > 0) {
    const numHabitaciones = product.habitaciones || 0;
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">🛏️ Hab. (${numHabitaciones}):</span>
        <span class="font-semibold text-green-600">+$${justificacion.habitaciones}</span>
      </div>
    `);
  }

  // Baños
  if (justificacion.banos > 0) {
    const numBanos = product.banos || 0;
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">🚿 Baños (${numBanos}):</span>
        <span class="font-semibold text-green-600">+$${justificacion.banos}</span>
      </div>
    `);
  }

  // Cocheras
  if (justificacion.cocheras > 0) {
    const numCocheras = product.cocheras || 0;
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">🚗 Cochera (${numCocheras}):</span>
        <span class="font-semibold text-green-600">+$${justificacion.cocheras}</span>
      </div>
    `);
  }

  // Superficie extra
  if (justificacion.superficie_extra > 0) {
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">📐 Sup. extra:</span>
        <span class="font-semibold text-green-600">+$${justificacion.superficie_extra}</span>
      </div>
    `);
  }

  // Amenities
  if (justificacion.amenities > 0) {
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">🏊 Amenities:</span>
        <span class="font-semibold text-green-600">+$${justificacion.amenities}</span>
      </div>
    `);
  }

  // Estado
  if (justificacion.estado !== 0) {
    const estadoTexto = justificacion.estado < 0 ? 'Buen estado' : 'Nuevo';
    const color = justificacion.estado < 0 ? 'text-orange-600' : 'text-green-600';
    const signo = justificacion.estado < 0 ? '' : '+';
    partes.push(`
      <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-300">✨ Estado:</span>
        <span class="font-semibold ${color}">${signo}$${justificacion.estado}</span>
      </div>
    `);
  }

  // Total calculado
  partes.push(`
        <div class="border-t border-gray-300 dark:border-gray-600 my-2 pt-2">
          <div class="flex justify-between">
            <span class="font-bold text-gray-800 dark:text-white">💵 TOTAL:</span>
            <span class="font-extrabold text-gray-800 dark:text-white">$${justificacion.total_calculado}</span>
          </div>
        </div>
      </div>
    </div>
  `);

  // Comparación con precio real
  const indicador = getIndicadorJustificacion(justificacion.porcentaje_diferencia);
  partes.push(`
    <div class="mt-2 p-2 rounded-lg ${indicador.bg_class}">
      <p class="text-xs font-semibold ${indicador.text_class} flex items-center gap-1">
        ${indicador.icon} vs Precio real ($${justificacion.precio_real}):
        <span class="ml-auto">${justificacion.diferencia > 0 ? '+' : ''}$${justificacion.diferencia.toFixed(0)}</span>
      </p>
      <p class="text-xs ${indicador.text_sub_class} mt-1">
        ${indicador.mensaje}
      </p>
    </div>
  `);

  return partes.join('');
};

interface IndicadorJustificacion {
  nivel: string;
  icon: string;
  bg_class: string;
  text_class: string;
  text_sub_class: string;
  mensaje: string;
}

const getIndicadorJustificacion = (porcentaje: number): IndicadorJustificacion => {
  if (porcentaje < -15) {
    return {
      nivel: 'muy_barato',
      icon: '🔥',
      bg_class: 'bg-green-100 dark:bg-green-900/30',
      text_class: 'text-green-700 dark:text-green-300',
      text_sub_class: 'text-green-600 dark:text-green-400',
      mensaje: '¡MUY POR DEBAJO! Podría estar subvalorado.',
    };
  } else if (porcentaje < -5) {
    return {
      nivel: 'barato',
      icon: '✅',
      bg_class: 'bg-green-50 dark:bg-green-900/20',
      text_class: 'text-green-600 dark:text-green-400',
      text_sub_class: 'text-green-500 dark:text-green-300',
      mensaje: 'Por debajo del promedio - ¡Oportunidad!',
    };
  } else if (porcentaje >= -5 && porcentaje <= 5) {
    return {
      nivel: 'justificado',
      icon: '⭐',
      bg_class: 'bg-blue-50 dark:bg-blue-900/20',
      text_class: 'text-blue-700 dark:text-blue-300',
      text_sub_class: 'text-blue-600 dark:text-blue-400',
      mensaje: 'Precio JUSTIFICADO según características.',
    };
  } else if (porcentaje <= 15) {
    return {
      nivel: 'caro',
      icon: '⚠️',
      bg_class: 'bg-orange-50 dark:bg-orange-900/20',
      text_class: 'text-orange-700 dark:text-orange-300',
      text_sub_class: 'text-orange-600 dark:text-orange-400',
      mensaje: 'Ligeramente CARO - evalúa si vale la pena.',
    };
  } else {
    return {
      nivel: 'muy_caro',
      icon: '🚨',
      bg_class: 'bg-red-100 dark:bg-red-900/30',
      text_class: 'text-red-700 dark:text-red-300',
      text_sub_class: 'text-red-600 dark:text-red-400',
      mensaje: '¡MUY CARO! Revisa si hay amenities extras.',
    };
  }
};

// Calcular promedio de alquiler en la zona del radar
const averageAlquiler = computed(() => {
  const alquilerProducts = filteredResults.value.filter(
    p => (p.operacion === 'alquiler') && (p.price_usd || p.price_bob)
  );

  if (alquilerProducts.length === 0) {
    return {
      usd: null as number | null,
      bob: null as number | null,
      count: 0,
    };
  }

  // Promedio USD
  const usdProducts = alquilerProducts.filter(p => p.price_usd);
  const avgUsd = usdProducts.length > 0
    ? usdProducts.reduce((sum, p) => sum + p.price_usd!, 0) / usdProducts.length
    : null;

  // Promedio BOB
  const bobProducts = alquilerProducts.filter(p => p.price_bob);
  const avgBob = bobProducts.length > 0
    ? bobProducts.reduce((sum, p) => sum + p.price_bob!, 0) / bobProducts.length
    : null;

  return {
    usd: avgUsd,
    bob: avgBob,
    count: alquilerProducts.length,
  };
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

// Calcular promedio de precio por m² útil de la zona
const averagePricePerSqmUtil = computed(() => {
  const validProducts = filteredResults.value.filter(p => p.superficie_util && p.superficie_util > 0);

  if (validProducts.length === 0) return null;

  const sum = validProducts.reduce((acc, p) => {
    const price = p.price_usd || p.price_bob;
    if (!price) return acc;
    return acc + (price / p.superficie_util!);
  }, 0);

  const validCount = validProducts.filter(p => p.price_usd || p.price_bob).length;
  if (validCount === 0) return null;

  return sum / validCount;
});

// Calcular promedio de precio por m² construido de la zona
const averagePricePerSqmConstruida = computed(() => {
  const validProducts = filteredResults.value.filter(p => p.superficie_construida && p.superficie_construida > 0);

  if (validProducts.length === 0) return null;

  const sum = validProducts.reduce((acc, p) => {
    const price = p.price_usd || p.price_bob;
    if (!price) return acc;
    return acc + (price / p.superficie_construida!);
  }, 0);

  const validCount = validProducts.filter(p => p.price_usd || p.price_bob).length;
  if (validCount === 0) return null;

  return sum / validCount;
});

// Calcular promedios separados para USD y BOB
const averagePricePerSqm = computed(() => {
  const result = {
    util: { usd: null as number | null, bob: null as number | null },
    construida: { usd: null as number | null, bob: null as number | null }
  };

  // Calcular promedio USD por m² útil
  const utilUSD = filteredResults.value.filter(p => p.price_usd && p.superficie_util && p.superficie_util > 0);
  if (utilUSD.length > 0) {
    result.util.usd = utilUSD.reduce((acc, p) => acc + (p.price_usd! / p.superficie_util!), 0) / utilUSD.length;
  }

  // Calcular promedio BOB por m² útil
  const utilBOB = filteredResults.value.filter(p => p.price_bob && p.superficie_util && p.superficie_util > 0);
  if (utilBOB.length > 0) {
    result.util.bob = utilBOB.reduce((acc, p) => acc + (p.price_bob! / p.superficie_util!), 0) / utilBOB.length;
  }

  // Calcular promedio USD por m² construida
  const constrUSD = filteredResults.value.filter(p => p.price_usd && p.superficie_construida && p.superficie_construida > 0);
  if (constrUSD.length > 0) {
    result.construida.usd = constrUSD.reduce((acc, p) => acc + (p.price_usd! / p.superficie_construida!), 0) / constrUSD.length;
  }

  // Calcular promedio BOB por m² construida
  const constrBOB = filteredResults.value.filter(p => p.price_bob && p.superficie_construida && p.superficie_construida > 0);
  if (constrBOB.length > 0) {
    result.construida.bob = constrBOB.reduce((acc, p) => acc + (p.price_bob! / p.superficie_construida!), 0) / constrBOB.length;
  }

  return result;
});

// Funciones de navegación
const goBack = () => {
  router.visit('/');
};

const goToHome = () => {
  router.visit('/');
};

const goToProperties = () => {
  router.visit('/mapa-propiedades');
};

// Función para confirmar selección y cargar el mapa
const confirmSelectionAndLoadMap = async () => {
  if (!canLoadMap.value) {
    alert('Por favor, selecciona al menos una categoría o tipo de operación');
    return;
  }

  console.time('Tiempo total hasta mapa visible');

  // Ocultar pantalla de selección INMEDIATAMENTE
  showSelectionScreen.value = false;

  // Forzar re-render para mostrar el mapa vacío
  await new Promise(resolve => setTimeout(resolve, 50));

  // Inicializar el mapa INMEDIATAMENTE (esto es rápido, <100ms)
  console.time('initMap');
  initMap();
  console.timeEnd('initMap');

  // Preparar filtros
  const filtros = {
    categoria: categoriaSeleccionada.value,
    operacion: operacionSeleccionada.value
  };

  console.log('Mapa inicializado, iniciando carga progresiva...');
  console.timeEnd('Tiempo total hasta mapa visible');

  // Iniciar carga progresiva (esto NO bloquea el mapa)
  loadPropertiesProgressive(filtros);
};

// Función para abrir el panel lateral de filtros
const openFilterPanel = () => {
  showFilterPanel.value = true;
  console.log('Panel de filtros abierto');
  console.log('Filtros actuales:', {
    categoria: categoriaSeleccionada.value,
    operacion: operacionSeleccionada.value
  });
};

// Función para aplicar filtros desde el panel lateral
const applyFiltersFromPanel = () => {
  console.log('Intentando aplicar filtros desde el panel...');
  console.log('Filtros seleccionados:', {
    categoria: categoriaSeleccionada.value,
    operacion: operacionSeleccionada.value,
    canLoadMap: canLoadMap.value
  });

  if (!canLoadMap.value) {
    alert('Por favor, selecciona al menos una categoría o tipo de operación');
    return;
  }

  // Construir parámetros para la recarga
  const params: Record<string, any> = {};

  if (categoriaSeleccionada.value !== null) {
    params.categoria = categoriaSeleccionada.value;
  }

  if (operacionSeleccionada.value !== null) {
    params.operacion = operacionSeleccionada.value;
  }

  console.log('Recargando mapa con filtros:', params);

  // Recargar la página con los nuevos filtros para que el backend devuelva los productos correctos
  router.get('/mapa-propiedades', params);
};

const selectCategoria = (categoryId: number | null, updateMarkersImmediate = true) => {
  console.log('Categoría seleccionada:', categoryId, 'Update immediate:', updateMarkersImmediate);
  categoriaSeleccionada.value = categoryId;
  showCategoryDropdown.value = false;

  // Solo actualizar marcadores si se solicita explícitamente
  if (updateMarkersImmediate) {
    setTimeout(() => {
      if (map && markerClusterGroup) {
        updateMarkers();
      }
    }, 50);
  }
};

const selectOperacion = (operacion: string | null, updateMarkersImmediate = true) => {
  console.log('Operación seleccionada:', operacion, 'Update immediate:', updateMarkersImmediate);
  operacionSeleccionada.value = operacion;
  showOperationDropdown.value = false;

  // Solo actualizar marcadores si se solicita explícitamente
  if (updateMarkersImmediate) {
    setTimeout(() => {
      if (map && markerClusterGroup) {
        updateMarkers();
      }
    }, 50);
  }
};

// Eliminado resetFilters - no se permite limpiar filtros para evitar cargar todos los datos
// Para cambiar filtros, usar el panel lateral con "Cambiar Filtros"

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

  map = L.map(mapContainer.value).setView([defaultCenter.lat, defaultCenter.lng], 6);

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
      let color = '#FAB90E';  /* Amarillo Alfa - Color base */
      let size = 40;

      if (count >= 50) {
        color = '#E0081D';  /* Rojo Alfa */
        size = 60;
      } else if (count >= 20) {
        color = '#FAB90E';  /* Amarillo Alfa */
        size = 50;
      } else if (count >= 10) {
        color = '#233C7A';  /* Azul Alfa */
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
  // NO llamar a updateMarkers() aquí - los marcadores se agregan progresivamente
  // con loadPropertiesProgressive() -> addMarkersToMap()

  // Solo escuchar clicks cuando el modo radar está activo
  map.on('click', (e) => {
    if (radarMode.value && !radarPlaced.value) {
      placeRadar(e);
    }
  });
};

/* ================= PROGRESSIVE LOADING FUNCTIONS ================= */

/**
 * Obtiene un chunk de propiedades desde el backend usando axios
 */
const fetchPropertyChunk = async (cursor: string | null, filtros: any) => {
  console.time(`fetchPropertyChunk (cursor: ${cursor ? 'yes' : 'no'})`);

  try {
    const response = await axios.post('/api/v1/propiedades/mapa-chunk', {
      cursor: cursor,
      filtros: filtros
    }, {
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });

    console.timeEnd(`fetchPropertyChunk (cursor: ${cursor ? 'yes' : 'no'})`);

    console.log('Respuesta axios:', {
      status: response.status,
      data_length: response.data.data?.length,
      chunk_size: response.data.chunk_size,
      has_more: response.data.has_more,
      total_count: response.data.total_count
    });

    return response.data;
  } catch (error) {
    console.timeEnd(`fetchPropertyChunk (cursor: ${cursor ? 'yes' : 'no'})`);
    console.error('Error fetching property chunk:', error);

    if (axios.isAxiosError(error)) {
      throw new Error(`Error ${error.response?.status}: ${error.response?.data?.message || error.message}`);
    }

    throw error;
  }
};

/**
 * Agrega marcadores al mapa de forma acumulativa (no borra los anteriores)
 */
const addMarkersToMap = (newProperties: Product[]) => {
  if (!map || !markerClusterGroup) {
    console.error('Mapa o markerClusterGroup no está inicializado');
    return;
  }

  console.log(`Agregando ${newProperties.length} marcadores al mapa...`);

  // Guardar propiedades en loadedProperties para usar en filtros y radar
  loadedProperties.value.push(...newProperties);

  newProperties.forEach((product) => {
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

    marker.bindPopup(getPopupContent(product));

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });

  console.log(`Total de marcadores en el mapa: ${markers.length}`);
};

/**
 * Carga propiedades de forma progresiva en chunks de 500
 */
const loadPropertiesProgressive = async (filtros: any) => {
  console.log('='.repeat(60));
  console.log('Iniciando carga progresiva de propiedades (chunks de 500)...', filtros);
  console.time('loadPropertiesProgressive - TIEMPO TOTAL');

  // Resetear estado de carga
  loadingState.value = {
    loaded: 0,
    total: 0,
    isLoading: true,
    isComplete: false,
    cancelled: false,
    progress: 0
  };
  currentCursor = null;
  loadedProperties.value = []; // Limpiar propiedades anteriores

  try {
    let response: any; // Declarar fuera del do-while para que esté disponible en el while
    let chunkNumber = 0;

    do {
      chunkNumber++;
      console.log(`\n📦 Chunk #${chunkNumber} - Iniciando...`);

      // Verificar si el usuario canceló
      if (loadingState.value.cancelled) {
        console.log('❌ Carga cancelada por el usuario');
        break;
      }

      // Obtener chunk del backend
      response = await fetchPropertyChunk(currentCursor, filtros);

      console.log('✅ Chunk recibido del backend:', {
        success: response.success,
        data_length: response.data?.length,
        chunk_size: response.chunk_size,
        has_more: response.has_more,
        total_count: response.total_count,
        next_cursor: response.next_cursor ? 'present' : 'null'
      });

      if (!response.success) {
        throw new Error(response.message || 'Error al cargar propiedades');
      }

      // Actualizar total (solo el primer chunk lo trae)
      if (response.total_count !== null) {
        loadingState.value.total = response.total_count;
        console.log(`📊 Total de propiedades: ${loadingState.value.total}`);
      }

      // Agregar marcadores al mapa
      console.time(`addMarkersToMap - Chunk #${chunkNumber}`);
      addMarkersToMap(response.data);
      console.timeEnd(`addMarkersToMap - Chunk #${chunkNumber}`);

      // Actualizar contador
      loadingState.value.loaded += response.chunk_size;

      // Calcular progreso
      if (loadingState.value.total > 0) {
        loadingState.value.progress = Math.round(
          (loadingState.value.loaded / loadingState.value.total) * 100
        );
      }

      // Actualizar cursor para el siguiente chunk
      currentCursor = response.next_cursor;

      console.log(`✅ Chunk #${chunkNumber} completado: ${loadingState.value.loaded}/${loadingState.value.total} (${loadingState.value.progress}%)`);

      // Pausa moderada para carga gradual (1 segundo entre chunks)
      await new Promise(resolve => setTimeout(resolve, 1000));

    } while (response.has_more && !loadingState.value.cancelled);

    // Carga completada
    loadingState.value.isComplete = true;
    loadingState.value.isLoading = false;

    console.log('='.repeat(60));
    console.log('✅ Carga progresiva completada:', {
      total_loaded: loadingState.value.loaded,
      cancelled: loadingState.value.cancelled,
      total_chunks: chunkNumber
    });
    console.timeEnd('loadPropertiesProgressive - TIEMPO TOTAL');
    console.log('='.repeat(60));

  } catch (error) {
    console.error('Error en carga progresiva:', error);
    loadingState.value.isLoading = false;

    // NO redirigir, solo mostrar error
    const errorMessage = error instanceof Error ? error.message : 'Error desconocido';

    console.error('Detalles del error:', {
      message: errorMessage,
      error: error,
      cursor: currentCursor,
      loaded: loadingState.value.loaded,
      total: loadingState.value.total
    });

    // Mostrar error más informativo
    alert(`Error al cargar propiedades: ${errorMessage}\n\nPropiedades cargadas: ${loadingState.value.loaded}\n\nPor favor, recarga la página.`);
  }
};

/**
 * Cancela la carga progresiva
 */
const cancelProgressiveLoading = () => {
  console.log('Cancelando carga progresiva...');
  loadingState.value.cancelled = true;
  loadingState.value.isLoading = false;
};

// Mantener la función updateMarkers para compatibilidad con otras funcionalidades
const updateMarkers = () => {
  console.log('updateMarkers llamado');
  console.log('Estado del mapa:', { mapInitialized: !!map, clusterInitialized: !!markerClusterGroup });
  console.log('Filtros activos:', {
    categoria: categoriaSeleccionada.value,
    operacion: operacionSeleccionada.value
  });
  console.log('Productos a mostrar:', productosFiltrados.value.length);

  // Si hay una carga progresiva en curso, NO interferir
  if (loadingState.value.isLoading) {
    console.log('Carga progresiva en curso, omitiendo updateMarkers');
    return;
  }

  if (!map || !markerClusterGroup) {
    console.error('Mapa o markerClusterGroup no está inicializado');
    return;
  }

  markerClusterGroup.clearLayers();
  markers.length = 0;

  let markersCount = 0;

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

    marker.bindPopup(getPopupContent(product));

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
    markersCount++;
  });

  console.log(`Total de marcadores agregados: ${markersCount}`);
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
    case 'venta': return '#233C7A';     /* Azul Alfa */
    case 'alquiler': return '#E0081D';   /* Rojo Alfa */
    case 'anticretico': return '#FAB90E'; /* Amarillo Alfa */
    default: return '#525252';           /* Gris neutro */
  }
};

onMounted(() => {
  attachPopupButtonListeners();
  addFullScreenStyles();

  // No inicializar el mapa hasta que se seleccionen los filtros
  if (!showSelectionScreen.value) {
    // El mapa ya viene con filtros aplicados
    initMap();

    // Iniciar carga progresiva si hay filtros aplicados
    if (props.filtrosAplicados && (props.filtrosAplicados.categoria || props.filtrosAplicados.operacion)) {
      const filtros = {
        categoria: props.filtrosAplicados.categoria,
        operacion: props.filtrosAplicados.operacion,
        precio_min: props.filtrosAplicados.precio_min,
        precio_max: props.filtrosAplicados.precio_max,
        habitaciones: props.filtrosAplicados.habitaciones,
        banos: props.filtrosAplicados.banos,
        ubicaciones: props.filtrosAplicados.ubicaciones
      };

      loadPropertiesProgressive(filtros);
    }
  }
});

onUnmounted(() => {
  removeFullScreenStyles();
  if (pulseInterval) clearInterval(pulseInterval);
  if (searchTimeout) clearTimeout(searchTimeout);
  if (successMessageTimeout) clearTimeout(successMessageTimeout);
  if (map) {
    if (markerClusterGroup) {
      map.removeLayer(markerClusterGroup);
    }
    if (searchLocationMarker) {
      map.removeLayer(searchLocationMarker);
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

      const pulsingIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `
          <div style="position: relative;">
            <div style="
              width: 20px;
              height: 20px;
              background: #233C7A;  /* Azul Alfa */
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

/* ================= LOCATION SEARCH FUNCTIONS ================= */
interface SearchResult {
  place_id: number;
  licence: string;
  osm_type: string;
  osm_id: number;
  boundingbox: number[];
  lat: string;
  lon: string;
  display_name: string;
  class: string;
  type: string;
  importance: number;
  icon: string;
}

// Buscar ubicación usando OpenStreetMap Nominatim
const searchLocation = async (query: string) => {
  if (!query || query.trim().length < 3) {
    searchResults.value = [];
    showSearchDropdown.value = false;
    return;
  }

  showSearchDropdown.value = true;

  // Cancelar búsqueda anterior si existe
  if (searchTimeout) clearTimeout(searchTimeout);

  isSearchingLocation.value = true;

  // Debounce: esperar 300ms después de que el usuario deje de escribir
  searchTimeout = window.setTimeout(async () => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=BO&limit=5&addressdetails=1`,
        {
          headers: {
            'Accept-Language': 'es',
            'User-Agent': 'AcmAnalytics/1.0'
          }
        }
      );

      if (!response.ok) throw new Error('Error en la búsqueda');

      const data: SearchResult[] = await response.json();
      searchResults.value = data;
    } catch (error) {
      console.error('Error buscando ubicación:', error);
      searchResults.value = [];
    } finally {
      isSearchingLocation.value = false;
    }
  }, 300);
};

// Ir a la ubicación seleccionada
const goToLocation = (lat: number, lng: number, name: string, result: SearchResult) => {
  if (!map) return;

  // Cerrar el dropdown
  showSearchDropdown.value = false;

  // Mover el mapa a la ubicación con animación
  map.setView([lat, lng], 16, {
    animate: true,
    duration: 1.5
  });

  // Eliminar marcador anterior si existe
  if (searchLocationMarker) {
    map.removeLayer(searchLocationMarker);
  }

  // Crear marcador personalizado para la ubicación buscada
  const searchIcon = L.divIcon({
    className: 'search-location-icon',
    html: `
      <div style="
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <div style="
          width: 40px;
          height: 40px;
          background: #FAB90E;
          border: 4px solid white;
          border-radius: 50%;
          box-shadow: 0 4px 12px rgba(250, 185, 14, 0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 20px;
        ">📍</div>
        <style>
          .search-location-icon::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(250, 185, 14, 0.3);
            border-radius: 50%;
            animation: pulse-search 2s infinite;
            z-index: -1;
          }
          @keyframes pulse-search {
            0% { transform: scale(1); opacity: 0.6; }
            70% { transform: scale(1.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0; }
          }
        </style>
      </div>
    `,
    iconSize: [40, 40],
    iconAnchor: [20, 40]
  });

  searchLocationMarker = L.marker([lat, lng], { icon: searchIcon }).addTo(map);

  // Crear contenido del popup con información detallada
  const popupContent = createSearchPopupContent(result);
  searchLocationMarker.bindPopup(popupContent).openPopup();

  // Cerrar popup automáticamente después de 3 segundos
  setTimeout(() => {
    if (searchLocationMarker) {
      searchLocationMarker.closePopup();
    }
  }, 3000);

  // Limpiar resultados de búsqueda
  searchResults.value = [];
  searchLocationInput.value = '';

  // Cerrar buscador después de seleccionar
  closeSearchAfterSelection();
};

// Crear contenido del popup de ubicación buscada
const createSearchPopupContent = (result: SearchResult): string => {
  // Extraer información relevante
  const addressParts = result.display_name.split(', ');
  const shortAddress = addressParts.slice(0, 3).join(', ');

  return `
    <div class="search-popup" style="min-width: 200px; max-width: 300px;">
      <div class="flex items-center gap-2 mb-2">
        <span style="font-size: 20px;">📍</span>
        <span style="font-weight: bold; color: #233C7A;">Ubicación buscada</span>
      </div>
      <div class="text-sm text-gray-700 mb-2">
        ${shortAddress}
      </div>
      <div class="text-xs text-gray-500">
        ${result.type ? `<span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">${result.type}</span>` : ''}
      </div>
      <style>
        .search-popup a {
          color: #233C7A !important;
          text-decoration: none;
        }
        .search-popup a:hover {
          text-decoration: underline;
        }
      </style>
    </div>
  `;
};

// Formatear nombre corto para mostrar en resultados
const formatResultName = (result: SearchResult): string => {
  const parts = result.display_name.split(', ');
  if (parts.length <= 2) return result.display_name;
  return parts.slice(0, 2).join(', ');
};

// Obtener el tipo de ubicación para mostrar ícono
const getLocationTypeIcon = (result: SearchResult): string => {
  if (result.class === 'highway') return '🛣️';
  if (result.class === 'building') return '🏢';
  if (result.class === 'amenity') return '🏪';
  if (result.class === 'shop') return '🏬';
  if (result.class === 'tourism') return '🏨';
  if (result.class === 'natural') return '🌳';
  if (result.class === 'leisure') return '🎯';
  if (result.type === 'residential') return '🏠';
  if (result.type === 'suburb') return '🏘️';
  if (result.type === 'city') return '🏙️';
  if (result.type === 'town') return '🏘️';
  if (result.type === 'village') return '🏡';
  return '📍';
};

// Limpiar búsqueda
const clearLocationSearch = () => {
  searchLocationInput.value = '';
  searchResults.value = [];
  showSearchDropdown.value = false;
  if (searchLocationMarker && map) {
    map.removeLayer(searchLocationMarker);
    searchLocationMarker = null;
  }
};

// Toggle expansión del buscador
const toggleSearchExpand = () => {
  isSearchExpanded.value = !isSearchExpanded.value;
  if (isSearchExpanded.value) {
    // Expandir
    setTimeout(() => {
      // Intentar enfocar el input de desktop o móvil
      const desktopInput = document.getElementById('location-search-input');
      const mobileInput = document.getElementById('location-search-input-mobile');
      if (desktopInput) desktopInput.focus();
      else if (mobileInput) mobileInput.focus();
    }, 100);
  } else {
    // Colapsar
    searchResults.value = [];
    showSearchDropdown.value = false;
  }
};

// Manejar blur del input (cerrar si no hay resultados seleccionados)
const handleSearchBlur = () => {
  // Esperar un poco para permitir que se haga clic en los resultados
  setTimeout(() => {
    if (!showSearchDropdown.value && !searchLocationInput.value) {
      isSearchExpanded.value = false;
    }
  }, 200);
};

// Cerrar buscador cuando se selecciona un resultado
const closeSearchAfterSelection = () => {
  setTimeout(() => {
    isSearchExpanded.value = false;
  }, 300);
};

// Cerrar dropdown cuando se hace click fuera
const handleOutsideClick = () => {
  if (showSearchDropdown.value) {
    showSearchDropdown.value = false;
  }
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
    color: '#233C7A',    /* Azul Alfa */
    fillColor: '#233C7A', /* Azul Alfa */
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
          background: #233C7A;  /* Azul Alfa */
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
    color: '#233C7A',  /* Azul Alfa */
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
  tempResults.value = [];

  // Obtener los productos ya filtrados por categoría y operación
  const productosFiltradosParaRadar = productosFiltrados.value;

  // Buscar propiedades dentro del radio del radar (solo de los productos filtrados)
  productosFiltradosParaRadar.forEach(p => {
    const pos = L.latLng(p.location.latitude, p.location.longitude);
    const distance = radarCenter.value!.distanceTo(pos);

    if (distance <= radarRadius.value) {
      tempResults.value.push(p);
    }
  });

  // Inicializar todas las propiedades como seleccionadas (todos los checkboxes marcados)
  selectedPropertyIds.value = new Set(tempResults.value.map(p => p.id));

  // Mostrar el modal previo de selección en lugar del panel lateral
  showSelectionModal.value = true;
  searchQuery.value = '';
};

/* ================= MODAL DE SELECCIÓN DE PROPIEDADES ================= */
// Toggle checkbox de una propiedad
const togglePropertySelection = (propertyId: number) => {
  if (selectedPropertyIds.value.has(propertyId)) {
    selectedPropertyIds.value.delete(propertyId);
  } else {
    selectedPropertyIds.value.add(propertyId);
  }
};

// Verificar si una propiedad está seleccionada
const isPropertySelected = (propertyId: number) => {
  return selectedPropertyIds.value.has(propertyId);
};

// Seleccionar todas las propiedades
const selectAllProperties = () => {
  selectedPropertyIds.value = new Set(tempResults.value.map(p => p.id));
};

// Deseleccionar todas las propiedades
const deselectAllProperties = () => {
  selectedPropertyIds.value.clear();
};

// Confirmar selección y calcular avalúo
const confirmSelection = () => {
  // Filtrar solo las propiedades seleccionadas
  results.value = tempResults.value.filter(p => selectedPropertyIds.value.has(p.id));

  // Cerrar modal de selección
  showSelectionModal.value = false;

  // Mostrar panel lateral con resultados seleccionados
  showPanel.value = true;

  // Actualizar los marcadores para mostrar solo los resultados seleccionados
  updateRadarMarkers();
};

// Cancelar selección
const cancelSelection = () => {
  showSelectionModal.value = false;
  tempResults.value = [];
  selectedPropertyIds.value.clear();
};

// Computed para contar propiedades seleccionadas
const selectedCount = computed(() => selectedPropertyIds.value.size);

/* ================= GENERAR PDF ================= */
const generarPDF = async () => {
  try {
    // Importar jsPDF dinámicamente
    const { default: jsPDF } = await import('jspdf');
    const { default: autoTable } = await import('jspdf-autotable');

    // Crear documento PDF (orientación vertical/portrait)
    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4'
    });

    // Colores de Alfa
    const colors = {
      azul: '#233C7A',
      amarillo: '#FAB90E',
      rojo: '#E0081D',
      gray: '#6B7280'
    };

    // ================= HEADER =================
    // Agregar logo de Alfa
    const logoUrl = '/logoalfa.png';
    try {
      doc.addImage(logoUrl, 'PNG', 15, 10, 25, 25);
    } catch (error) {
      console.warn('No se pudo cargar el logo:', error);
    }

    // Título principal
    doc.setFontSize(24);
    doc.setTextColor(parseInt(colors.azul.slice(1, 3), 16), parseInt(colors.azul.slice(3, 5), 16), parseInt(colors.azul.slice(5, 7), 16));
    doc.setFont('helvetica', 'bold');
    doc.text('Alfa Analytics - Reporte de Análisis', 45, 18);

    // Subtítulo
    doc.setFontSize(12);
    doc.setTextColor(parseInt(colors.gray.slice(1, 3), 16), parseInt(colors.gray.slice(3, 5), 16), parseInt(colors.gray.slice(5, 7), 16));
    doc.setFont('helvetica', 'normal');
    doc.text(`Análisis de propiedades en área de radar - Radio: ${radarRadius.value}m`, 45, 25);

    // Fecha y hora
    doc.setFontSize(10);
    const fecha = new Date().toLocaleDateString('es-BO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    doc.text(`Generado: ${fecha}`, 45, 30);

    // ================= INFORMACIÓN DE FILTROS =================
    let yPos = 45;

    // Línea separadora
    doc.setDrawColor(parseInt(colors.azul.slice(1, 3), 16), parseInt(colors.azul.slice(3, 5), 16), parseInt(colors.azul.slice(5, 7), 16));
    doc.setLineWidth(0.5);
    doc.line(15, yPos, 195, yPos);
    yPos += 10;

    // Filtros aplicados
    doc.setFontSize(14);
    doc.setTextColor(parseInt(colors.azul.slice(1, 3), 16), parseInt(colors.azul.slice(3, 5), 16), parseInt(colors.azul.slice(5, 7), 16));
    doc.setFont('helvetica', 'bold');
    doc.text('Filtros Aplicados:', 15, yPos);
    yPos += 7;

    doc.setFontSize(11);
    doc.setTextColor(parseInt(colors.gray.slice(1, 3), 16), parseInt(colors.gray.slice(3, 5), 16), parseInt(colors.gray.slice(5, 7), 16));
    doc.setFont('helvetica', 'normal');

    const operacionTexto = operacionSeleccionada.value
      ? operacionSeleccionada.value.charAt(0).toUpperCase() + operacionSeleccionada.value.slice(1)
      : 'Todas';
    doc.text(`• Operación: ${operacionTexto}`, 20, yPos);
    yPos += 6;
    doc.text(`• Categoría: ${nombreCategoriaSeleccionada.value || 'Todas'}`, 20, yPos);
    yPos += 6;
    doc.text(`• Propiedades encontradas: ${filteredResults.value.length}`, 20, yPos);
    yPos += 10;

    // ================= TABLA DE PROPIEDADES =================
    if (filteredResults.value.length > 0) {
      // Preparar datos para la tabla
      const tableData = filteredResults.value.map((p, index) => {
        // Precio USD
        const precioUSD = p.price_usd ? `$${p.price_usd.toLocaleString('es-BO')}` : 'N/A';
        // Precio BOB
        const precioBOB = p.price_bob ? `Bs. ${p.price_bob.toLocaleString('es-BO')}` : 'N/A';
        // Recortar nombre de propiedad si es muy largo
        const nombreCorto = p.name.length > 40 ? p.name.substring(0, 37) + '...' : p.name;

        return [
          index + 1,
          nombreCorto,
          p.superficie_util ? `${p.superficie_util} m²` : 'N/A',
          p.superficie_construida ? `${p.superficie_construida} m²` : 'N/A',
          precioUSD,
          precioBOB
        ];
      });

      // Generar tabla
      autoTable(doc, {
        startY: yPos,
        head: [['#', 'Propiedad', 'Sup. Útil', 'Sup. Const.', 'Precio USD', 'Precio BOB']],
        body: tableData,
        theme: 'grid',
        styles: {
          fontSize: 8,
          cellPadding: 2,
          font: 'helvetica'
        },
        headStyles: {
          fillColor: [35, 60, 122], // Azul Alfa (#233C7A)
          textColor: 255,
          fontStyle: 'bold',
          halign: 'center',
          fontSize: 9
        },
        alternateRowStyles: {
          fillColor: [245, 245, 245]
        },
        columnStyles: {
          0: { cellWidth: 10, halign: 'center', fontStyle: 'bold' }, // #
          1: { cellWidth: 50 }, // Propiedad
          2: { cellWidth: 25, halign: 'center' }, // Sup. Útil
          3: { cellWidth: 25, halign: 'center' }, // Sup. Const.
          4: { cellWidth: 30, halign: 'right', fontStyle: 'bold' }, // Precio USD
          5: { cellWidth: 30, halign: 'right', fontStyle: 'bold' }  // Precio BOB
        },
        margin: { top: 10, left: 15, right: 15, bottom: 10 }
      });

      yPos = (doc as any).lastAutoTable.finalY + 15;
    } else {
      doc.setFontSize(11);
      doc.setTextColor(parseInt(colors.gray.slice(1, 3), 16), parseInt(colors.gray.slice(3, 5), 16), parseInt(colors.gray.slice(5, 7), 16));
      doc.text('No se encontraron propiedades en el área del radar.', 15, yPos);
      yPos += 10;
    }

    // ================= ESTADÍSTICAS Y AVALÚO FINAL =================
    // Nueva página para estadísticas
    doc.addPage();

    // Título de estadísticas
    doc.setFontSize(20);
    doc.setTextColor(parseInt(colors.azul.slice(1, 3), 16), parseInt(colors.azul.slice(3, 5), 16), parseInt(colors.azul.slice(5, 7), 16));
    doc.setFont('helvetica', 'bold');
    doc.text('AVALÚO FINAL', 105, 20, { align: 'center' });

    yPos = 40;

    // Según la operación, mostrar diferentes estadísticas
    if (operacionSeleccionada.value === 'venta' || !operacionSeleccionada.value) {
      // ================= VENTA: Promedios por m² =================
      doc.setFontSize(16);
      doc.setTextColor(parseInt(colors.azul.slice(1, 3), 16), parseInt(colors.azul.slice(3, 5), 16), parseInt(colors.azul.slice(5, 7), 16));
      doc.setFont('helvetica', 'bold');
      doc.text('Precio Promedio por m²', 105, yPos, { align: 'center' });
      yPos += 15;

      // Superficie Útil
      if (averagePricePerSqm.value.util.usd || averagePricePerSqm.value.util.bob) {
        doc.setFillColor(35, 60, 122); // Azul Alfa
        doc.rect(15, yPos, 180, 35, 'F');
        yPos += 10;

        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.text('Superficie Útil', 105, yPos, { align: 'center' });
        yPos += 10;

        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        if (averagePricePerSqm.value.util.usd) {
          doc.text(`USD: $${averagePricePerSqm.value.util.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/m²`, 105, yPos, { align: 'center' });
          yPos += 7;
        }
        if (averagePricePerSqm.value.util.bob) {
          doc.text(`BOB: Bs. ${averagePricePerSqm.value.util.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/m²`, 105, yPos, { align: 'center' });
        }
        yPos += 15;
      }

      // Superficie Construida
      if (averagePricePerSqm.value.construida.usd || averagePricePerSqm.value.construida.bob) {
        doc.setFillColor(35, 60, 122); // Azul Alfa
        doc.rect(15, yPos, 180, 35, 'F');
        yPos += 10;

        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.text('Superficie Construida', 105, yPos, { align: 'center' });
        yPos += 10;

        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        if (averagePricePerSqm.value.construida.usd) {
          doc.text(`USD: $${averagePricePerSqm.value.construida.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/m²`, 105, yPos, { align: 'center' });
          yPos += 7;
        }
        if (averagePricePerSqm.value.construida.bob) {
          doc.text(`BOB: Bs. ${averagePricePerSqm.value.construida.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/m²`, 105, yPos, { align: 'center' });
        }
        yPos += 15;
      }
    } else if (operacionSeleccionada.value === 'alquiler' && averageAlquiler.value.count > 0) {
      // ================= ALQUILER: Promedios =================
      doc.setFontSize(16);
      doc.setTextColor(parseInt(colors.rojo.slice(1, 3), 16), parseInt(colors.rojo.slice(3, 5), 16), parseInt(colors.rojo.slice(5, 7), 16));
      doc.setFont('helvetica', 'bold');
      doc.text('Promedio de Alquiler', 105, yPos, { align: 'center' });
      yPos += 15;

      doc.setFillColor(224, 8, 29); // Rojo Alfa
      doc.rect(15, yPos, 180, 35, 'F');
      yPos += 10;

      doc.setFontSize(14);
      doc.setTextColor(255, 255, 255);
      doc.setFont('helvetica', 'bold');
      doc.text('Promedio en la Zona', 105, yPos, { align: 'center' });
      yPos += 10;

      doc.setFontSize(13);
      doc.setFont('helvetica', 'normal');
      if (averageAlquiler.value.usd) {
        doc.text(`USD: $${averageAlquiler.value.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/mes`, 105, yPos, { align: 'center' });
        yPos += 8;
      }
      if (averageAlquiler.value.bob) {
        doc.text(`BOB: Bs. ${averageAlquiler.value.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/mes`, 105, yPos, { align: 'center' });
      }
      yPos += 12;

      doc.setFontSize(11);
      doc.text(`Basado en ${averageAlquiler.value.count} propiedades en el área`, 105, yPos, { align: 'center' });
    } else if (operacionSeleccionada.value === 'anticretico' && averageAnticreticoMensual.value.count > 0) {
      // ================= ANTICRÉTICO: Equivalencia Mensual =================
      doc.setFontSize(16);
      doc.setTextColor(parseInt(colors.amarillo.slice(1, 3), 16), parseInt(colors.amarillo.slice(3, 5), 16), parseInt(colors.amarillo.slice(5, 7), 16));
      doc.setFont('helvetica', 'bold');
      doc.text('Equivalencia Mensual de Anticrético', 105, yPos, { align: 'center' });
      yPos += 15;

      doc.setFillColor(250, 185, 14); // Amarillo Alfa
      doc.rect(15, yPos, 180, 40, 'F');
      yPos += 10;

      doc.setFontSize(14);
      doc.setTextColor(35, 60, 122); // Texto azul sobre amarillo
      doc.setFont('helvetica', 'bold');
      doc.text('Equivalencia Mensual (30% anual)', 105, yPos, { align: 'center' });
      yPos += 12;

      doc.setFontSize(13);
      doc.setFont('helvetica', 'normal');
      if (averageAnticreticoMensual.value.usd) {
        doc.text(`USD: $${averageAnticreticoMensual.value.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/mes`, 105, yPos, { align: 'center' });
        yPos += 8;
      }
      if (averageAnticreticoMensual.value.bob) {
        doc.text(`BOB: Bs. ${averageAnticreticoMensual.value.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}/mes`, 105, yPos, { align: 'center' });
        yPos += 10;
      }

      doc.setFontSize(10);
      doc.text(`Basado en ${averageAnticreticoMensual.value.count} propiedades en el área`, 105, yPos, { align: 'center' });
    }

    // ================= FOOTER =================
    const pageCount = doc.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      doc.setFontSize(9);
      doc.setTextColor(parseInt(colors.gray.slice(1, 3), 16), parseInt(colors.gray.slice(3, 5), 16), parseInt(colors.gray.slice(5, 7), 16));
      doc.setFont('helvetica', 'normal');

      // Footer centrado para formato vertical (coordenada X = 105 es el centro)
      doc.text(
        `Página ${i} de ${pageCount} - Alfa Analytics - ${new Date().toLocaleDateString('es-BO')}`,
        105,
        285,
        { align: 'center' }
      );
    }

    // ================= DESCARGAR PDF =================
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    doc.save(`alfa-analytics-reporte-radar-${timestamp}.pdf`);

  } catch (error) {
    console.error('Error al generar PDF:', error);
    alert('Error al generar el PDF. Por favor, intente nuevamente.');
  }
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

    marker.bindPopup(getPopupContent(product));

    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });

  // Agregar marcadores de resultados del radar (más prominentes)
  results.value.forEach((product) => {
    const customIcon = L.divIcon({
      className: 'radar-result-marker',
      html: `
        <div style="
          background: #FAB90E;  /* Amarillo Alfa */
          border: 3px solid white;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          box-shadow: 0 4px 12px rgba(250, 185, 14, 0.4);
          animation: pulse-radar 2s infinite;
          z-index: 1000;
        ">🎯</div>
        <style>
          @keyframes pulse-radar {
            0% { transform: scale(1); box-shadow: 0 4px 12px rgba(250, 185, 14, 0.4); }
            50% { transform: scale(1.1); box-shadow: 0 6px 20px rgba(250, 185, 14, 0.6); }
            100% { transform: scale(1); box-shadow: 0 4px 12px rgba(250, 185, 14, 0.4); }
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
    const extraInfo = `📏 ${distance.toFixed(0)}m del centro del radar`;

    marker.bindPopup(getPopupContent(product, extraInfo));

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

  <div class="relative w-screen h-screen overflow-hidden" @click="showCategoryDropdown = false; showOperationDropdown = false; handleOutsideClick();">

    <!-- PANTALLA DE SELECCIÓN DE FILTROS -->
    <transition name="fade">
      <div v-if="showSelectionScreen" class="absolute inset-0 bg-gradient-to-br from-[#233C7A] via-[#1e325e] to-[#233C7A] z-[3000] flex items-center justify-center p-4">
        <div class="max-w-4xl w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] text-white p-6 sm:p-8">
            <div class="flex items-center justify-between mb-4">
              <h1 class="text-2xl sm:text-3xl font-bold flex items-center gap-3">
                <MapPin :size="32" />
                Mapa Interactivo de Propiedades
              </h1>
              <button
                @click="goToHome"
                class="bg-white/20 hover:bg-white/30 p-2 rounded-lg transition-colors"
                title="Volver a Inicio"
              >
                <ArrowLeft :size="24" />
              </button>
            </div>
            <p class="text-blue-100 text-sm sm:text-base">
              Selecciona qué tipo de propiedades deseas ver en el mapa para optimizar la carga y mejorar tu experiencia.
            </p>
            <p class="text-yellow-200 text-xs sm:text-sm mt-2 flex items-center gap-2">
              <span class="bg-yellow-400/20 px-2 py-1 rounded">⚠️ Importante</span>
              Debes seleccionar al menos una opción (categoría u operación) para continuar
            </p>
          </div>

          <!-- Contenido -->
          <div class="p-6 sm:p-8">
            <div class="grid md:grid-cols-2 gap-6 mb-8">
              <!-- Categorías -->
              <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                  <Building :size="20" class="text-[#233C7A]" />
                  Categoría <span class="text-sm font-normal text-gray-500">(opcional)</span>
                </h2>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <button
                    v-for="(nombre, id) in categoriasDisponibles"
                    :key="id"
                    @click="categoriaSeleccionada = parseInt(id as string)"
                    :class="[
                      'w-full text-left px-4 py-3 rounded-lg border-2 transition-all flex items-center justify-between',
                      categoriaSeleccionada === parseInt(id as string)
                        ? 'border-[#233C7A] bg-[#F5F5F5] dark:bg-[#233C7A]/20'
                        : 'border-gray-200 dark:border-gray-600 hover:border-[#233C7A] dark:hover:border-[#233C7A]'
                    ]"
                  >
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ nombre }}</span>
                    <Check v-if="categoriaSeleccionada === parseInt(id as string)" :size="20" class="text-[#233C7A]" />
                  </button>
                </div>
              </div>

              <!-- Operaciones -->
              <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                  <DollarSign :size="20" class="text-[#233C7A]" />
                  Tipo de Operación <span class="text-sm font-normal text-gray-500">(opcional)</span>
                </h2>
                <div class="space-y-2">
                  <button
                    v-for="op in operacionesDisponibles"
                    :key="op.value"
                    @click="operacionSeleccionada = op.value"
                    :class="[
                      'w-full text-left px-4 py-3 rounded-lg border-2 transition-all flex items-center justify-between',
                      operacionSeleccionada === op.value
                        ? 'border-[#E0081D] bg-[#F5F5F5] dark:bg-[#E0081D]/20'
                        : 'border-gray-200 dark:border-gray-600 hover:border-[#E0081D] dark:hover:border-[#E0081D]'
                    ]"
                  >
                    <div class="flex items-center gap-3">
                      <component :is="op.icon" :size="18" />
                      <span class="font-medium text-gray-700 dark:text-gray-200">{{ op.label }}</span>
                    </div>
                    <Check v-if="operacionSeleccionada === op.value" :size="20" class="text-[#E0081D]" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Resumen de selección -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
              <p class="text-sm text-gray-600 dark:text-gray-300 text-center">
                <span class="font-semibold">Selección actual:</span>
                {{ nombreCategoriaSeleccionada }} y {{ nombreOperacionSeleccionada }}
              </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-3">
              <button
                @click="goBack"
                class="flex-1 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="confirmSelectionAndLoadMap"
                class="flex-1 px-6 py-3 bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] text-white rounded-lg font-semibold hover:from-[#E0081D] hover:to-[#233C7A] transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02]"
              >
                🗺️ Ver Mapa
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Mapa -->
    <div ref="mapContainer" class="absolute inset-0 w-full h-full"></div>

    <!-- CONTROLES DESKTOP -->
    <div class="absolute top-5 left-12 right-10 z-[1000] hidden lg:flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3" @click.stop>
      <!-- Controles de navegación -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 flex-wrap">
        <!-- Inicio -->
        <button
          @click="goToHome"
          class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Inicio"
        >
          <Home :size="18" />
        </button>

        <!-- Volver
        <button
          @click="goBack"
          class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Volver"
        >
          <ArrowLeft :size="18" />
        </button> -->

        <!-- Cambiar filtros (panel lateral) -->
        <button
          @click="openFilterPanel"
          class="bg-[#FAB90E]/90 hover:bg-[#FAB90E] text-white px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
          title="Cambiar filtros"
        >
          <Filter :size="16" />
          <span class="hidden sm:inline text-sm font-medium">Cambiar Filtros</span>
        </button>

        <!-- BUSCADOR DE UBICACIÓN - Responsive Dropdown junto a filtros -->
        <div class="relative" @click.stop>
          <!-- Botón icono lupa (estado colapsado) -->
          <button
            v-if="!isSearchExpanded"
            @click="toggleSearchExpand"
            class="w-10 h-10 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg flex items-center justify-center hover:bg-white transition-all hover:scale-105 text-[#233C7A]"
            type="button"
            title="Buscar ubicación"
          >
            <Search :size="18" />
          </button>

          <!-- Input de búsqueda expandido - Absolute para no afectar el layout -->
          <transition name="search-expand-inline">
            <div
              v-if="isSearchExpanded"
              class="absolute left-0 top-0 z-[1500] min-w-0"
            >
              <div class="flex items-center">
                <Search
                  :size="16"
                  :class="isSearchingLocation ? 'animate-spin' : ''"
                  class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"
                />
                <input
                  id="location-search-input"
                  v-model="searchLocationInput"
                  @input="searchLocation(searchLocationInput)"
                  @focus="searchLocationInput && searchLocation(searchLocationInput)"
                  @blur="handleSearchBlur"
                  placeholder="Buscar dirección..."
                  type="text"
                  autocomplete="off"
                  class="w-56 pl-9 pr-8 py-2 rounded-lg shadow-lg border-0 focus:ring-2 focus:ring-[#233C7A] bg-white/95 backdrop-blur-sm text-sm"
                />
                <!-- Botón limpiar -->
                <button
                  v-if="searchLocationInput"
                  @click="clearLocationSearch"
                  class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors z-10"
                  type="button"
                >
                  <X :size="14" />
                </button>
                <!-- Botón cerrar -->
                <button
                  @click="toggleSearchExpand"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors z-10"
                  type="button"
                >
                  <X :size="14" />
                </button>
              </div>

              <!-- Dropdown de resultados -->
              <transition name="fade">
                <div
                  v-if="showSearchDropdown && searchResults.length > 0"
                  class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-100 z-[2001] w-56"
                >
                  <div class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] px-4 py-2">
                    <p class="text-xs text-white font-medium">
                      {{ searchResults.length }} resultado{{ searchResults.length > 1 ? 's' : '' }}
                    </p>
                  </div>

                  <div class="max-h-80 overflow-y-auto">
                    <button
                      v-for="result in searchResults"
                      :key="result.place_id"
                      @click.stop="goToLocation(parseFloat(result.lat), parseFloat(result.lon), result.display_name, result)"
                      type="button"
                      class="w-full text-left px-3 py-2.5 hover:bg-[#F5F5F5] transition-colors border-b border-gray-100 last:border-b-0 group"
                    >
                      <div class="flex items-start gap-2">
                        <span class="text-lg mt-0.5">{{ getLocationTypeIcon(result) }}</span>
                        <div class="flex-1 min-w-0">
                          <p class="text-xs font-semibold text-gray-800 group-hover:text-[#233C7A] truncate">
                            {{ formatResultName(result) }}
                          </p>
                          <p class="text-xs text-gray-500 truncate mt-0.5">
                            {{ result.type }}
                          </p>
                        </div>
                      </div>
                    </button>
                  </div>

                  <div class="bg-gray-50 px-4 py-2 text-xs text-gray-500 text-center">
                    Datos de OpenStreetMap
                  </div>
                </div>
              </transition>

              <!-- Estado de búsqueda -->
              <div
                v-if="showSearchDropdown && isSearchingLocation"
                class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 px-4 py-3 z-[2001] w-56"
              >
                <div class="flex items-center gap-3">
                  <div class="animate-spin">
                    <Search :size="16" class="text-[#233C7A]" />
                  </div>
                  <span class="text-xs text-gray-600">Buscando...</span>
                </div>
              </div>
            </div>
          </transition>
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
            isLocatingUser ? 'bg-[#233C7A]/60 cursor-not-allowed' : 'bg-[#233C7A]/90 hover:bg-[#233C7A] text-white'
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
          class="bg-[#FAB90E]/90 hover:bg-[#FAB90E] text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Activar radar"
        >
          <Radar :size="18" />
        </button>

        <button
          v-if="radarMode"
          @click="resetRadar"
          class="bg-[#E0081D]/90 hover:bg-[#E0081D] text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm animate-pulse"
          title="Quitar radar"
        >
          <X :size="18" />
        </button>

        <!-- Ver todo -->
        <button
          @click="resetView"
          class="bg-white/95 hover:bg-white text-[#212121] p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
          title="Ver todo"
        >
          <MapPin :size="18" />
        </button>
      </div>
    </div>

    <!-- CONTROLES MÓVIL SIMPLE - ELIMINADOS, ahora usan los mismos controles que desktop -->

    <!-- Dropdown filtros - ELIMINADO, ahora solo está el botón Cambiar Filtros -->

    <!-- Botón cambiar filtros (móvil) -->
    <div class="fixed bottom-20 left-4 z-[1200] lg:hidden flex flex-col gap-3">
      <!-- Inicio -->
      <button
        @click="goToHome"
        class="bg-white/95 hover:bg-white text-gray-700 p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Inicio"
      >
        <Home :size="18" />
      </button>

      <button
        @click="openFilterPanel"
        class="bg-[#FAB90E]/90 hover:bg-[#FAB90E] text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Cambiar filtros"
        aria-label="Cambiar filtros"
      >
        <Filter :size="18" />
      </button>

      <!-- BUSCADOR DE UBICACIÓN (móvil) -->
      <div class="relative" @click.stop>
        <!-- Botón icono lupa (estado colapsado) -->
        <button
          v-if="!isSearchExpanded"
          @click="toggleSearchExpand"
          class="bg-white/95 backdrop-blur-sm p-3 rounded-lg shadow-lg transition-all hover:scale-105 text-[#233C7A]"
          type="button"
          title="Buscar ubicación"
        >
          <Search :size="18" />
        </button>

        <!-- Input de búsqueda expandido (móvil) - Absolute para no afectar el layout -->
        <transition name="search-expand-inline">
          <div
            v-if="isSearchExpanded"
            class="absolute left-0 top-0 z-[1500] min-w-0"
          >
            <div class="flex items-center">
              <Search
                :size="16"
                :class="isSearchingLocation ? 'animate-spin' : ''"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"
              />
              <input
                id="location-search-input-mobile"
                v-model="searchLocationInput"
                @input="searchLocation(searchLocationInput)"
                @focus="searchLocationInput && searchLocation(searchLocationInput)"
                @blur="handleSearchBlur"
                placeholder="Buscar dirección..."
                type="text"
                autocomplete="off"
                class="w-52 pl-9 pr-8 py-2 rounded-lg shadow-lg border-0 focus:ring-2 focus:ring-[#233C7A] bg-white/95 backdrop-blur-sm text-sm"
              />
              <!-- Botón limpiar -->
              <button
                v-if="searchLocationInput"
                @click="clearLocationSearch"
                class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors z-10"
                type="button"
              >
                <X :size="14" />
              </button>
              <!-- Botón cerrar -->
              <button
                @click="toggleSearchExpand"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors z-10"
                type="button"
              >
                <X :size="14" />
              </button>
            </div>

            <!-- Dropdown de resultados (móvil) -->
            <transition name="fade">
              <div
                v-if="showSearchDropdown && searchResults.length > 0"
                class="absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-100 z-[2001] w-52"
              >
                <div class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] px-4 py-2">
                  <p class="text-xs text-white font-medium">
                    {{ searchResults.length }} resultado{{ searchResults.length > 1 ? 's' : '' }}
                  </p>
                </div>

                <div class="max-h-60 overflow-y-auto">
                  <button
                    v-for="result in searchResults"
                    :key="result.place_id"
                    @click.stop="goToLocation(parseFloat(result.lat), parseFloat(result.lon), result.display_name, result)"
                    type="button"
                    class="w-full text-left px-3 py-2.5 hover:bg-[#F5F5F5] transition-colors border-b border-gray-100 last:border-b-0 group"
                  >
                    <div class="flex items-start gap-2">
                      <span class="text-lg mt-0.5">{{ getLocationTypeIcon(result) }}</span>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-[#233C7A] truncate">
                          {{ formatResultName(result) }}
                        </p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                          {{ result.type }}
                        </p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </transition>
          </div>
        </transition>
      </div>
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
    <div class="fixed bottom-20 right-4 z-[1200] lg:hidden flex flex-col gap-3">
      <!-- Radar / Quitar Radar -->
      <button
        v-if="!radarMode"
        @click="activateRadarMode"
        class="bg-[#FAB90E]/90 hover:bg-[#FAB90E] text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Activar modo radar para buscar propiedades"
        aria-label="Activar modo radar"
      >
        <Radar :size="18" />
      </button>

      <button
        v-if="radarMode"
        @click="resetRadar"
        class="bg-[#E0081D]/90 hover:bg-[#E0081D] text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm animate-pulse"
        title="Desactivar modo radar"
        aria-label="Desactivar modo radar"
      >
        <X :size="18" />
      </button>

      <!-- Ver todo -->
      <button
        @click="resetView"
        class="bg-white/95 hover:bg-white text-[#212121] p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
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
          isLocatingUser ? 'bg-[#233C7A]/60 cursor-not-allowed' : 'bg-[#233C7A]/90 hover:bg-[#233C7A] text-white'
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
      class="absolute top-20 left-1/2 -translate-x-1/2 z-[1000] bg-[#FAB90E] text-white px-4 py-2 rounded-lg shadow-xl"
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
          <label class="font-semibold text-[#212121] text-sm">Radio de búsqueda</label>
          <span class="bg-[#233C7A] text-white px-3 py-1 rounded-full font-bold text-sm">
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
          class="w-full h-2 bg-[#F5F5F5] rounded-lg appearance-none cursor-pointer"
          :style="{
            background: `linear-gradient(to right, #233C7A 0%, #233C7A ${((radarRadius - 100) / (5000 - 100) * 100)}%, #F5F5F5 ${((radarRadius - 100) / (5000 - 100) * 100)}%, #F5F5F5 100%)`
          }"
        />

        <div class="flex justify-between text-xs text-gray-500">
          <span>100m</span>
          <span>2.5km</span>
          <span>5km</span>
        </div>

        <button
          @click="search"
          class="w-full bg-gradient-to-r from-[#FAB90E] to-[#E0081D] hover:from-[#E0081D] hover:to-[#FAB90E] text-white py-2 rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all"
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
        <div class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] text-white px-3 py-2 flex justify-between items-center flex-shrink-0">
          <div>
            <h3 class="font-bold text-xs sm:text-sm">📍 Propiedades</h3>
            <p class="text-[10px] sm:text-xs text-blue-100">{{ filteredResults.length }} resultados</p>
          </div>
          <button
            @click="showPanel = false"
            class="hover:bg-white/20 p-1.5 rounded-lg transition-colors"
          >
            <X :size="16" />
          </button>
        </div>

        <!-- Búsqueda Compacta -->
        <div class="px-2 py-1.5 border-b bg-gray-50 flex-shrink-0">
          <div class="relative">
            <Search class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" :size="14" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar..."
              class="w-full pl-7 pr-7 py-1 text-xs border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <X :size="14" />
            </button>
          </div>
        </div>

        <!-- Lista de resultados -->
        <div class="flex-1 overflow-y-auto px-2 sm:px-3 py-2 sm:py-3 space-y-2">
          <div
            v-for="p in filteredResults"
            :key="p.id"
            @click="focusProperty(p)"
            class="border border-gray-200 hover:border-blue-500 hover:bg-blue-50 cursor-pointer p-2 sm:p-3 rounded-lg transition-all transform hover:scale-[1.01] hover:shadow-sm relative"
          >
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-2 gap-2">
              <h4 class="font-bold text-gray-800 text-sm sm:text-base line-clamp-2 flex-1">{{ p.name }}</h4>

              <!-- PRECIO SEGÚN OPERACIÓN -->
              <div class="ml-0 sm:ml-2 flex-shrink-0">
                <!-- Si es VENTA: mostrar precio normal -->
                <div v-if="operacionSeleccionada === 'venta' || operacionSeleccionada === null" v-html="getResultPriceDisplay(p)"></div>

                <!-- Si es ALQUILER: mostrar precio + justificación -->
                <div v-else-if="operacionSeleccionada === 'alquiler'" class="space-y-1">
                  <!-- Precio del alquiler -->
                  <div v-html="getResultPriceDisplay(p)"></div>

                  <!-- 📊 JUSTIFICACIÓN DEL PRECIO -->
                  <div v-html="getAlquilerJustificacionDisplay(p)"></div>
                </div>

                <!-- Si es ANTICRÉTICO: mostrar precio + equivalencia mensual -->
                <div v-else-if="operacionSeleccionada === 'anticretico'" class="space-y-1">
                  <!-- Precio del anticrético -->
                  <div v-html="getResultPriceDisplay(p)"></div>

                  <!-- 📊 EQUIVALENCIA MENSUAL -->
                  <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-2 mt-1">
                    <p class="text-xs font-bold text-green-700 dark:text-green-300 mb-1">
                      📊 Eq. mensual (30% anual):
                    </p>
                    <div v-html="getAnticreticoMensualDisplay(p)"></div>
                  </div>
                </div>
              </div>
            </div>

            <p class="text-xs sm:text-sm text-gray-500 mb-2">{{ p.codigo_inmueble }}</p>

            <!-- Superficie -->
            <div class="bg-gray-50 rounded-lg p-2 sm:p-3 mb-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs sm:text-sm">
                <div>
                  <p class="text-blue-600 font-medium mb-1">📐 Útil</p>
                  <p class="font-bold text-blue-800 text-sm sm:text-base">
                    {{ p.superficie_util ? p.superficie_util.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-blue-700 text-xs" v-if="getPricePerSqmUtilDisplay(p)">
                    {{ getPricePerSqmUtilDisplay(p) }}
                  </p>
                </div>
                <div>
                  <p class="text-orange-600 font-medium mb-1">🏢 Constr.</p>
                  <p class="font-bold text-orange-800 text-sm sm:text-base">
                    {{ p.superficie_construida ? p.superficie_construida.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-orange-700 text-xs" v-if="getPricePerSqmConstruidaDisplay(p)">
                    {{ getPricePerSqmConstruidaDisplay(p) }}
                  </p>
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs sm:text-sm gap-1">
              <span v-if="p.category" class="bg-gray-100 px-2 sm:px-3 py-1 rounded text-gray-700 inline-block w-fit">
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
          v-if="averagePricePerSqm.util.usd || averagePricePerSqm.util.bob || averagePricePerSqm.construida.usd || averagePricePerSqm.construida.bob || averageAnticreticoMensual.count > 0 || averageAlquiler.count > 0"
          class="border-t bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 sm:p-3 flex-shrink-0"
        >
          <!-- ================= VENTA: Promedios por m² ================= -->
          <div v-if="operacionSeleccionada === 'venta' || operacionSeleccionada === null">
            <p class="text-center text-sm sm:text-xs font-bold text-blue-100 mb-2 sm:mb-1">📊 Precio promedio en la zona</p>
            <p class="text-center text-[10px] sm:text-xs text-blue-200 mb-3 sm:mb-2">Cálculo considera superficie útil o construida según disponibilidad</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
              <!-- Promedio Útil -->
              <div
                v-if="averagePricePerSqm.util.usd || averagePricePerSqm.util.bob"
                class="bg-white/10 rounded-lg p-4 sm:p-3 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-colors"
              >
                <div class="flex items-center justify-center gap-2 mb-2">
                  <span class="text-lg sm:text-base">📐</span>
                  <p class="text-xs sm:text-xs text-blue-100 font-medium">Superficie Útil</p>
                </div>
                <div class="space-y-1 mb-2">
                  <p v-if="averagePricePerSqm.util.usd" class="text-xl sm:text-base font-extrabold text-white leading-tight">
                    $US {{ averagePricePerSqm.util.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}/m²
                  </p>
                  <p v-if="averagePricePerSqm.util.bob" class="text-lg sm:text-sm font-bold text-blue-100 leading-tight">
                    Bs. {{ averagePricePerSqm.util.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}/m²
                  </p>
                </div>
                <div class="flex items-center justify-center gap-1 text-xs text-blue-100">
                  <span class="bg-white/20 px-2 py-0.5 rounded-full">
                    {{ filteredResults.filter(p => p.superficie_util && p.superficie_util > 0).length }} propiedades
                  </span>
                </div>
              </div>

              <!-- Promedio Construida -->
              <div
                v-if="averagePricePerSqm.construida.usd || averagePricePerSqm.construida.bob"
                class="bg-white/10 rounded-lg p-4 sm:p-3 backdrop-blur-sm border border-white/20 hover:bg-white/15 transition-colors"
              >
                <div class="flex items-center justify-center gap-2 mb-2">
                  <span class="text-lg sm:text-base">🏢</span>
                  <p class="text-xs sm:text-xs text-blue-100 font-medium">Superficie Construida</p>
                </div>
                <div class="space-y-1 mb-2">
                  <p v-if="averagePricePerSqm.construida.usd" class="text-xl sm:text-base font-extrabold text-white leading-tight">
                    $US {{ averagePricePerSqm.construida.usd.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}/m²
                  </p>
                  <p v-if="averagePricePerSqm.construida.bob" class="text-lg sm:text-sm font-bold text-blue-100 leading-tight">
                    Bs. {{ averagePricePerSqm.construida.bob.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}/m²
                  </p>
                </div>
                <div class="flex items-center justify-center gap-1 text-xs text-blue-100">
                  <span class="bg-white/20 px-2 py-0.5 rounded-full">
                    {{ filteredResults.filter(p => p.superficie_construida && p.superficie_construida > 0).length }} propiedades
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ================= ALQUILER: Análisis de Zona (COMPACTO) ================= -->
          <div v-else-if="operacionSeleccionada === 'alquiler'">
            <div v-if="averageAlquiler.count > 0">
              <!-- Layout de 2 columnas compacto -->
              <div class="grid grid-cols-2 gap-2">

                <!-- COLUMNA IZQUIERDA: Promedio -->
                <div class="bg-white/10 rounded-lg p-2 backdrop-blur-sm border border-white/20">
                  <p class="text-xs font-bold text-blue-100 text-center mb-2">💰 Promedio</p>

                  <!-- Promedio USD -->
                  <div v-if="averageAlquiler.usd" class="text-center mb-2">
                    <p class="text-xl font-extrabold text-white leading-tight">
                      ${{ averageAlquiler.usd.toLocaleString('es-BO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }) }}
                    </p>
                    <p class="text-xs text-blue-100">USD/mes</p>
                  </div>

                  <!-- Promedio BOB -->
                  <div v-if="averageAlquiler.bob" class="text-center">
                    <p class="text-lg font-bold text-white leading-tight">
                      {{ averageAlquiler.bob.toLocaleString('es-BO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }) }}
                    </p>
                    <p class="text-xs text-blue-100">Bs./mes</p>
                  </div>

                  <p class="text-xs text-blue-200 text-center mt-2">
                    {{ averageAlquiler.count }} props.
                  </p>
                </div>

                <!-- COLUMNA DERECHA: Distribución -->
                <div class="bg-white/5 rounded-lg p-2 border border-white/10">
                  <p class="text-xs font-bold text-blue-100 text-center mb-2">🏠 Distribución</p>

                  <div class="grid grid-cols-2 gap-1 text-xs">
                    <div class="text-center">
                      <p class="text-lg font-extrabold text-white">
                        {{ filteredResults.filter(p => p.habitaciones === 1).length }}
                      </p>
                      <p class="text-blue-200">1 hab</p>
                    </div>
                    <div class="text-center">
                      <p class="text-lg font-extrabold text-white">
                        {{ filteredResults.filter(p => p.habitaciones === 2).length }}
                      </p>
                      <p class="text-blue-200">2 hab</p>
                    </div>
                    <div class="text-center">
                      <p class="text-lg font-extrabold text-white">
                        {{ filteredResults.filter(p => p.habitaciones === 3).length }}
                      </p>
                      <p class="text-blue-200">3 hab</p>
                    </div>
                    <div class="text-center">
                      <p class="text-lg font-extrabold text-white">
                        {{ filteredResults.filter(p => p.habitaciones && p.habitaciones >= 4).length }}
                      </p>
                      <p class="text-blue-200">4+ hab</p>
                    </div>
                  </div>
                </div>

                <!-- BLOQUE INFERIOR: Rangos + Tip (spanning 2 columnas) -->
                <div class="col-span-2 bg-white/5 rounded-lg p-2 border border-white/10">
                  <div class="flex items-center justify-between gap-2">
                    <!-- Rangos compactos -->
                    <div class="flex-1">
                      <p class="text-xs font-bold text-blue-100 mb-1">📈 Rangos:</p>
                      <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs">
                        <span class="text-blue-200">1hab: $400-700</span>
                        <span class="text-blue-200">2hab: $600-950</span>
                        <span class="text-blue-200">3hab: $800-1.3k</span>
                        <span class="text-blue-200">4+: $1k-1.6k</span>
                      </div>
                    </div>

                    <!-- Separador vertical -->
                    <div class="w-px bg-white/20"></div>

                    <!-- Tip compacto -->
                    <div class="flex-1">
                      <p class="text-xs text-blue-100 text-center">
                        💡 Revisa el <strong class="text-yellow-300">desglose</strong> de cada propiedad
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else class="text-center py-2">
              <p class="text-xs text-blue-100">
                🔍 Usa el radar para ver análisis
              </p>
            </div>
          </div>

          <!-- ================= ANTICRÉTICO: EQUIVALENCIA MENSUAL ================= -->
          <div v-else-if="operacionSeleccionada === 'anticretico'">
            <p class="text-center text-sm sm:text-xs font-bold text-blue-100 mb-3">
              📊 Equivalencia Mensual del Anticrético
            </p>

            <p class="text-center text-xs text-blue-200 mb-3">
              Basado en tasa del 30% anual del mercado boliviano
            </p>

            <!-- PROMEDIO DE EQUIVALENCIA MENSUAL EN LA ZONA DEL RADAR -->
            <div v-if="averageAnticreticoMensual.count > 0" class="space-y-3">

              <!-- Tarjeta de equivalencia mensual promedio -->
              <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm border border-white/20">
                <div class="flex items-center justify-center gap-2 mb-3">
                  <span class="text-2xl">💰</span>
                  <p class="text-sm font-bold text-blue-100">Promedio mensual en el área</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Equivalencia USD -->
                  <div v-if="averageAnticreticoMensual.usd" class="text-center">
                    <p class="text-3xl font-extrabold text-white leading-tight">
                      ${{ averageAnticreticoMensual.usd.toLocaleString('es-BO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }) }}
                    </p>
                    <p class="text-xs text-blue-100 mt-1">USD/mes</p>
                    <p class="text-xs text-blue-200 mt-2">
                      Equivale a un alquiler de esta zona
                    </p>
                  </div>

                  <!-- Equivalencia BOB -->
                  <div v-if="averageAnticreticoMensual.bob" class="text-center">
                    <p class="text-3xl font-extrabold text-white leading-tight">
                      Bs. {{ averageAnticreticoMensual.bob.toLocaleString('es-BO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }) }}
                    </p>
                    <p class="text-xs text-blue-100 mt-1">BOB/mes</p>
                    <p class="text-xs text-blue-200 mt-2">
                      {{ averageAnticreticoMensual.count }} propiedades en el área
                    </p>
                  </div>
                </div>
              </div>

              <!-- Explicación del cálculo -->
              <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                <p class="text-xs text-blue-100 text-center">
                  💡 <strong>Cómo se calcula:</strong> Anticrético × 30% ÷ 12 = Alquiler mensual equivalente
                </p>
              </div>

              <!-- Rango de tasas (información adicional) -->
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-white/5 rounded-lg p-2">
                  <p class="text-xs text-blue-200">Mínimo (25%)</p>
                  <p v-if="averageAnticreticoMensual.usd" class="text-sm font-bold text-white">
                    ${{ ((averageAnticreticoMensual.usd / 0.30) * 0.25).toFixed(0) }}/mes
                  </p>
                </div>
                <div class="bg-white/10 rounded-lg p-2 border border-white/30">
                  <p class="text-xs text-blue-200">Promedio (30%)</p>
                  <p v-if="averageAnticreticoMensual.usd" class="text-base font-extrabold text-yellow-300">
                    ${{ averageAnticreticoMensual.usd.toFixed(0) }}/mes
                  </p>
                </div>
                <div class="bg-white/5 rounded-lg p-2">
                  <p class="text-xs text-blue-200">Máximo (35%)</p>
                  <p v-if="averageAnticreticoMensual.usd" class="text-sm font-bold text-white">
                    ${{ ((averageAnticreticoMensual.usd / 0.30) * 0.35).toFixed(0) }}/mes
                  </p>
                </div>
              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else class="text-center py-4">
              <p class="text-sm text-blue-100">
                🔍 Usa el radar para ver equivalencias de anticréticos en el área
              </p>
            </div>
          </div>

          <!-- ================= BOTONES DE ACCIÓN ================= -->
          <div class="border-t border-white/20 p-3 bg-white/5">
            <!-- Botón de descarga de PDF -->
            <div class="grid grid-cols-1 gap-2">
              <button
                @click="generarPDF"
                class="w-full bg-white hover:bg-gray-100 text-[#233C7A] font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 group"
                title="Descargar reporte en PDF"
              >
                <FileText :size="20" class="group-hover:scale-110 transition-transform" />
                <span class="text-sm">Descargar Reporte PDF</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- PANEL LATERAL DE FILTROS -->
    <transition name="slide-left">
      <div
        v-if="showFilterPanel"
        class="absolute left-0 top-0 h-full w-full sm:w-96 bg-white shadow-2xl z-[1300] flex flex-col"
      >
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#FAB90E] to-[#E0081D] text-white p-4 flex justify-between items-center flex-shrink-0">
          <div>
            <h3 class="font-bold text-lg">Cambiar Filtros</h3>
            <p class="text-xs text-white/80">Selecciona las nuevas opciones</p>
          </div>
          <button
            @click="showFilterPanel = false"
            class="hover:bg-white/20 p-2 rounded-lg transition-colors"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Contenido -->
        <div class="flex-1 overflow-y-auto p-4 space-y-6">
          <!-- Categorías -->
          <div>
            <h2 class="text-base font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
              <Building :size="18" class="text-blue-600" />
              Categoría <span class="text-xs font-normal text-gray-500">(opcional)</span>
            </h2>
            <div class="space-y-2">
              <button
                v-for="(nombre, id) in categoriasDisponibles"
                :key="id"
                @click="selectCategoria(parseInt(id as string), false)"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg border-2 transition-all flex items-center justify-between text-sm',
                  categoriaSeleccionada === parseInt(id as string)
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                    : 'border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500'
                ]"
              >
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ nombre }}</span>
                <Check v-if="categoriaSeleccionada === parseInt(id as string)" :size="16" class="text-blue-600" />
              </button>
            </div>
          </div>

          <!-- Operaciones -->
          <div>
            <h2 class="text-base font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
              <DollarSign :size="18" class="text-green-600" />
              Tipo de Operación <span class="text-xs font-normal text-gray-500">(opcional)</span>
            </h2>
            <div class="space-y-2">
              <button
                v-for="op in operacionesDisponibles"
                :key="op.value"
                @click="selectOperacion(op.value, false)"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg border-2 transition-all flex items-center justify-between text-sm',
                  operacionSeleccionada === op.value
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                    : 'border-gray-200 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-500'
                ]"
              >
                <div class="flex items-center gap-2">
                  <component :is="op.icon" :size="16" />
                  <span class="font-medium text-gray-700 dark:text-gray-200">{{ op.label }}</span>
                </div>
                <Check v-if="operacionSeleccionada === op.value" :size="16" class="text-green-600" />
              </button>
            </div>
          </div>

          <!-- Información -->
          <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3">
            <p class="text-xs text-purple-700 dark:text-purple-300">
              💡 <strong>Tip:</strong> Debes seleccionar al menos una opción (categoría u operación) para aplicar los filtros.
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t bg-gray-50 dark:bg-gray-800 p-4 flex-shrink-0">
          <div class="space-y-3">
            <!-- Resumen -->
            <div class="text-center">
              <p class="text-xs text-gray-600 dark:text-gray-400">
                <span class="font-semibold">Selección:</span> {{ nombreCategoriaSeleccionada }} y {{ nombreOperacionSeleccionada }}
              </p>
            </div>

            <!-- Botones -->
            <div class="flex gap-2">
              <button
                @click="showFilterPanel = false"
                class="flex-1 px-4 py-2 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="applyFiltersFromPanel"
                :class="[
                  'flex-1 px-4 py-2 rounded-lg font-semibold text-sm transition-all',
                  canLoadMap
                    ? 'bg-gradient-to-r from-[#FAB90E] to-[#E0081D] text-white hover:from-[#E0081D] hover:to-[#FAB90E]'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
                :disabled="!canLoadMap"
              >
                Aplicar Filtros
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL PREVIO DE SELECCIÓN DE PROPIEDADES -->
    <transition name="fade">
      <div v-if="showSelectionModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-2 sm:p-4">
        <div class="absolute inset-0 bg-black bg-opacity-60" @click="cancelSelection"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col mx-0 sm:mx-4">
          <!-- Header -->
          <div class="bg-gradient-to-r from-[#233C7A] to-[#1e325e] text-white px-3 sm:px-6 py-3 sm:py-5 flex-shrink-0">
            <div class="flex items-center justify-between gap-2">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg sm:text-2xl font-bold flex items-center gap-2 sm:gap-3">
                  <Radar :size="20" class="sm:hidden" />
                  <Radar :size="28" class="hidden sm:block" />
                  <span class="truncate">Seleccionar Propiedades</span>
                </h3>
                <p class="text-xs sm:text-sm text-blue-100 mt-1 sm:mt-2">
                  {{ tempResults.length }} encontradas • {{ selectedCount }} seleccionadas
                </p>
              </div>
              <button
                @click="cancelSelection"
                class="hover:bg-white/20 p-1.5 sm:p-2 rounded-lg transition-colors flex-shrink-0"
                title="Cancelar"
              >
                <X :size="20" class="sm:hidden" />
                <X :size="24" class="hidden sm:block" />
              </button>
            </div>
          </div>

          <!-- Contenido scrollable -->
          <div class="flex-1 overflow-y-auto p-3 sm:p-6">
            <!-- Explicación -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
              <div class="flex items-start gap-2 sm:gap-3">
                <div class="text-blue-600 mt-0.5 flex-shrink-0">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs sm:text-sm text-blue-800 font-medium mb-0.5 sm:mb-1">Selecciona las propiedades para el avalúo</p>
                  <p class="text-[10px] sm:text-xs text-blue-700">
                    Desmarca las que quieras excluir (precios incorrectos, muy caras, datos erróneos)
                  </p>
                </div>
              </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="flex flex-col sm:flex-row gap-2 mb-3 sm:mb-4">
              <button
                @click="selectAllProperties"
                class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-medium transition-colors flex items-center justify-center gap-1.5 sm:gap-2"
              >
                <Check :size="14" class="sm:hidden" />
                <Check :size="16" class="hidden sm:block" />
                <span class="hidden sm:inline">Seleccionar Todas ({{ tempResults.length }})</span>
                <span class="sm:hidden">Todas ({{ tempResults.length }})</span>
              </button>
              <button
                @click="deselectAllProperties"
                class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-medium transition-colors flex items-center justify-center gap-1.5 sm:gap-2"
              >
                <X :size="14" class="sm:hidden" />
                <X :size="16" class="hidden sm:block" />
                <span class="hidden sm:inline">Deseleccionar Todas</span>
                <span class="sm:hidden">Ninguna</span>
              </button>
            </div>

            <!-- Tabla de propiedades -->
            <div class="border rounded-lg overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 w-8 whitespace-nowrap">✓</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700">Propiedad</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 hidden sm:table-cell">Sup. Útil</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 hidden sm:table-cell">Sup. Const.</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700">Precio USD</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 hidden sm:table-cell">BOB</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 hidden sm:table-cell">m² Util</th>
                    <th class="px-1.5 sm:px-2 py-1.5 sm:py-2 text-left text-[9px] sm:text-[10px] font-semibold text-gray-700 hidden sm:table-cell">m² Const</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr
                    v-for="property in tempResults"
                    :key="property.id"
                    :class="[
                      'hover:bg-blue-50 transition-colors',
                      !isPropertySelected(property.id) ? 'bg-red-50 hover:bg-red-100' : ''
                    ]"
                  >
                    <!-- Checkbox -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2">
                      <input
                        type="checkbox"
                        :id="`property-${property.id}`"
                        :checked="isPropertySelected(property.id)"
                        @change="togglePropertySelection(property.id)"
                        class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                      />
                    </td>

                    <!-- Nombre -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div class="font-medium text-gray-900 text-[10px] sm:text-[11px] leading-tight">{{ property.name }}</div>
                        <div class="text-[9px] sm:text-[10px] text-gray-500 truncate">{{ property.codigo_inmueble }}</div>
                      </label>
                    </td>

                    <!-- Superficie Útil -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2 hidden sm:table-cell">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.superficie_util" class="text-[10px] sm:text-xs text-gray-700">
                          {{ property.superficie_util.toLocaleString('es-BO') }} m²
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>

                    <!-- Superficie Construida -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2 hidden sm:table-cell">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.superficie_construida" class="text-[10px] sm:text-xs text-gray-700">
                          {{ property.superficie_construida.toLocaleString('es-BO') }} m²
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>

                    <!-- Precio USD -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.price_usd" class="text-[10px] sm:text-[11px] font-semibold text-gray-900">
                          {{ formatPrice(property.price_usd, '$') }}
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>

                    <!-- Precio BOB -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2 hidden sm:table-cell">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.price_bob" class="text-[10px] sm:text-xs font-semibold text-[#E0081D]">
                          {{ formatPrice(property.price_bob, 'Bs.') }}
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>

                    <!-- Precio m² Util -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2 hidden sm:table-cell">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.price_usd && property.superficie_util && property.superficie_util > 0" class="text-[10px] sm:text-xs font-bold text-blue-600">
                          {{ formatPrice(property.price_usd / property.superficie_util, '$') }}/m²
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>

                    <!-- Precio m² Const -->
                    <td class="px-1.5 sm:px-2 py-1.5 sm:py-2 hidden sm:table-cell">
                      <label :for="`property-${property.id}`" class="cursor-pointer block">
                        <div v-if="property.price_usd && property.superficie_construida && property.superficie_construida > 0" class="text-[10px] sm:text-xs font-bold text-green-600">
                          {{ formatPrice(property.price_usd / property.superficie_construida, '$') }}/m²
                        </div>
                        <div v-else class="text-[9px] sm:text-[10px] text-gray-400">N/A</div>
                      </label>
                    </td>
                  </tr>

                  <!-- Sin propiedades -->
                  <tr v-if="tempResults.length === 0">
                    <td colspan="8" class="px-1.5 sm:px-2 py-6 sm:py-8 text-center text-gray-500">
                      <div class="text-2xl sm:text-3xl mb-1">🔍</div>
                      <p class="font-medium text-xs sm:text-sm">No se encontraron propiedades</p>
                      <p class="text-[10px] sm:text-xs mt-0.5">Intenta mover el radar o cambiar el radio</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Resumen de selección -->
            <div v-if="tempResults.length > 0" class="mt-3 sm:mt-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs sm:text-sm">
                <span class="text-gray-700 text-center sm:text-left">
                  <span class="font-semibold">{{ selectedCount }}</span> de
                  <span class="font-semibold">{{ tempResults.length }}</span> seleccionadas
                </span>
                <span v-if="selectedCount === 0" class="text-red-600 font-medium text-center">
                  ⚠️ Selecciona al menos 1
                </span>
                <span v-else-if="selectedCount < tempResults.length" class="text-orange-600 font-medium text-center">
                  {{ tempResults.length - selectedCount }} excluidas
                </span>
                <span v-else class="text-green-600 font-medium text-center">
                  ✓ Todas incluidas
                </span>
              </div>
            </div>
          </div>

          <!-- Footer con botones -->
          <div class="border-t bg-gray-50 px-3 sm:px-6 py-3 sm:py-4 flex-shrink-0">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
              <button
                @click="cancelSelection"
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg transition-colors flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base"
              >
                <X :size="18" class="sm:hidden" />
                <X :size="20" class="hidden sm:block" />
                <span>Cancelar</span>
              </button>
              <button
                @click="confirmSelection"
                :disabled="selectedCount === 0"
                class="flex-1 bg-gradient-to-r from-[#FAB90E] to-[#E0081D] hover:from-[#E0081D] hover:to-[#FAB90E] text-white font-semibold py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base"
              >
                <Check :size="18" class="sm:hidden" />
                <Check :size="20" class="hidden sm:block" />
                <span>Calcular Avalúo</span>
                <span v-if="selectedCount > 0" class="bg-white/20 px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] sm:text-xs">
                  {{ selectedCount }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Indicador de Carga Progresiva -->
    <transition name="fade-slide">
      <div v-if="loadingState.isLoading && !loadingState.isComplete"
           class="fixed bottom-6 right-6 z-[2000] bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden min-w-[320px] max-w-[400px]">
        <!-- Header con gradiente -->
        <div class="bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] px-4 py-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <span class="text-white font-semibold text-sm">Cargando propiedades...</span>
            </div>
            <button
              @click="cancelProgressiveLoading"
              class="text-white/80 hover:text-white transition-colors text-xs font-medium underline"
            >
              Cancelar
            </button>
          </div>
        </div>

        <!-- Body con progreso -->
        <div class="p-4 space-y-3">
          <!-- Progress bar -->
          <div class="relative">
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-[#FAB90E] to-[#E0081D] transition-all duration-300 ease-out rounded-full"
                :style="{ width: loadingState.progress + '%' }"
              ></div>
            </div>
          </div>

          <!-- Info de progreso -->
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">
              <span class="font-semibold text-[#233C7A]">{{ loadingState.loaded }}</span>
              <span v-if="loadingState.total > 0">
                de {{ loadingState.total.toLocaleString() }}
              </span>
              propiedades
            </span>
            <span v-if="loadingState.total > 0" class="font-semibold text-[#E0081D]">
              {{ loadingState.progress }}%
            </span>
          </div>

          <!-- Mensaje informativo -->
          <p class="text-xs text-gray-500 text-center">
            Cargando en grupos de 500 propiedades para mayor velocidad 🚀
          </p>
        </div>
      </div>
    </transition>

    <!-- Mensaje de carga completada -->
    <transition name="fade-slide">
      <div v-if="loadingState.isComplete && !loadingState.isLoading"
           class="fixed bottom-6 right-6 z-[2000] bg-green-50 border border-green-200 rounded-xl shadow-2xl px-6 py-4 flex items-center gap-3">
        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div>
          <p class="font-semibold text-green-800 text-sm">
            ¡Carga completa! 🎉
          </p>
          <p class="text-green-600 text-xs">
            {{ loadingState.loaded.toLocaleString() }} propiedades cargadas
          </p>
        </div>
        <button
          @click="loadingState.isComplete = false"
          class="ml-2 text-green-600 hover:text-green-800 transition-colors"
          title="Cerrar"
        >
          <X :size="18" />
        </button>
      </div>
    </transition>

    <!-- Modal de Detalles de Propiedad -->
    <PropertyDetailsModal
      v-if="selectedProperty"
      :property="selectedProperty"
      :show="showPropertyModal"
      @close="closePropertyModal"
    />
  </div>
</template>

<style>
/* Animación de fade para la pantalla de selección */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Animación de fade-slide para el indicador de carga */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px) translateX(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px) translateX(20px);
}

.fade-slide-leave-active {
  transition-duration: 0.3s;
}

/* Animación de fade-down para el buscador */
.fade-down-enter-active,
.fade-down-leave-active {
  transition: all 0.3s ease;
}

.fade-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Animación del panel lateral derecho (resultados radar) */
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

/* Animación del panel lateral izquierdo (filtros) */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(-100%);
}

.slide-left-leave-to {
  transform: translateX(-100%);
}

/* Animación de expansión del buscador */
.search-expand-enter-active,
.search-expand-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-expand-enter-from {
  opacity: 0;
  width: 0;
  transform: scaleX(0);
}

.search-expand-leave-to {
  opacity: 0;
  width: 0;
  transform: scaleX(0);
}

/* Animación de fade para el icono del buscador */
.search-icon-fade-enter-active,
.search-icon-fade-leave-active {
  transition: opacity 0.2s ease;
}

.search-icon-fade-enter-from,
.search-icon-fade-leave-to {
  opacity: 0;
}

/* Animación de expansión del buscador en línea (sin afectar layout) */
.search-expand-inline-enter-active,
.search-expand-inline-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  transform-origin: left center;
}

.search-expand-inline-enter-from {
  opacity: 0;
  transform: scaleX(0) translateX(-10px);
}

.search-expand-inline-leave-to {
  opacity: 0;
  transform: scaleX(0) translateX(-10px);
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
  background: #233C7A;  /* Azul Alfa */
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #233C7A;  /* Azul Alfa */
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 2px solid white;
}
</style>
