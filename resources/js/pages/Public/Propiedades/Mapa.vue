<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import { ArrowLeft, Navigation, X, Search, Radar, MapPin, Home, Filter, ChevronDown, DollarSign, Building, Key, FileText, Check } from 'lucide-vue-next';
import PropertyDetailsModal from '@/components/public/PropertyDetailsModal.vue';

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

const defaultCenter = props.defaultCenter || { lat: -17.38, lng: -66.16 };

/* ================= MAP ================= */
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map;
const markers: L.Marker[] = [];
let markerClusterGroup: L.MarkerClusterGroup | null = null;

/* ================= USER LOCATION ================= */
let userLocationMarker: L.Marker | null = null;
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

// Verificar si hay filtros aplicados para decidir si mostrar pantalla de selección
const tieneFiltrosAplicados = props.filtrosAplicados && (
  props.filtrosAplicados.categoria !== null ||
  props.filtrosAplicados.operacion !== null
);
const showSelectionScreen = ref(!tieneFiltrosAplicados); // No mostrar si hay filtros
const showFilterPanel = ref(false); // Mostrar panel lateral de filtros en el mapa

// Modal state
const selectedProperty = ref<Product | null>(null);
const showPropertyModal = ref(false);

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
  { value: 'venta', label: 'Venta', icon: DollarSign },
  { value: 'alquiler', label: 'Alquiler', icon: Key },
  { value: 'anticretico', label: 'Anticrético', icon: FileText },
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
  // Si no hay filtros seleccionados, no mostrar nada para evitar cargar todos los datos
  if (!categoriaSeleccionada.value && !operacionSeleccionada.value) {
    return [];
  }

  return props.productsConUbicacion.filter(product => {
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
    prices.push(`<span class="text-green-600 font-bold">${formatPrice(product.price_usd, '$')} USD</span>`);
  }
  if (product.price_bob) {
    prices.push(`<span class="text-blue-600 font-bold">${formatPrice(product.price_bob, 'Bs.')} BOB</span>`);
  }
  return prices.join('<br>') || '<span class="text-gray-400">Precio no disponible</span>';
};

// Generar popup HTML mejorado con imagen y botón
const getPopupContent = (product: Product, extraInfo: string = '') => {
  const imageUrl = getPropertyImageUrl(product);
  const hasImage = !!imageUrl;

  const imageHtml = hasImage
    ? `<div class="relative w-full h-32 overflow-hidden rounded-t-lg">
        <img src="${imageUrl}" alt="${product.name}" class="w-full h-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
      </div>`
    : `<div class="w-full h-20 bg-gray-200 rounded-t-lg flex items-center justify-center">
        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
      </div>`;

  // Badge de operación con color
  const operacionColors = {
    'venta': 'bg-green-500',
    'alquiler': 'bg-blue-500',
    'anticretico': 'bg-red-500'
  };
  const operacionColor = operacionColors[product.operacion as keyof typeof operacionColors] || 'bg-gray-500';

  return `
    <div class="property-popup min-w-[280px] max-w-[320px]">
      ${imageHtml}
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
        .property-popup img {
          transition: transform 0.3s ease;
        }
        .property-popup:hover img {
          transform: scale(1.05);
        }
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
  router.visit('/propiedades');
};

const goToHome = () => {
  router.visit('/');
};

const goToProperties = () => {
  router.visit('/propiedades');
};

// Función para confirmar selección y cargar el mapa
const confirmSelectionAndLoadMap = () => {
  if (!canLoadMap.value) {
    alert('Por favor, selecciona al menos una categoría o tipo de operación');
    return;
  }
  showSelectionScreen.value = false;
  // Inicializar el mapa después de la confirmación
  setTimeout(() => {
    initMap();
  }, 100);
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
      let color = '#10b981';  /* Verde éxito - Mantenido */
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
  updateMarkers();

  // Solo escuchar clicks cuando el modo radar está activo
  map.on('click', (e) => {
    if (radarMode.value && !radarPlaced.value) {
      placeRadar(e);
    }
  });
};

const updateMarkers = () => {
  console.log('updateMarkers llamado');
  console.log('Estado del mapa:', { mapInitialized: !!map, clusterInitialized: !!markerClusterGroup });
  console.log('Filtros activos:', {
    categoria: categoriaSeleccionada.value,
    operacion: operacionSeleccionada.value
  });
  console.log('Productos a mostrar:', productosFiltrados.value.length);

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
    case 'venta': return '#10b981';     /* Verde éxito */
    case 'alquiler': return '#233C7A';   /* Azul Alfa */
    case 'anticretico': return '#E0081D'; /* Rojo Alfa */
    default: return '#525252';           /* Gris neutro */
  }
};

onMounted(() => {
  attachPopupButtonListeners();
  addFullScreenStyles();
  // No inicializar el mapa hasta que se seleccionen los filtros
  if (!showSelectionScreen.value) {
    initMap();
  }
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
          background: #10b981;  /* Verde éxito */
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

  <div class="relative w-screen h-screen overflow-hidden" @click="showCategoryDropdown = false; showOperationDropdown = false;">

    <!-- PANTALLA DE SELECCIÓN DE FILTROS -->
    <transition name="fade">
      <div v-if="showSelectionScreen" class="absolute inset-0 bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 z-[2000] flex items-center justify-center p-4">
        <div class="max-w-4xl w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 sm:p-8">
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
                  <Building :size="20" class="text-blue-600" />
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
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                        : 'border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500'
                    ]"
                  >
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ nombre }}</span>
                    <Check v-if="categoriaSeleccionada === parseInt(id as string)" :size="20" class="text-blue-600" />
                  </button>
                </div>
              </div>

              <!-- Operaciones -->
              <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                  <DollarSign :size="20" class="text-green-600" />
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
                        ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                        : 'border-gray-200 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-500'
                    ]"
                  >
                    <div class="flex items-center gap-3">
                      <component :is="op.icon" :size="18" />
                      <span class="font-medium text-gray-700 dark:text-gray-200">{{ op.label }}</span>
                    </div>
                    <Check v-if="operacionSeleccionada === op.value" :size="20" class="text-green-600" />
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
                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02]"
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
          class="bg-purple-600/90 hover:bg-purple-700/90 text-white px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
          title="Cambiar filtros"
        >
          <Filter :size="16" />
          <span class="hidden sm:inline text-sm font-medium">Cambiar Filtros</span>
        </button>
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

    <!-- CONTROLES MÓVIL SIMPLE - ELIMINADOS, ahora usan los mismos controles que desktop -->

    <!-- Dropdown filtros - ELIMINADO, ahora solo está el botón Cambiar Filtros -->

    <!-- Botón cambiar filtros (móvil) -->
    <div class="fixed bottom-4 left-4 z-[1200] lg:hidden">
      <button
        @click="openFilterPanel"
        class="bg-purple-600/90 hover:bg-purple-700/90 text-white p-3 rounded-lg shadow-lg transition-all backdrop-blur-sm"
        title="Cambiar filtros"
        aria-label="Cambiar filtros"
      >
        <Filter :size="18" />
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
            background: `linear-gradient(to right, #233C7A 0%, #233C7A ${((radarRadius - 100) / (5000 - 100) * 100)}%, #d4d4d4 ${((radarRadius - 100) / (5000 - 100) * 100)}%, #d4d4d4 100%)`
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

            <p class="text-xs text-gray-500 mb-2">{{ p.codigo_inmueble }}</p>

            <!-- Superficie compacta -->
            <div class="bg-gray-50 rounded-lg p-2 mb-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <div>
                  <p class="text-blue-600 font-medium mb-1">📐 Útil</p>
                  <p class="font-bold text-blue-800 text-sm sm:text-xs">
                    {{ p.superficie_util ? p.superficie_util.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-blue-700 text-xs" v-if="getPricePerSqmUtilDisplay(p)">
                    {{ getPricePerSqmUtilDisplay(p) }}
                  </p>
                </div>
                <div>
                  <p class="text-orange-600 font-medium mb-1">🏢 Constr.</p>
                  <p class="font-bold text-orange-800 text-sm sm:text-xs">
                    {{ p.superficie_construida ? p.superficie_construida.toLocaleString() + ' m²' : 'N/A' }}
                  </p>
                  <p class="text-orange-700 text-xs" v-if="getPricePerSqmConstruidaDisplay(p)">
                    {{ getPricePerSqmConstruidaDisplay(p) }}
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
          v-if="averagePricePerSqm.util.usd || averagePricePerSqm.util.bob || averagePricePerSqm.construida.usd || averagePricePerSqm.construida.bob || averageAnticreticoMensual.count > 0 || averageAlquiler.count > 0"
          class="border-t bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 sm:p-3 flex-shrink-0"
        >
          <!-- ================= VENTA: Promedios por m² ================= -->
          <div v-if="operacionSeleccionada === 'venta' || operacionSeleccionada === null">
            <p class="text-center text-sm sm:text-xs font-bold text-blue-100 mb-3 sm:mb-2">📊 Precio promedio en la zona</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-4 flex justify-between items-center flex-shrink-0">
          <div>
            <h3 class="font-bold text-lg">Cambiar Filtros</h3>
            <p class="text-xs text-purple-100">Selecciona las nuevas opciones</p>
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
                    ? 'bg-gradient-to-r from-purple-600 to-purple-700 text-white hover:from-purple-700 hover:to-purple-800'
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
