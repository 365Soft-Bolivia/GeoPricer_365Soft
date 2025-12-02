<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    Filter,
    X,
    ChevronDown,
    ChevronRight,
    Search,
    DollarSign,
    Home,
    MapPin,
    BedDouble,
    Bath,
    Maximize2,
    Car,
    Calendar,
    SlidersHorizontal,
    RotateCcw
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger
} from '@/components/ui/collapsible';

interface Props {
    filtros?: {
        busqueda?: string;
        categorias?: string[];
        operaciones?: string[];
        precio_min?: number;
        precio_max?: number;
        ubicaciones?: string[];
        ambientes_min?: number;
        ambientes_max?: number;
        habitaciones_min?: number;
        habitaciones_max?: number;
        banos_min?: number;
        estacionamientos_min?: number;
        estacionamientos_max?: number;
        superficie_terreno_min?: number;
        superficie_terreno_max?: number;
        superficie_construida_min?: number;
        superficie_construida_max?: number;
        antiguedad_min?: number;
        antiguedad_max?: number;
        ordenar_por?: string;
        orden?: string;
    };
    options?: {
        categorias: Record<string, string>;
        operaciones: Record<string, string>;
        ubicaciones: string[];
        opciones_ordenamiento: Record<string, string>;
    };
    loading?: boolean;
    isCollapsed?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    isCollapsed: true
});

const emit = defineEmits<{
    filter: [filtros: Props['filtros']];
    reset: [];
    toggleCollapse: [];
}>();

// Estado local
const filtrosLocales = ref({
    busqueda: props.filtros?.busqueda || '',
    categorias: props.filtros?.categorias || [],
    operaciones: props.filtros?.operaciones || [],
    precio_min: props.filtros?.precio_min || undefined,
    precio_max: props.filtros?.precio_max || undefined,
    ubicaciones: props.filtros?.ubicaciones || [],
    ambientes_min: props.filtros?.ambientes_min || undefined,
    ambientes_max: props.filtros?.ambientes_max || undefined,
    habitaciones_min: props.filtros?.habitaciones_min || undefined,
    habitaciones_max: props.filtros?.habitaciones_max || undefined,
    banos_min: props.filtros?.banos_min || undefined,
    estacionamientos_min: props.filtros?.estacionamientos_min || undefined,
    estacionamientos_max: props.filtros?.estacionamientos_max || undefined,
    superficie_terreno_min: props.filtros?.superficie_terreno_min || undefined,
    superficie_terreno_max: props.filtros?.superficie_terreno_max || undefined,
    superficie_construida_min: props.filtros?.superficie_construida_min || undefined,
    superficie_construida_max: props.filtros?.superficie_construida_max || undefined,
    antiguedad_min: props.filtros?.antiguedad_min || undefined,
    antiguedad_max: props.filtros?.antiguedad_max || undefined,
    ordenar_por: props.filtros?.ordenar_por || 'created_at',
    orden: props.filtros?.orden || 'desc'
});

// Estado de secciones colapsables
const seccionesAbiertas = ref({
    basicos: true,
    precio: false,
    caracteristicas: false,
    ubicacion: false,
    superficie: false,
    adicionales: false
});

const searchInput = ref<HTMLInputElement>();

// Computed properties
const filtrosActivosCount = computed(() => {
    let count = 0;
    const f = filtrosLocales.value;

    if (f.busqueda) count++;
    if (f.categorias?.length) count++;
    if (f.operaciones?.length) count++;
    if (f.precio_min !== undefined || f.precio_max !== undefined) count++;
    if (f.ubicaciones?.length) count++;
    if (f.ambientes_min !== undefined || f.ambientes_max !== undefined) count++;
    if (f.habitaciones_min !== undefined || f.habitaciones_max !== undefined) count++;
    if (f.banos_min !== undefined) count++;
    if (f.estacionamientos_min !== undefined || f.estacionamientos_max !== undefined) count++;
    if (f.superficie_terreno_min !== undefined || f.superficie_terreno_max !== undefined) count++;
    if (f.superficie_construida_min !== undefined || f.superficie_construida_max !== undefined) count++;
    if (f.antiguedad_min !== undefined || f.antiguedad_max !== undefined) count++;

    return count;
});

const tieneFiltrosActivos = computed(() => filtrosActivosCount.value > 0);

const precioFormateadoMin = computed(() => {
    return filtrosLocales.value.precio_min
        ? new Intl.NumberFormat('es-BO').format(filtrosLocales.value.precio_min)
        : '';
});

const precioFormateadoMax = computed(() => {
    return filtrosLocales.value.precio_max
        ? new Intl.NumberFormat('es-BO').format(filtrosLocales.value.precio_max)
        : '';
});

// Métodos
const toggleCollapse = () => {
    emit('toggleCollapse');
};

const toggleSeccion = (seccion: keyof typeof seccionesAbiertas.value) => {
    seccionesAbiertas.value[seccion] = !seccionesAbiertas.value[seccion];
};

const limpiarFiltros = () => {
    Object.keys(filtrosLocales.value).forEach(key => {
        if (Array.isArray(filtrosLocales.value[key as keyof typeof filtrosLocales.value])) {
            (filtrosLocales.value as any)[key] = [];
        } else if (typeof filtrosLocales.value[key as keyof typeof filtrosLocales.value] === 'number') {
            (filtrosLocales.value as any)[key] = undefined;
        } else if (typeof filtrosLocales.value[key as keyof typeof filtrosLocales.value] === 'string') {
            (filtrosLocales.value as any)[key] = '';
        }
    });

    emit('reset');
    emit('filter', {});
};

const aplicarFiltros = () => {
    const filtrosLimpios = Object.fromEntries(
        Object.entries(filtrosLocales.value).filter(([_, valor]) => {
            if (Array.isArray(valor)) {
                return valor.length > 0;
            }
            return valor !== undefined && valor !== '' && valor !== null;
        })
    );

    emit('filter', filtrosLimpios);
};

const handleCategoriaChange = (categoriaId: string, checked: boolean) => {
    const categorias = [...(filtrosLocales.value.categorias || [])];
    if (checked) {
        if (!categorias.includes(categoriaId)) {
            categorias.push(categoriaId);
        }
    } else {
        const index = categorias.indexOf(categoriaId);
        if (index > -1) {
            categorias.splice(index, 1);
        }
    }
    filtrosLocales.value.categorias = categorias;
};

const handleOperacionChange = (operacionId: string, checked: boolean) => {
    const operaciones = [...(filtrosLocales.value.operaciones || [])];
    if (checked) {
        if (!operaciones.includes(operacionId)) {
            operaciones.push(operacionId);
        }
    } else {
        const index = operaciones.indexOf(operacionId);
        if (index > -1) {
            operaciones.splice(index, 1);
        }
    }
    filtrosLocales.value.operaciones = operaciones;
};

const handleUbicacionChange = (ubicacion: string, checked: boolean) => {
    const ubicaciones = [...(filtrosLocales.value.ubicaciones || [])];
    if (checked) {
        if (!ubicaciones.includes(ubicacion)) {
            ubicaciones.push(ubicacion);
        }
    } else {
        const index = ubicaciones.indexOf(ubicacion);
        if (index > -1) {
            ubicaciones.splice(index, 1);
        }
    }
    filtrosLocales.value.ubicaciones = ubicaciones;
};

const handleSearch = (event?: KeyboardEvent) => {
    if (event?.key === 'Enter') {
        aplicarFiltros();
    }
};

// Watch para actualizar filtros cuando cambian las props
watch(() => props.filtros, (nuevosFiltros) => {
    if (nuevosFiltros) {
        Object.assign(filtrosLocales.value, nuevosFiltros);
    }
}, { deep: true, immediate: true });

// Exponer métodos para acceso externo
defineExpose({
    focus: () => searchInput.value?.focus(),
    clear: limpiarFiltros
});
</script>

<template>
    <!-- Sidebar colapsable -->
    <div
        :class="[
            'bg-white dark:bg-gray-800 border-r h-full transition-all duration-300 ease-in-out',
            props.isCollapsed ? 'w-12 lg:w-64' : 'w-12'
        ]"
    >
        <!-- Botón de toggle -->
        <div class="p-4 border-b dark:border-gray-700">
            <Button
                variant="ghost"
                size="sm"
                @click="toggleCollapse"
                class="w-full justify-center p-2"
            >
                <Filter class="w-5 h-5" />
                <span
                    v-if="!props.isCollapsed"
                    class="ml-2 font-medium"
                >
                    Filtros
                    <Badge v-if="tieneFiltrosActivos" variant="secondary" class="ml-2">
                        {{ filtrosActivosCount }}
                    </Badge>
                </span>
            </Button>
        </div>

        <!-- Contenido de filtros -->
        <div v-if="!props.isCollapsed" class="h-[calc(100vh-80px)] overflow-y-auto">
            <div class="p-4 space-y-6">
                <!-- Búsqueda general -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <Search class="w-4 h-4" />
                        Búsqueda
                    </label>
                    <Input
                        ref="searchInput"
                        v-model="filtrosLocales.busqueda"
                        placeholder="Buscar propiedades..."
                        @keyup="handleSearch"
                        class="w-full"
                    />
                </div>

                <!-- Filtros básicos -->
                <Collapsible v-model:open="seccionesAbiertas.basicos">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Home class="w-4 h-4" />
                            Básicos
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.basicos }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <!-- Categorías -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Categorías
                            </label>
                            <div class="space-y-2">
                                <div
                                    v-for="(nombre, id) in options?.categorias"
                                    :key="id"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`categoria-${id}`"
                                        :checked="filtrosLocales.categorias?.includes(id)"
                                        @change="handleCategoriaChange(id, ($event.target as HTMLInputElement).checked)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <label
                                        :for="`categoria-${id}`"
                                        class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
                                    >
                                        {{ nombre }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Operaciones -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipo de operación
                            </label>
                            <div class="space-y-2">
                                <div
                                    v-for="(nombre, id) in options?.operaciones"
                                    :key="id"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`operacion-${id}`"
                                        :checked="filtrosLocales.operaciones?.includes(id)"
                                        @change="handleOperacionChange(id, ($event.target as HTMLInputElement).checked)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <label
                                        :for="`operacion-${id}`"
                                        class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
                                    >
                                        {{ nombre }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Rango de precios -->
                <Collapsible v-model:open="seccionesAbiertas.precio">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <DollarSign class="w-4 h-4" />
                            Precio
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.precio }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <div class="space-y-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Rango de precios (USD)
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Desde</label>
                                    <Input
                                        v-model.number="filtrosLocales.precio_min"
                                        type="number"
                                        placeholder="Mínimo"
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Hasta</label>
                                    <Input
                                        v-model.number="filtrosLocales.precio_max"
                                        type="number"
                                        placeholder="Máximo"
                                        min="0"
                                    />
                                </div>
                            </div>
                            <div v-if="precioFormateadoMin || precioFormateadoMax" class="text-xs text-gray-600 dark:text-gray-400">
                                Rango actual: ${{ precioFormateadoMin }} - ${{ precioFormateadoMax }}
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Características -->
                <Collapsible v-model:open="seccionesAbiertas.caracteristicas">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <BedDouble class="w-4 h-4" />
                            Características
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.caracteristicas }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <div class="space-y-4">
                            <!-- Ambientes -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Ambientes (min)</label>
                                    <Input
                                        v-model.number="filtrosLocales.ambientes_min"
                                        type="number"
                                        placeholder="Mínimo"
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Ambientes (max)</label>
                                    <Input
                                        v-model.number="filtrosLocales.ambientes_max"
                                        type="number"
                                        placeholder="Máximo"
                                        min="0"
                                    />
                                </div>
                            </div>

                            <!-- Habitaciones -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Habitaciones (min)</label>
                                    <Input
                                        v-model.number="filtrosLocales.habitaciones_min"
                                        type="number"
                                        placeholder="Mínimo"
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Habitaciones (max)</label>
                                    <Input
                                        v-model.number="filtrosLocales.habitaciones_max"
                                        type="number"
                                        placeholder="Máximo"
                                        min="0"
                                    />
                                </div>
                            </div>

                            <!-- Baños -->
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">Baños (mínimo)</label>
                                <Input
                                    v-model.number="filtrosLocales.banos_min"
                                    type="number"
                                    placeholder="Mínimo de baños"
                                    min="0"
                                />
                            </div>

                            <!-- Estacionamientos -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Estacionamientos (min)</label>
                                    <Input
                                        v-model.number="filtrosLocales.estacionamientos_min"
                                        type="number"
                                        placeholder="Mínimo"
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400">Estacionamientos (max)</label>
                                    <Input
                                        v-model.number="filtrosLocales.estacionamientos_max"
                                        type="number"
                                        placeholder="Máximo"
                                        min="0"
                                    />
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Ubicación -->
                <Collapsible v-model:open="seccionesAbiertas.ubicacion">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <MapPin class="w-4 h-4" />
                            Ubicación
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.ubicacion }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ubicaciones
                            </label>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <div
                                    v-for="ubicacion in options?.ubicaciones"
                                    :key="ubicacion"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`ubicacion-${ubicacion}`"
                                        :checked="filtrosLocales.ubicaciones?.includes(ubicacion)"
                                        @change="handleUbicacionChange(ubicacion, ($event.target as HTMLInputElement).checked)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <label
                                        :for="`ubicacion-${ubicacion}`"
                                        class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
                                    >
                                        {{ ubicacion }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Superficie -->
                <Collapsible v-model:open="seccionesAbiertas.superficie">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Maximize2 class="w-4 h-4" />
                            Superficie
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.superficie }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <div class="space-y-4">
                            <!-- Terreno -->
                            <div class="space-y-3">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Terreno (m²)
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Desde</label>
                                        <Input
                                            v-model.number="filtrosLocales.superficie_terreno_min"
                                            type="number"
                                            placeholder="Mínimo"
                                            min="0"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Hasta</label>
                                        <Input
                                            v-model.number="filtrosLocales.superficie_terreno_max"
                                            type="number"
                                            placeholder="Máximo"
                                            min="0"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Construida -->
                            <div class="space-y-3">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Construida (m²)
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Desde</label>
                                        <Input
                                            v-model.number="filtrosLocales.superficie_construida_min"
                                            type="number"
                                            placeholder="Mínimo"
                                            min="0"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Hasta</label>
                                        <Input
                                            v-model.number="filtrosLocales.superficie_construida_max"
                                            type="number"
                                            placeholder="Máximo"
                                            min="0"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Antigüedad -->
                <Collapsible v-model:open="seccionesAbiertas.adicionales">
                    <CollapsibleTrigger class="w-full flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Calendar class="w-4 h-4" />
                            Adicionales
                        </span>
                        <ChevronDown
                            :class="{ 'rotate-180': seccionesAbiertas.adicionales }"
                            class="w-4 h-4 transition-transform"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-4 mt-2">
                        <div class="space-y-4">
                            <!-- Antigüedad -->
                            <div class="space-y-3">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Antigüedad (años)
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Desde</label>
                                        <Input
                                            v-model.number="filtrosLocales.antiguedad_min"
                                            type="number"
                                            placeholder="Mínimo"
                                            min="0"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Hasta</label>
                                        <Input
                                            v-model.number="filtrosLocales.antiguedad_max"
                                            type="number"
                                            placeholder="Máximo"
                                            min="0"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Botones de acción -->
                <div class="pt-4 border-t dark:border-gray-700 space-y-3">
                    <Button
                        @click="aplicarFiltros"
                        :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        <Search class="w-4 h-4 mr-2" />
                        {{ loading ? 'Aplicando...' : 'Aplicar filtros' }}
                    </Button>

                    <div class="grid grid-cols-2 gap-3">
                        <Button
                            v-if="tieneFiltrosActivos"
                            variant="outline"
                            @click="limpiarFiltros"
                            :disabled="loading"
                            class="w-full"
                        >
                            <RotateCcw class="w-4 h-4 mr-1" />
                            Limpiar
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="toggleSeccion('basicos'); toggleSeccion('precio'); toggleSeccion('caracteristicas'); toggleSeccion('ubicacion'); toggleSeccion('superficie'); toggleSeccion('adicionales')"
                            class="w-full"
                        >
                            <SlidersHorizontal class="w-4 h-4 mr-1" />
                            Expandir todo
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado colapsado (solo iconos) -->
        <div v-else class="p-2 space-y-4">
            <!-- Búsqueda -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse"
                    class="p-2"
                    title="Buscar"
                >
                    <Search class="w-5 h-5" />
                </Button>
            </div>

            <!-- Filtros básicos -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('basicos')"
                    class="p-2"
                    :class="{ 'text-blue-600': filtrosLocales.categorias?.length || filtrosLocales.operaciones?.length }"
                    title="Filtros básicos"
                >
                    <Home class="w-5 h-5" />
                </Button>
            </div>

            <!-- Precio -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('precio')"
                    class="p-2"
                    :class="{ 'text-blue-600': filtrosLocales.precio_min !== undefined || filtrosLocales.precio_max !== undefined }"
                    title="Precio"
                >
                    <DollarSign class="w-5 h-5" />
                </Button>
            </div>

            <!-- Características -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('caracteristicas')"
                    class="p-2"
                    :class="{ 'text-blue-600': tieneCaracteristicasActivas }"
                    title="Características"
                >
                    <BedDouble class="w-5 h-5" />
                </Button>
            </div>

            <!-- Ubicación -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('ubicacion')"
                    class="p-2"
                    :class="{ 'text-blue-600': filtrosLocales.ubicaciones?.length }"
                    title="Ubicación"
                >
                    <MapPin class="w-5 h-5" />
                </Button>
            </div>

            <!-- Superficie -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('superficie')"
                    class="p-2"
                    :class="{ 'text-blue-600': tieneSuperficieActiva }"
                    title="Superficie"
                >
                    <Maximize2 class="w-5 h-5" />
                </Button>
            </div>

            <!-- Adicionales -->
            <div class="text-center">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleCollapse; toggleSeccion('adicionales')"
                    class="p-2"
                    :class="{ 'text-blue-600': tieneAdicionalesActivas }"
                    title="Adicionales"
                >
                    <Calendar class="w-5 h-5" />
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
// Helper para verificar si hay características activas
const tieneCaracteristicasActivas = computed(() => {
    return filtrosLocales.value.ambientes_min !== undefined ||
           filtrosLocales.value.ambientes_max !== undefined ||
           filtrosLocales.value.habitaciones_min !== undefined ||
           filtrosLocales.value.habitaciones_max !== undefined ||
           filtrosLocales.value.banos_min !== undefined ||
           filtrosLocales.value.estacionamientos_min !== undefined ||
           filtrosLocales.value.estacionamientos_max !== undefined;
});

const tieneSuperficieActiva = computed(() => {
    return filtrosLocales.value.superficie_terreno_min !== undefined ||
           filtrosLocales.value.superficie_terreno_max !== undefined ||
           filtrosLocales.value.superficie_construida_min !== undefined ||
           filtrosLocales.value.superficie_construida_max !== undefined;
});

const tieneAdicionalesActivas = computed(() => {
    return filtrosLocales.value.antiguedad_min !== undefined ||
           filtrosLocales.value.antiguedad_max !== undefined;
});
</script>

<style scoped>
/* Estilos para el sidebar colapsable */
.h-\[calc\(100vh-80px\)\] {
    height: calc(100vh - 5rem);
}

/* Animaciones suaves */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Scrollbar personalizada */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgb(203 213 225);
    border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgb(75 85 99);
}

/* Hover effects */
.hover\:bg-gray-50:hover {
    background-color: rgb(249 250 251);
}

.dark .hover\:bg-gray-50:hover {
    background-color: rgb(55 65 81);
}

/* Asegurar que los checkboxes estén bien alineados */
input[type="checkbox"] {
    margin-top: 2px;
}

/* Responsive para móviles - sidebar se convierte en modal */
@media (max-width: 768px) {
    .h-\[calc\(100vh-80px\)\] {
        height: 100vh;
    }
}
</style>