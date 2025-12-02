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

const handlePageChange = (page: number) => {
    if (page === currentPage.value) return;

    const params = { ...props.filtros, page: page.toString() };
    router.get(publicRoutes.propiedades.url(params), {}, {
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
    router.get(publicRoutes.propiedades.url(params), {}, {
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
    filtersSidebar.value?.clear();
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

// Cargar preferencias guardadas
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
});

// Guardar favoritos en localStorage cuando cambien
watch(favoriteProperties, (newFavorites) => {
    localStorage.setItem('favorite-properties', JSON.stringify(Array.from(newFavorites)));
}, { deep: true });
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
        <!-- Sidebar estático ocultable -->
        <div
            :class="[
                'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300 ease-in-out z-30',
                sidebarCollapsed ? 'w-16' : 'w-80',
                sidebarCollapsed ? 'fixed -translate-x-full lg:relative lg:translate-x-0' : 'fixed lg:relative',
                'h-screen lg:h-auto overflow-y-auto'
            ]"
        >
            <!-- Header del Sidebar -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 v-if="!sidebarCollapsed" class="text-lg font-semibold text-gray-900 dark:text-white">
                        Filtros
                    </h2>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden"
                    >
                        <component :is="sidebarCollapsed ? Menu : X" class="w-4 h-4" />
                    </Button>
                    <!-- Botón de cerrar solo visible en móvil cuando está abierto -->
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
                <!-- Filtro de Ubicación (comentado por ahora) -->
                <!--
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Ubicación
                    </label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todas las ubicaciones</option>
                    </select>
                </div>
                -->

                <!-- Filtro de Categorías -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Categorías
                    </label>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="casa" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Casas</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="departamento" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Departamentos</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="terreno" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Terrenos</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="oficina" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Oficinas</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="local" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Locales Comerciales</span>
                        </label>
                    </div>
                </div>

                <!-- Filtro de Tipo de Operación -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipo de Operación
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="venta" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Venta</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="alquiler" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Alquiler</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                            <input type="checkbox" value="anticretico" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Anticrético</span>
                        </label>
                    </div>
                </div>

                <!-- Filtros Numéricos -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Características</h3>

                    <!-- Número de Ambientes -->
                    <div class="space-y-2">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Ambientes</label>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">1</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">2</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">3</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">4+</button>
                        </div>
                    </div>

                    <!-- Número de Dormitorios -->
                    <div class="space-y-2">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Dormitorios</label>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">1</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">2</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">3</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">4+</button>
                        </div>
                    </div>

                    <!-- Número de Baños -->
                    <div class="space-y-2">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Baños</label>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">1</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">2</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">3</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">4+</button>
                        </div>
                    </div>

                    <!-- Número de Estacionamientos -->
                    <div class="space-y-2">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Estacionamientos</label>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">1</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">2</button>
                            <button class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">3+</button>
                        </div>
                    </div>
                </div>

                <!-- Filtros de Rango -->
                <div class="space-y-6">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Rangos</h3>

                    <!-- Rango de Precios -->
                    <div class="space-y-3">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Precio (USD)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" placeholder="Desde" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <span class="text-gray-500">-</span>
                            <input type="number" placeholder="Hasta" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <Button size="sm" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm">
                            Aplicar Precio
                        </Button>
                    </div>

                    <!-- Metros de Terreno -->
                    <div class="space-y-3">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Terreno (m²)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" placeholder="Desde" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <span class="text-gray-500">-</span>
                            <input type="number" placeholder="Hasta" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <Button size="sm" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm">
                            Aplicar Terreno
                        </Button>
                    </div>

                    <!-- Metros de Construcción -->
                    <div class="space-y-3">
                        <label class="text-xs text-gray-600 dark:text-gray-400">Metros de Construcción (m²)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" placeholder="Desde" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <span class="text-gray-500">-</span>
                            <input type="number" placeholder="Hasta" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <Button size="sm" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm">
                            Aplicar Construcción
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Icono cuando está colapsado -->
            <div v-else class="flex items-center justify-center h-full">
                <Button
                    variant="ghost"
                    @click="sidebarCollapsed = false"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <ChevronLeft class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </Button>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="flex-1 flex flex-col">
            <!-- Header del catálogo -->
            <div class="bg-white dark:bg-gray-800 border-b sticky top-0 z-40">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Título y contador -->
                        <div class="flex items-center gap-4">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="sidebarCollapsed = !sidebarCollapsed"
                                class="lg:hidden"
                            >
                                <Filter class="w-4 h-4" />
                                Filtros
                            </Button>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                                Catálogo de Propiedades
                            </h1>
                            <Badge v-if="total > 0" variant="secondary" class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ total }} propiedades
                            </Badge>
                        </div>

                        <!-- Controles -->
                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <!-- Vista toggle -->
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
            <div class="flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
                    <!-- Estado de carga -->
                    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <PropertyCardSkeleton :count="perPage" />
                    </div>

                    <!-- Lista de propiedades usando el componente separado -->
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
                            :description="hasFilters
                                ? 'No hay propiedades que coincidan con los filtros seleccionados. Intenta ajustar los criterios.'
                                : 'Actualmente no hay propiedades disponibles. Vuelve a revisar pronto.'"
                            action-text="Ajustar búsqueda"
                            @action="handleReset"
                            @retry="() => router.reload()"
                        />
                    </div>
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