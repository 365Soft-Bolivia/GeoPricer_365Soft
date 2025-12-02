<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    Filter,
    X,
    MapPin,
    Home,
    DollarSign,
    Calendar,
    SlidersHorizontal,
    ChevronDown,
    RotateCcw
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger
} from '@/components/ui/collapsible';

interface Props {
    filtros?: {
        busqueda?: string;
        categoria?: string;
        operacion?: string;
        rango_precio?: string;
        ubicacion?: string;
        codigo?: string;
        ambientes_min?: number;
        ambientes_max?: number;
        habitaciones_min?: number;
        habitaciones_max?: number;
        banos_min?: number;
        superficie_min?: number;
        superficie_max?: number;
        ordenar_por?: string;
        orden?: string;
    };
    options?: {
        categorias: Record<string, string>;
        operaciones: Record<string, string>;
        rangos_precios: Record<string, string>;
        ubicaciones: string[];
        opciones_ordenamiento: Record<string, string>;
    };
    loading?: boolean;
    showAdvanced?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    showAdvanced: false
});

const emit = defineEmits<{
    filter: [filtros: Props['filtros']];
    reset: [];
    search: [];
}>();

// Estado local para filtros
const filtrosLocales = ref({
    busqueda: props.filtros?.busqueda || '',
    categoria: props.filtros?.categoria || '',
    operacion: props.filtros?.operacion || '',
    rango_precio: props.filtros?.rango_precio || '',
    ubicacion: props.filtros?.ubicacion || '',
    codigo: props.filtros?.codigo || '',
    ambientes_min: props.filtros?.ambientes_min || undefined,
    ambientes_max: props.filtros?.ambientes_max || undefined,
    habitaciones_min: props.filtros?.habitaciones_min || undefined,
    habitaciones_max: props.filtros?.habitaciones_max || undefined,
    banos_min: props.filtros?.banos_min || undefined,
    superficie_min: props.filtros?.superficie_min || undefined,
    superficie_max: props.filtros?.superficie_max || undefined,
    ordenar_por: props.filtros?.ordenar_por || 'created_at',
    orden: props.filtros?.orden || 'desc'
});

const advancedOpen = ref(props.showAdvanced);
const searchInput = ref<HTMLInputElement>();

// Computed properties
const filtrosActivos = computed(() => {
    const activos: string[] = [];
    const f = filtrosLocales.value;

    if (f.busqueda) activos.push(`"${f.busqueda}"`);
    if (f.categoria) activos.push(props.options?.categorias[f.categoria] || f.categoria);
    if (f.operacion) activos.push(props.options?.operaciones[f.operacion] || f.operacion);
    if (f.rango_precio) activos.push(props.options?.rangos_precios[f.rango_precio] || f.rango_precio);
    if (f.ubicacion) activos.push(f.ubicacion);
    if (f.codigo) activos.push(`Código: ${f.codigo}`);
    if (f.ambientes_min || f.ambientes_max) {
        const min = f.ambientes_min || '1';
        const max = f.ambientes_max || '+';
        activos.push(`${min}-${max} amb.`);
    }
    if (f.habitaciones_min || f.habitaciones_max) {
        const min = f.habitaciones_min || '1';
        const max = f.habitaciones_max || '+';
        activos.push(`${min}-${max} hab.`);
    }
    if (f.banos_min) activos.push(`${f.banos_min}+ baños`);
    if (f.superficie_min || f.superficie_max) {
        const min = f.superficie_min || '0';
        const max = f.superficie_max || '+';
        activos.push(`${min}-${max}m²`);
    }

    return activos;
});

const tieneFiltrosAvanzados = computed(() => {
    return filtrosLocales.value.ambientes_min ||
           filtrosLocales.value.ambientes_max ||
           filtrosLocales.value.habitaciones_min ||
           filtrosLocales.value.habitaciones_max ||
           filtrosLocales.value.banos_min ||
           filtrosLocales.value.superficie_min ||
           filtrosLocales.value.superficie_max;
});

// Métodos
const aplicarFiltros = () => {
    const filtrosLimpios = Object.fromEntries(
        Object.entries(filtrosLocales.value).filter(([_, valor]) =>
            valor !== undefined && valor !== '' && valor !== null
        )
    );

    emit('filter', filtrosLimpios);
};

const limpiarFiltros = () => {
    Object.keys(filtrosLocales.value).forEach(key => {
        if (key === 'ordenar_por' || key === 'orden') {
            filtrosLocales.value[key] = key === 'ordenar_por' ? 'created_at' : 'desc';
        } else if (key.includes('_min') || key.includes('_max')) {
            filtrosLocales.value[key] = undefined;
        } else {
            filtrosLocales.value[key] = '';
        }
    });

    emit('reset');
    emit('filter', {});
};

const removerFiltroActivo = (filtro: string) => {
    // Lógica para remover un filtro específico basado en su texto
    if (filtro.startsWith('"') && filtro.endsWith('"')) {
        filtrosLocales.value.busqueda = '';
    } else if (props.options?.categorias && Object.values(props.options.categorias).includes(filtro)) {
        const key = Object.keys(props.options.categorias).find(k => props.options.categorias[k] === filtro);
        if (key) filtrosLocales.value.categoria = '';
    } else if (props.options?.operaciones && Object.values(props.options.operaciones).includes(filtro)) {
        const key = Object.keys(props.options.operaciones).find(k => props.options.operaciones[k] === filtro);
        if (key) filtrosLocales.value.operacion = '';
    } else if (props.options?.rangos_precios && Object.values(props.options.rangos_precios).includes(filtro)) {
        const key = Object.keys(props.options.rangos_precios).find(k => props.options.rangos_precios[k] === filtro);
        if (key) filtrosLocales.value.rango_precio = '';
    } else if (props.options?.ubicaciones?.includes(filtro)) {
        filtrosLocales.value.ubicacion = '';
    } else if (filtro.startsWith('Código:')) {
        filtrosLocales.value.codigo = '';
    } else if (filtro.includes('amb.')) {
        filtrosLocales.value.ambientes_min = undefined;
        filtrosLocales.value.ambientes_max = undefined;
    } else if (filtro.includes('hab.')) {
        filtrosLocales.value.habitaciones_min = undefined;
        filtrosLocales.value.habitaciones_max = undefined;
    } else if (filtro.includes('baños')) {
        filtrosLocales.value.banos_min = undefined;
    } else if (filtro.includes('m²')) {
        filtrosLocales.value.superficie_min = undefined;
        filtrosLocales.value.superficie_max = undefined;
    }

    aplicarFiltros();
};

const handleSearch = (event?: KeyboardEvent) => {
    if (event?.key === 'Enter') {
        aplicarFiltros();
    }
};

// Watch para emitir cambios
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
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border p-6 space-y-6">
        <!-- Header de filtros -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Filter class="w-5 h-5 text-gray-500" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Filtros de Búsqueda
                </h3>
                <Badge
                    v-if="filtrosActivos.length > 0"
                    variant="secondary"
                    class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ filtrosActivos.length }} activos
                </Badge>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="filtrosActivos.length > 0"
                    variant="ghost"
                    size="sm"
                    @click="limpiarFiltros"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <RotateCcw class="w-4 h-4 mr-1" />
                    Limpiar
                </Button>
                <Collapsible v-model:open="advancedOpen">
                    <CollapsibleTrigger as-child>
                        <Button variant="ghost" size="sm">
                            <SlidersHorizontal class="w-4 h-4 mr-1" />
                            Avanzado
                            <ChevronDown
                                class="w-4 h-4 ml-1 transition-transform"
                                :class="{ 'rotate-180': advancedOpen }"
                            />
                        </Button>
                    </CollapsibleTrigger>
                </Collapsible>
            </div>
        </div>

        <!-- Filtros principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Búsqueda general -->
            <div class="relative">
                <Input
                    ref="searchInput"
                    v-model="filtrosLocales.busqueda"
                    placeholder="Buscar propiedades..."
                    class="pl-10"
                    @keyup="handleSearch"
                >
                    <template #prefix>
                        <Search class="w-4 h-4 text-gray-400" />
                    </template>
                </Input>
            </div>

            <!-- Categoría -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="w-full justify-between">
                        <Home class="w-4 h-4 mr-2" />
                        {{
                            filtrosLocales.categoria
                                ? (options?.categorias[filtrosLocales.categoria] || filtrosLocales.categoria)
                                : 'Categoría'
                        }}
                        <ChevronDown class="w-4 h-4 ml-2" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56 max-h-60 overflow-y-auto">
                    <DropdownMenuItem @click="filtrosLocales.categoria = ''">
                        Todas las categorías
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="(nombre, id) in options?.categorias"
                        :key="id"
                        @click="filtrosLocales.categoria = id"
                    >
                        {{ nombre }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Operación -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="w-full justify-between">
                        <DollarSign class="w-4 h-4 mr-2" />
                        {{
                            filtrosLocales.operacion
                                ? (options?.operaciones[filtrosLocales.operacion] || filtrosLocales.operacion)
                                : 'Operación'
                        }}
                        <ChevronDown class="w-4 h-4 ml-2" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-48">
                    <DropdownMenuItem @click="filtrosLocales.operacion = ''">
                        Todas las operaciones
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="(nombre, key) in options?.operaciones"
                        :key="key"
                        @click="filtrosLocales.operacion = key"
                    >
                        {{ nombre }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Rango de precios -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="w-full justify-between">
                        <DollarSign class="w-4 h-4 mr-2" />
                        {{
                            filtrosLocales.rango_precio
                                ? (options?.rangos_precios[filtrosLocales.rango_precio] || filtrosLocales.rango_precio)
                                : 'Rango de precio'
                        }}
                        <ChevronDown class="w-4 h-4 ml-2" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56">
                    <DropdownMenuItem @click="filtrosLocales.rango_precio = ''">
                        Cualquier precio
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="(nombre, key) in options?.rangos_precios"
                        :key="key"
                        @click="filtrosLocales.rango_precio = key"
                    >
                        {{ nombre }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Filtros adicionales -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Ubicación -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="w-full justify-between">
                        <MapPin class="w-4 h-4 mr-2" />
                        {{ filtrosLocales.ubicacion || 'Ubicación' }}
                        <ChevronDown class="w-4 h-4 ml-2" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-64 max-h-60 overflow-y-auto">
                    <DropdownMenuItem @click="filtrosLocales.ubicacion = ''">
                        Todas las ubicaciones
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="ubicacion in options?.ubicaciones"
                        :key="ubicacion"
                        @click="filtrosLocales.ubicacion = ubicacion"
                    >
                        {{ ubicacion }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Código de propiedad -->
            <Input
                v-model="filtrosLocales.codigo"
                placeholder="Código de propiedad (ej: PRO001)"
                @keyup="handleSearch"
            />
        </div>

        <!-- Filtros avanzados -->
        <Collapsible v-model:open="advancedOpen">
            <CollapsibleContent class="space-y-4 pt-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Ambientes -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Ambientes
                        </label>
                        <div class="flex gap-2">
                            <Input
                                v-model.number="filtrosLocales.ambientes_min"
                                type="number"
                                placeholder="Mín"
                                min="0"
                            />
                            <Input
                                v-model.number="filtrosLocales.ambientes_max"
                                type="number"
                                placeholder="Máx"
                                min="0"
                            />
                        </div>
                    </div>

                    <!-- Habitaciones -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Habitaciones
                        </label>
                        <div class="flex gap-2">
                            <Input
                                v-model.number="filtrosLocales.habitaciones_min"
                                type="number"
                                placeholder="Mín"
                                min="0"
                            />
                            <Input
                                v-model.number="filtrosLocales.habitaciones_max"
                                type="number"
                                placeholder="Máx"
                                min="0"
                            />
                        </div>
                    </div>

                    <!-- Baños -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Baños
                        </label>
                        <Input
                            v-model.number="filtrosLocales.banos_min"
                            type="number"
                            placeholder="Mínimo"
                            min="0"
                        />
                    </div>

                    <!-- Superficie -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Superficie (m²)
                        </label>
                        <div class="flex gap-2">
                            <Input
                                v-model.number="filtrosLocales.superficie_min"
                                type="number"
                                placeholder="Mín"
                                min="0"
                            />
                            <Input
                                v-model.number="filtrosLocales.superficie_max"
                                type="number"
                                placeholder="Máx"
                                min="0"
                            />
                        </div>
                    </div>

                    <!-- Ordenamiento -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Ordenar por
                        </label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    {{
                                        filtrosLocales.ordenar_por
                                            ? (options?.opciones_ordenamiento[filtrosLocales.ordenar_por] || filtrosLocales.ordenar_por)
                                            : 'Ordenar por'
                                    }}
                                    <ChevronDown class="w-4 h-4 ml-2" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56">
                                <DropdownMenuItem
                                    v-for="(nombre, key) in options?.opciones_ordenamiento"
                                    :key="key"
                                    @click="filtrosLocales.ordenar_por = key"
                                >
                                    {{ nombre }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Dirección de ordenamiento -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Dirección
                        </label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    {{ filtrosLocales.orden === 'asc' ? 'Ascendente' : 'Descendente' }}
                                    <ChevronDown class="w-4 h-4 ml-2" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-40">
                                <DropdownMenuItem @click="filtrosLocales.orden = 'asc'">
                                    Ascendente
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="filtrosLocales.orden = 'desc'">
                                    Descendente
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <!-- Filtros activos -->
        <div v-if="filtrosActivos.length > 0" class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Filtros activos:
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <Badge
                    v-for="(filtro, index) in filtrosActivos"
                    :key="index"
                    variant="secondary"
                    class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors pr-1"
                    @click="removerFiltroActivo(filtro)"
                >
                    {{ filtro }}
                    <X class="w-3 h-3 ml-1" />
                </Badge>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-3 pt-4 border-t">
            <Button
                @click="aplicarFiltros"
                :disabled="loading"
                class="flex-1"
            >
                <Search class="w-4 h-4 mr-2" />
                {{ loading ? 'Buscando...' : 'Aplicar filtros' }}
            </Button>

            <Button
                variant="outline"
                @click="limpiarFiltros"
                :disabled="loading"
            >
                <RotateCcw class="w-4 h-4 mr-2" />
                Limpiar todo
            </Button>
        </div>
    </div>
</template>

<style scoped>
/* Aseguramos que los dropdowns tengan buen z-index */
[data-radix-dropdown-menu-content] {
    z-index: 50;
}
</style>