<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Search, Grid, List, Download, Share, Filter, Menu, X, ChevronLeft } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import PropertyCard from '@/components/public/PropertyCard.vue';
import PropertyFiltersSidebar from '@/components/public/PropertyFiltersSidebar.vue';
import PropertyCardSkeleton from '@/components/public/PropertyCardSkeleton.vue';
import EmptyState from '@/components/public/EmptyState.vue';
import Pagination from '@/components/public/Pagination.vue';
import PropiedadesTable from './PropiedadesTable.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import publicRoutes from '@/routes/public';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from "@/components/ui/collapsible";
import { ChevronDown } from "lucide-vue-next"; 


interface Props {
    propiedades: any[];
    filtros: any;
    pagination?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from?: number;
        to?: number;
    };
    filter_options: {
        categorias: Record<string, string>;
        operaciones: Record<string, string>;
        rango_precios: Record<string, string>;
        ubicaciones: string[];
        opciones_ordenamiento: Record<string, string>;
    };
}

// Mostrar modal de filtros (solo móvil)
const showFiltersModal = ref(false);


const props = withDefaults(defineProps<Props>(), {
    propiedades: () => [],
    filtros: () => ({}),
    pagination: () => ({
        current_page: 1,
        last_page: 1,
        per_page: 12,
        total: 0
    })
});

defineOptions({
    layout: PublicLayout
});

// Estado del componente
const loading = ref(false);
const viewMode = ref<'grid' | 'list'>('grid');
const favoriteProperties = ref<Set<number>>(new Set());
const sidebarCollapsed = ref(false);
const filtersSidebar = ref<InstanceType<typeof PropertyFiltersSidebar>>();

// Estado para controlar qué dropdowns están abiertos
const openDropdowns = ref<Record<string, boolean>>({
    categorias: false,
    operaciones: false,
    caracteristicas: false,
    rangos: false
});

// Estado para los filtros seleccionados
const selectedFilters = ref({
    categorias: [] as number[],
    operaciones: [] as string[],
    ambientes: null as number | null,
    habitaciones: null as number | null,
    banos: null as number | null,
    cocheras: null as number | null
});

// Estado para los filtros de rango
const rangeFilters = ref({
    precio_min: '',
    precio_max: '',
    superficie_construida_min: '',
    superficie_construida_max: '',
    superficie_terreno_min: '',
    superficie_terreno_max: ''
});

// Computed properties
const hasPropiedades = computed(() => props.propiedades.length > 0);
const isLoading = computed(() => loading.value);
const hasFilters = computed(() => {
    return Object.values(props.filtros).some(value =>
        value !== undefined && value !== '' && value !== null
    );
});

const viewModeOptions = [
    { value: 'grid', label: 'Cuadrícula', icon: Grid },
    { value: 'list', label: 'Lista', icon: List }
];

const currentPage = computed(() => props.pagination?.current_page || 1);
const lastPage = computed(() => props.pagination?.last_page || 1);
const perPage = computed(() => props.pagination?.per_page || 12);
const total = computed(() => props.pagination?.total || 0);

// Métodos
const handleFilter = (newFilters: any) => {
    loading.value = true;

    // Construir URL con los nuevos filtros
    const params: Record<string, string> = {};

    Object.entries(newFilters).forEach(([key, value]) => {
        if (value !== undefined && value !== '' && value !== null) {
            params[key] = value.toString();
        }
    });

    // Resetear a la primera página si cambian los filtros
    if (!newFilters.page) {
        params.page = '1';
    }

    // Navegar con los nuevos filtros
    router.get(publicRoutes.propiedades.url(params), {}, {
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        }
    });
};

// Función para toggle de dropdowns (evita que se cierren al hacer click en checkboxes)
const toggleDropdown = (dropdown: string) => {
    openDropdowns.value[dropdown] = !openDropdowns.value[dropdown];
};

const handlePageChange = (page: number) => {
    if (page === currentPage.value) return;

    const params = { ...props.filtros, page: page.toString() };
    router.get('/propiedades', params, {
        preserveScroll: false,
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
            // Scroll al top de la página
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};

const handlePerPageChange = (newPerPage: number) => {
    const params = { ...props.filtros, per_page: newPerPage.toString(), page: '1' };
    router.get('/propiedades', params, {
        preserveScroll: false,
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

const handleReset = () => {
    resetFilters();
};

const handleFavorite = (propertyId: number) => {
    if (favoriteProperties.value.has(propertyId)) {
        favoriteProperties.value.delete(propertyId);
    } else {
        favoriteProperties.value.add(propertyId);
    }
    // Aquí podrías hacer una llamada API para persistir el favorito
};

const handleShare = async (propiedad: any) => {
    try {
        const shareData = {
            title: propiedad.name,
            text: `¡Mira esta propiedad en 365Soft! ${propiedad.codigo_inmueble}`,
            url: window.location.origin + publicRoutes.propiedad.show.url(propiedad.id)
        };

        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            // Fallback para navegadores que no soportan Web Share API
            await navigator.clipboard.writeText(shareData.url);
            // Aquí podrías mostrar un toast de "¡Enlace copiado!"
        }
    } catch (error) {
        console.error('Error al compartir propiedad:', error);
    }
};

// const exportProperties = () => {
//     // Lógica para exportar propiedades a PDF/Excel
//     console.log('Exportando propiedades...');
// };

const toggleViewMode = (mode: 'grid' | 'list') => {
    viewMode.value = mode;
    localStorage.setItem('propiedades-view-mode', mode);
};

// Funciones para manejar los filtros
const applyFilters = () => {
    loading.value = true;
    console.log('Applying filters...'); // Debug log - Updated

    // Construir parámetros para la visita de Inertia
    const params: Record<string, string> = {};

    // Filtros de categorías (múltiples)
    if (selectedFilters.value.categorias.length > 0) {
        params.categoria = selectedFilters.value.categorias.join(',');
    }

    // Filtros de operaciones (múltiples)
    if (selectedFilters.value.operaciones.length > 0) {
        params.operacion = selectedFilters.value.operaciones.join(',');
    }

    // Filtros de características (simple, solo uno a la vez)
    if (selectedFilters.value.ambientes) {
        params.ambientes = selectedFilters.value.ambientes.toString();
    }
    if (selectedFilters.value.habitaciones) {
        params.habitaciones = selectedFilters.value.habitaciones.toString();
    }
    if (selectedFilters.value.banos) {
        params.banos = selectedFilters.value.banos.toString();
    }
    if (selectedFilters.value.cocheras) {
        params.cocheras = selectedFilters.value.cocheras.toString();
    }

    // Filtros de rango
    if (rangeFilters.value.precio_min) {
        params.precio_min = rangeFilters.value.precio_min;
    }
    if (rangeFilters.value.precio_max) {
        params.precio_max = rangeFilters.value.precio_max;
    }
    if (rangeFilters.value.superficie_construida_min) {
        params.superficie_construida_min = rangeFilters.value.superficie_construida_min;
    }
    if (rangeFilters.value.superficie_construida_max) {
        params.superficie_construida_max = rangeFilters.value.superficie_construida_max;
    }
    if (rangeFilters.value.superficie_terreno_min) {
        params.superficie_terreno_min = rangeFilters.value.superficie_terreno_min;
    }
    if (rangeFilters.value.superficie_terreno_max) {
        params.superficie_terreno_max = rangeFilters.value.superficie_terreno_max;
    }

    // Resetear a la primera página
    params.page = '1';

    // Usar router.get para actualizar la URL y los datos
    router.get('/propiedades', params, {
        preserveScroll: true,
        preserveState: false, // Importante: para que las props se actualicen
        onFinish: () => {
            loading.value = false;
        }
    });
};

const applyRangeFilters = () => {
    applyFilters();
};

const toggleCharacteristicFilter = (type: string, value: number) => {
    // Si ya está seleccionado el mismo valor, lo deselecciona
    if (selectedFilters.value[type as keyof typeof selectedFilters.value] === value) {
        selectedFilters.value[type as keyof typeof selectedFilters.value] = null;
    } else {
        selectedFilters.value[type as keyof typeof selectedFilters.value] = value;
    }

    // Aplica los filtros automáticamente
    applyFilters();
};

const resetFilters = () => {
    // Resetear todos los filtros
    selectedFilters.value = {
        categorias: [],
        operaciones: [],
        ambientes: null,
        habitaciones: null,
        banos: null,
        cocheras: null
    };

    rangeFilters.value = {
        precio_min: '',
        precio_max: '',
        superficie_construida_min: '',
        superficie_construida_max: '',
        superficie_terreno_min: '',
        superficie_terreno_max: ''
    };

    // Aplicar filtros reseteados
    applyFilters();
};

// Inicializar filtros desde las props (que vienen de la URL)
const initializeFiltersFromProps = () => {
    // Cargar categorías (pueden venir como string separado por comas)
    if (props.filtros.categoria) {
        if (Array.isArray(props.filtros.categoria)) {
            selectedFilters.value.categorias = props.filtros.categoria.map(id => parseInt(id));
        } else if (typeof props.filtros.categoria === 'string') {
            selectedFilters.value.categorias = props.filtros.categoria.split(',').map(id => parseInt(id));
        } else {
            selectedFilters.value.categorias = [parseInt(props.filtros.categoria)];
        }
    }

    // Cargar operaciones (pueden venir como string separado por comas)
    if (props.filtros.operacion) {
        if (Array.isArray(props.filtros.operacion)) {
            selectedFilters.value.operaciones = props.filtros.operacion;
        } else if (typeof props.filtros.operacion === 'string') {
            selectedFilters.value.operaciones = props.filtros.operacion.split(',');
        } else {
            selectedFilters.value.operaciones = [props.filtros.operacion];
        }
    }

    // Cargar características
    selectedFilters.value.ambientes = props.filtros.ambientes ? parseInt(props.filtros.ambientes) : null;
    selectedFilters.value.habitaciones = props.filtros.habitaciones ? parseInt(props.filtros.habitaciones) : null;
    selectedFilters.value.banos = props.filtros.banos ? parseInt(props.filtros.banos) : null;
    selectedFilters.value.cocheras = props.filtros.cocheras ? parseInt(props.filtros.cocheras) : null;

    // Cargar rangos
    rangeFilters.value.precio_min = props.filtros.precio_min || '';
    rangeFilters.value.precio_max = props.filtros.precio_max || '';
    rangeFilters.value.superficie_construida_min = props.filtros.superficie_construida_min || '';
    rangeFilters.value.superficie_construida_max = props.filtros.superficie_construida_max || '';
    rangeFilters.value.superficie_terreno_min = props.filtros.superficie_terreno_min || '';
    rangeFilters.value.superficie_terreno_max = props.filtros.superficie_terreno_max || '';

    // Mantener compatibilidad con parámetros antiguos
    if (!rangeFilters.value.superficie_construida_min) {
        rangeFilters.value.superficie_construida_min = props.filtros.superficie_min || '';
    }
    if (!rangeFilters.value.superficie_construida_max) {
        rangeFilters.value.superficie_construida_max = props.filtros.superficie_max || '';
    }
};

// Cargar preferencias guardadas y filtros de la URL
onMounted(() => {
    const savedViewMode = localStorage.getItem('propiedades-view-mode') as 'grid' | 'list' | null;
    if (savedViewMode) {
        viewMode.value = savedViewMode;
    }

    // Cargar favoritos desde localStorage
    const savedFavorites = localStorage.getItem('favorite-properties');
    if (savedFavorites) {
        favoriteProperties.value = new Set(JSON.parse(savedFavorites));
    }

    // Inicializar filtros desde los parámetros de la URL
    initializeFiltersFromProps();
});

// Guardar favoritos en localStorage cuando cambien
watch(favoriteProperties, (newFavorites) => {
    localStorage.setItem('favorite-properties', JSON.stringify(Array.from(newFavorites)));
}, { deep: true });

// Sincronizar los filtros cuando cambian las props (cuando se aplica un filtro)
watch(() => props.filtros, (newFilters) => {
    initializeFiltersFromProps();
}, { deep: true });
</script>

<template>
  <div class="w-full">
    <!-- DESKTOP -->
    <div class="hidden lg:block">
      <div class="h-screen bg-gray-50 dark:bg-gray-900 flex overflow-hidden">
        <!-- SIDEBAR -->
        <aside
          :class="[
            'bg-white/80 dark:bg-[#0d1b2a] text-gray-800 dark:text-gray-200 border-r border-gray-300 dark:border-gray-700 backdrop-blur-sm transition-all duration-300 ease-in-out z-30 max-h-screen overflow-y-auto',
            sidebarCollapsed ? 'w-16' : 'w-80',
            sidebarCollapsed ? 'fixed -translate-x-full lg:relative lg:translate-x-0' : 'fixed lg:relative'
          ]"
        >
          <!-- Header del Sidebar -->
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 relative">
            <div class="flex items-center justify-between">
              <h2 v-if="!sidebarCollapsed" class="text-lg font-semibold text-gray-900 dark:text-white">Filtros</h2>

              <!-- Botón de limpiar filtros (solo visible cuando sidebar está expandido) -->
              <Button
                v-if="!sidebarCollapsed && hasFilters"
                @click="resetFilters"
                variant="outline"
                size="sm"
                class="text-xs px-2 py-1 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Limpiar
              </Button>

              <!-- Toggle (oculto en desktop porque lg:hidden) -->
              <Button
                variant="ghost"
                size="sm"
                @click="sidebarCollapsed = !sidebarCollapsed"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden"
              >
                <component :is="sidebarCollapsed ? Menu : X" class="w-4 h-4" />
              </Button>

              <!-- Botón de cerrar visible solo en móvil dentro del sidebar -->
              <Button
                v-if="sidebarCollapsed === false"
                variant="ghost"
                size="sm"
                @click="sidebarCollapsed = true"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden absolute top-4 right-4"
              >
                <X class="w-4 h-4" />
              </Button>
            </div>
          </div>

          <!-- Contenido del Sidebar -->
          <div v-if="!sidebarCollapsed" class="p-4 space-y-6">
            <!-- Filtro de Categorías -->
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
              <button
                @click="toggleDropdown('categorias')"
                class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-t-lg transition-colors"
              >
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Categorías</span>
                <ChevronDown
                  :class="[
                    'w-4 h-4 transition-transform duration-200',
                    openDropdowns.categorias ? 'rotate-180' : ''
                  ]"
                />
              </button>

              <div
                v-show="openDropdowns.categorias"
                class="border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-b-lg"
              >
                <div class="p-3 space-y-2 max-h-40 overflow-y-auto">
                  <label
                    v-for="(nombre, id) in filter_options.categorias"
                    :key="id"
                    class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded transition-colors"
                  >
                    <input
                      type="checkbox"
                      :value="id"
                      v-model="selectedFilters.categorias"
                      @click.stop
                      @change="applyFilters"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ nombre }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Filtro de Tipo de Operación -->
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
              <button
                @click="toggleDropdown('operaciones')"
                class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-t-lg transition-colors"
              >
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Operación</span>
                <ChevronDown
                  :class="[
                    'w-4 h-4 transition-transform duration-200',
                    openDropdowns.operaciones ? 'rotate-180' : ''
                  ]"
                />
              </button>

              <div
                v-show="openDropdowns.operaciones"
                class="border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-b-lg"
              >
                <div class="p-3 space-y-2 max-h-40 overflow-y-auto">
                  <label
                    v-for="(nombre, value) in filter_options.operaciones"
                    :key="value"
                    class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded transition-colors"
                  >
                    <input
                      type="checkbox"
                      :value="value"
                      v-model="selectedFilters.operaciones"
                      @click.stop
                      @change="applyFilters"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ nombre }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Filtros Numéricos -->
            <div class="space-y-4">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Características</h3>

              <!-- Ambientes -->
              <div class="space-y-2">
                <label class="text-xs text-gray-600 dark:text-gray-400">Ambientes</label>
                <div class="flex gap-2">
                  <button
                    v-for="num in [1, 2, 3, 4]"
                    :key="`amb-${num}`"
                    @click="toggleCharacteristicFilter('ambientes', num === 4 ? 4 : num)"
                    :class="[
                      'px-3 py-1 text-xs border rounded transition-colors',
                      selectedFilters.ambientes === (num === 4 ? 4 : num)
                        ? 'bg-blue-500 text-white border-blue-500'
                        : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]"
                  >
                    {{ num === 4 ? '4+' : num }}
                  </button>
                </div>
              </div>

              <!-- Dormitorios -->
              <div class="space-y-2">
                <label class="text-xs text-gray-600 dark:text-gray-400">Dormitorios</label>
                <div class="flex gap-2">
                  <button
                    v-for="num in [1, 2, 3, 4]"
                    :key="`hab-${num}`"
                    @click="toggleCharacteristicFilter('habitaciones', num === 4 ? 4 : num)"
                    :class="[
                      'px-3 py-1 text-xs border rounded transition-colors',
                      selectedFilters.habitaciones === (num === 4 ? 4 : num)
                        ? 'bg-blue-500 text-white border-blue-500'
                        : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]"
                  >
                    {{ num === 4 ? '4+' : num }}
                  </button>
                </div>
              </div>

              <!-- Baños -->
              <div class="space-y-2">
                <label class="text-xs text-gray-600 dark:text-gray-400">Baños</label>
                <div class="flex gap-2">
                  <button
                    v-for="num in [1, 2, 3, 4]"
                    :key="`ban-${num}`"
                    @click="toggleCharacteristicFilter('banos', num === 4 ? 4 : num)"
                    :class="[
                      'px-3 py-1 text-xs border rounded transition-colors',
                      selectedFilters.banos === (num === 4 ? 4 : num)
                        ? 'bg-blue-500 text-white border-blue-500'
                        : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]"
                  >
                    {{ num === 4 ? '4+' : num }}
                  </button>
                </div>
              </div>

              <!-- Estacionamientos -->
              <div class="space-y-2">
                <label class="text-xs text-gray-600 dark:text-gray-400">Estacionamientos</label>
                <div class="flex gap-2">
                  <button
                    v-for="num in [1, 2, 3]"
                    :key="`coc-${num}`"
                    @click="toggleCharacteristicFilter('cocheras', num === 3 ? 3 : num)"
                    :class="[
                      'px-3 py-1 text-xs border rounded transition-colors',
                      selectedFilters.cocheras === (num === 3 ? 3 : num)
                        ? 'bg-blue-500 text-white border-blue-500'
                        : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]"
                  >
                    {{ num === 3 ? '3+' : num }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Rangos -->
            <div class="space-y-6 pb-20">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Rangos</h3>

              <!-- Precio -->
              <div class="space-y-3">
                <label class="text-xs text-gray-600 dark:text-gray-400">Precio (USD)</label>
                <div class="flex gap-2 items-center">
                  <input
                    type="number"
                    v-model="rangeFilters.precio_min"
                    placeholder="Desde"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                  <span class="text-gray-500">-</span>
                  <input
                    type="number"
                    v-model="rangeFilters.precio_max"
                    placeholder="Hasta"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <Button
                  @click="applyRangeFilters"
                  size="sm"
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm"
                >
                  Aplicar Precio
                </Button>
              </div>

              <!-- Metros de Construcción -->
              <div class="space-y-3">
                <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Construcción (m²)</label>
                <div class="flex gap-2 items-center">
                  <input
                    type="number"
                    v-model="rangeFilters.superficie_construida_min"
                    placeholder="Desde"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                  <span class="text-gray-500">-</span>
                  <input
                    type="number"
                    v-model="rangeFilters.superficie_construida_max"
                    placeholder="Hasta"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <Button
                  @click="applyRangeFilters"
                  size="sm"
                  class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm"
                >
                  Aplicar Construcción
                </Button>
              </div>

              <!-- Metros de Terreno -->
              <div class="space-y-3">
                <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Terreno (m²)</label>
                <div class="flex gap-2 items-center">
                  <input
                    type="number"
                    v-model="rangeFilters.superficie_terreno_min"
                    placeholder="Desde"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                  <span class="text-gray-500">-</span>
                  <input
                    type="number"
                    v-model="rangeFilters.superficie_terreno_max"
                    placeholder="Hasta"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <Button
                  @click="applyRangeFilters"
                  size="sm"
                  class="w-full bg-green-600 hover:bg-green-700 text-white text-sm"
                >
                  Aplicar Terreno
                </Button>
              </div>
            </div>
          </div>
          <!-- Icono cuando está colapsado -->
          <div v-else class="flex items-center justify-center h-full">
            <Button variant="ghost" @click="sidebarCollapsed = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
              <ChevronLeft class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </Button>
          </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="flex-1 flex flex-col max-h-screen overflow-y-auto">
          <!-- Header del catálogo -->
          <div class="bg-gray-900 text-gray-200 border-gray-700 border-b sticky top-0 z-40">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Título y contador -->
                <div class="flex items-center gap-4">
                  <Button variant="outline" size="sm" @click="showFiltersModal = true" class="lg:hidden">
                    <Filter class="w-4 h-4" />
                    Filtros
                  </Button>
                  <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">Catálogo de Propiedades</h1>
                  <Badge v-if="total > 0" variant="secondary" class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ total }} propiedades</Badge>
                </div>

                <!-- Controles -->
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                  <div class="flex items-center border rounded-lg p-1">
                    <Button
                      v-for="mode in viewModeOptions"
                      :key="mode.value"
                      :variant="viewMode === mode.value ? 'default' : 'ghost'"
                      size="sm"
                      @click="toggleViewMode(mode.value)"
                      class="h-8 w-8 p-0"
                    >
                      <component :is="mode.icon" class="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Contenido de propiedades -->
          <main class="flex-1 overflow-y-auto">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 lg:py-28">
              <!-- Estado de carga -->
              <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <PropertyCardSkeleton :count="perPage" />
              </div>

              <!-- Lista de propiedades -->
              <PropiedadesTable
                v-else-if="hasPropiedades"
                :propiedades="propiedades"
                :pagination="pagination"
                :loading="isLoading"
                :view-mode="viewMode"
                @favorite="handleFavorite"
                @share="handleShare"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
              />

              <!-- Estado vacío -->
              <div v-else class="py-12">
                <EmptyState
                  :type="hasFilters ? 'filter' : 'general'"
                  :loading="isLoading"
                  title="No se encontraron propiedades"
                  :description="hasFilters ? 'No hay propiedades que coincidan con los filtros seleccionados. Intenta ajustar los criterios.' : 'Actualmente no hay propiedades disponibles. Vuelve a revisar pronto.'"
                  action-text="Ajustar búsqueda"
                  @action="handleReset"
                  @retry="() => router.reload()"
                />
              </div>
            </div>
          </main>
        </div>
      </div>
    </div>

    <!-- MOBILE -->
    <div class="lg:hidden min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
      <!-- Header móvil -->
      <header class="sticky top-0 z-40 bg-gray-900 text-gray-200 border-b border-gray-700">
        <div class="px-4 py-4 flex items-center justify-between">
          <h1 class="text-lg font-semibold">Catálogo de Propiedades</h1>
          <button class="inline-flex items-center gap-2 rounded-lg border border-gray-700 px-3 py-1 text-sm" @click="showFiltersModal = true">
            <Filter class="w-4 h-4" />
            <span>Filtros</span>
          </button>
        </div>
      </header>

      <!-- Contenido propiedades móvil -->
      <main class="flex-1 overflow-y-auto">
        <div class="px-4 py-4">
          <!-- Estado de carga -->
          <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <PropertyCardSkeleton :count="perPage" />
          </div>

          <!-- Lista o vacío -->
          <PropiedadesTable
            v-else-if="hasPropiedades"
            :propiedades="propiedades"
            :pagination="pagination"
            :loading="isLoading"
            :view-mode="viewMode"
            @favorite="handleFavorite"
            @share="handleShare"
            @page-change="handlePageChange"
            @per-page-change="handlePerPageChange"
          />

          <div v-else class="py-8">
            <EmptyState
              :type="hasFilters ? 'filter' : 'general'"
              :loading="isLoading"
              title="No se encontraron propiedades"
              :description="hasFilters ? 'No hay propiedades que coincidan con los filtros seleccionados. Intenta ajustar los criterios.' : 'Actualmente no hay propiedades disponibles. Vuelve a revisar pronto.'"
              action-text="Ajustar búsqueda"
              @action="handleReset"
              @retry="() => router.reload()"
            />
          </div>
        </div>
      </main>
    </div>

<!-- MODAL FILTROS (solo móvil) -->
<div v-if="showFiltersModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center p-4 z-50 lg:hidden">
  <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md p-6 relative">
    <button class="absolute right-4 top-4 text-gray-500" @click="showFiltersModal = false">✕</button>
    <h2 class="text-xl font-semibold mb-4">Filtros</h2>

    <div class="space-y-6 max-h-[70vh] overflow-y-auto">
      <!-- Categorías -->
      <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
        <button
          @click="toggleDropdown('categorias')"
          class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-t-lg transition-colors"
        >
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Categorías</span>
          <ChevronDown
            :class="[
              'w-4 h-4 transition-transform duration-200',
              openDropdowns.categorias ? 'rotate-180' : ''
            ]"
          />
        </button>

        <div
          v-show="openDropdowns.categorias"
          class="border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-b-lg"
        >
          <div class="p-3 space-y-2 max-h-40 overflow-y-auto">
            <label
              v-for="(nombre, id) in filter_options.categorias"
              :key="id"
              class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded transition-colors"
            >
              <input
                type="checkbox"
                :value="id"
                v-model="selectedFilters.categorias"
                @click.stop
                @change="applyFilters"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              >
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ nombre }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Tipo de Operación -->
      <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
        <button
          @click="toggleDropdown('operaciones')"
          class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-t-lg transition-colors"
        >
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Operación</span>
          <ChevronDown
            :class="[
              'w-4 h-4 transition-transform duration-200',
              openDropdowns.operaciones ? 'rotate-180' : ''
            ]"
          />
        </button>

        <div
          v-show="openDropdowns.operaciones"
          class="border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-b-lg"
        >
          <div class="p-3 space-y-2 max-h-40 overflow-y-auto">
            <label
              v-for="(nombre, value) in filter_options.operaciones"
              :key="value"
              class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded transition-colors"
            >
              <input
                type="checkbox"
                :value="value"
                v-model="selectedFilters.operaciones"
                @click.stop
                @change="applyFilters"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              >
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ nombre }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Filtros Numéricos (Características) -->
      <div class="space-y-4">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Características</h3>

        <!-- Ambientes -->
        <div class="space-y-2">
          <label class="text-xs text-gray-600 dark:text-gray-400">Ambientes</label>
          <div class="flex gap-2">
            <button
              v-for="num in [1, 2, 3, 4]"
              :key="`amb-mob-${num}`"
              @click="toggleCharacteristicFilter('ambientes', num === 4 ? 4 : num)"
              :class="[
                'px-3 py-1 text-xs border rounded transition-colors',
                selectedFilters.ambientes === (num === 4 ? 4 : num)
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
              ]"
            >
              {{ num === 4 ? '4+' : num }}
            </button>
          </div>
        </div>

        <!-- Dormitorios -->
        <div class="space-y-2">
          <label class="text-xs text-gray-600 dark:text-gray-400">Dormitorios</label>
          <div class="flex gap-2">
            <button
              v-for="num in [1, 2, 3, 4]"
              :key="`hab-mob-${num}`"
              @click="toggleCharacteristicFilter('habitaciones', num === 4 ? 4 : num)"
              :class="[
                'px-3 py-1 text-xs border rounded transition-colors',
                selectedFilters.habitaciones === (num === 4 ? 4 : num)
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
              ]"
            >
              {{ num === 4 ? '4+' : num }}
            </button>
          </div>
        </div>

        <!-- Baños -->
        <div class="space-y-2">
          <label class="text-xs text-gray-600 dark:text-gray-400">Baños</label>
          <div class="flex gap-2">
            <button
              v-for="num in [1, 2, 3, 4]"
              :key="`ban-mob-${num}`"
              @click="toggleCharacteristicFilter('banos', num === 4 ? 4 : num)"
              :class="[
                'px-3 py-1 text-xs border rounded transition-colors',
                selectedFilters.banos === (num === 4 ? 4 : num)
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
              ]"
            >
              {{ num === 4 ? '4+' : num }}
            </button>
          </div>
        </div>

        <!-- Estacionamientos -->
        <div class="space-y-2">
          <label class="text-xs text-gray-600 dark:text-gray-400">Estacionamientos</label>
          <div class="flex gap-2">
            <button
              v-for="num in [1, 2, 3]"
              :key="`coc-mob-${num}`"
              @click="toggleCharacteristicFilter('cocheras', num === 3 ? 3 : num)"
              :class="[
                'px-3 py-1 text-xs border rounded transition-colors',
                selectedFilters.cocheras === (num === 3 ? 3 : num)
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
              ]"
            >
              {{ num === 3 ? '3+' : num }}
            </button>
          </div>
        </div>
      </div>

      <!-- Rangos -->
      <div class="space-y-6 pb-20">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Rangos</h3>

        <!-- Precio -->
        <div class="space-y-3">
          <label class="text-xs text-gray-600 dark:text-gray-400">Precio (USD)</label>
          <div class="flex gap-2 items-center">
            <input
              type="number"
              v-model="rangeFilters.precio_min"
              placeholder="Desde"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <span class="text-gray-500">-</span>
            <input
              type="number"
              v-model="rangeFilters.precio_max"
              placeholder="Hasta"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
          <Button
            @click="applyRangeFilters"
            size="sm"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm"
          >
            Aplicar Precio
          </Button>
        </div>

        <!-- Metros de Construcción -->
        <div class="space-y-3">
          <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Construcción (m²)</label>
          <div class="flex gap-2 items-center">
            <input
              type="number"
              v-model="rangeFilters.superficie_construida_min"
              placeholder="Desde"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <span class="text-gray-500">-</span>
            <input
              type="number"
              v-model="rangeFilters.superficie_construida_max"
              placeholder="Hasta"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
          <Button
            @click="applyRangeFilters"
            size="sm"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm"
          >
            Aplicar Construcción
          </Button>
        </div>

        <!-- Metros de Terreno -->
        <div class="space-y-3">
          <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Terreno (m²)</label>
          <div class="flex gap-2 items-center">
            <input
              type="number"
              v-model="rangeFilters.superficie_terreno_min"
              placeholder="Desde"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <span class="text-gray-500">-</span>
            <input
              type="number"
              v-model="rangeFilters.superficie_terreno_max"
              placeholder="Hasta"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
          <Button
            @click="applyRangeFilters"
            size="sm"
            class="w-full bg-green-600 hover:bg-green-700 text-white text-sm"
          >
            Aplicar Terreno
          </Button>
        </div>
      </div>
    </div>

    <!-- BOTONES EN LA PARTE INFERIOR -->
    <div class="mt-4 flex gap-3">
      <Button @click="showFiltersModal = false" class="flex-1">
        Aplicar Filtros
      </Button>
      <Button @click="resetFilters" variant="outline" class="flex-1">
        Limpiar
      </Button>
    </div>
  </div>
</div>
</div>
</template>

<style scoped>
/* Estilos para el modo lista */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Asegurar buen spacing en móviles */
@media (max-width: 640px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 768px) and (max-width: 1024px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mejorar visibilidad del sticky header */
.sticky {
    backdrop-filter: blur(8px);
    background-color: rgba(255, 255, 255, 0.9);
}

.dark .sticky {
    background-color: rgba(31, 41, 55, 0.9);
}
</style>