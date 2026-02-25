<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Home,
    Building,
    DollarSign,
    Key,
    FileText,
    Check,
    Search,
    ArrowRight
} from 'lucide-vue-next';

interface Props {
    options?: {
        categorias: Record<number, string>;
        operaciones: Record<string, string>;
    };
}

const props = withDefaults(defineProps<Props>(), {
    options: () => ({
        categorias: {},
        operaciones: {
            'venta': 'Venta',
            'alquiler': 'Alquiler',
            'anticretico': 'Anticrético'
        }
    })
});

// Estado local - Igual que el mapa: number para categoría
const categoriaSeleccionada = ref<number | null>(null);
const operacionSeleccionada = ref<string | null>(null);

// Filtros adicionales (desactivados - solo búsqueda por categoría y operación)

const operacionesDisponibles = [
    { value: 'venta', label: 'Venta', icon: DollarSign },
    { value: 'alquiler', label: 'Alquiler', icon: Key },
    { value: 'anticretico', label: 'Anticrético', icon: FileText },
];

// Validar que se haya seleccionado al menos una opción
const puedeBuscar = computed(() => {
    return categoriaSeleccionada.value !== null || operacionSeleccionada.value !== null;
});

const nombreCategoriaSeleccionada = computed(() => {
    if (!categoriaSeleccionada.value) return 'Sin categoría';
    return props.options.categorias[categoriaSeleccionada.value] || 'Sin categoría';
});

const nombreOperacionSeleccionada = computed(() => {
    if (!operacionSeleccionada.value) return 'Sin operación';
    return operacionesDisponibles.find(op => op.value === operacionSeleccionada.value)?.label || 'Sin operación';
});

// Métodos
const selectCategoria = (categoryId: number | null) => {
    categoriaSeleccionada.value = categoryId;
};

const selectOperacion = (operacion: string | null) => {
    operacionSeleccionada.value = operacion;
};

const buscarPropiedades = () => {
    if (!puedeBuscar.value) {
        alert('Por favor, selecciona al menos una categoría o tipo de operación');
        return;
    }

    const params: Record<string, any> = {};

    if (categoriaSeleccionada.value !== null) {
        params.categoria = categoriaSeleccionada.value;
    }

    if (operacionSeleccionada.value !== null) {
        params.operacion = operacionSeleccionada.value;
    }

    console.log('Filtros enviados al mapa:', params);

    // Redirigir al MAPA INTERACTIVO con los filtros aplicados
    router.get('/mapa-propiedades', params);
};
</script>

<template>
    <div class="max-w-4xl w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <!-- Header con gradiente mejorado -->
        <div class="bg-gradient-to-r from-[#233C7A] via-[#2a4a94] to-[#233C7A] text-white p-8 relative overflow-hidden">
            <!-- Decoración de fondo -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-5">
                    <div class="w-14 h-14 bg-gradient-to-br from-[#FAB90E] to-[#f0a908] rounded-2xl flex items-center justify-center shadow-xl">
                        <Search :size="32" class="text-[#233C7A]" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Encuentra tu Propiedad</h1>
                        <p class="text-blue-100 text-base mt-1">Buscador profesional de propiedades en el mapa</p>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <p class="text-sm text-blue-50 flex items-center gap-2">
                        <span class="w-2 h-2 bg-[#FAB90E] rounded-full animate-pulse"></span>
                        Selecciona al menos una categoría o tipo de operación para ver el mapa
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="p-8 space-y-8">
            <!-- Filtros principales -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Categorías -->
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-[#233C7A] to-[#1a2e5f] rounded-lg flex items-center justify-center">
                            <Building :size="18" class="text-white" />
                        </div>
                        Categoría
                        <span class="text-sm font-normal text-gray-500">(opcional)</span>
                    </h2>
                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                        <button
                            v-for="(nombre, id) in options.categorias"
                            :key="id"
                            @click="selectCategoria(parseInt(id as string))"
                            :class="[
                                'w-full text-left px-5 py-4 rounded-xl border-2 transition-colors duration-200 flex items-center justify-between',
                                categoriaSeleccionada === parseInt(id as string)
                                    ? 'border-[#233C7A] bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 shadow-md'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-[#233C7A] dark:hover:border-blue-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ]"
                        >
                            <span :class="[
                                'font-semibold transition-colors duration-200',
                                categoriaSeleccionada === parseInt(id as string)
                                    ? 'text-[#233C7A] dark:text-blue-300'
                                    : 'text-gray-700 dark:text-gray-200 group-hover:text-[#233C7A] dark:group-hover:text-blue-300'
                            ]">{{ nombre }}</span>
                            <div v-if="categoriaSeleccionada === parseInt(id as string)" class="w-6 h-6 bg-[#233C7A] rounded-full flex items-center justify-center">
                                <Check :size="14" class="text-white" />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Operaciones -->
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                            <DollarSign :size="18" class="text-white" />
                        </div>
                        Tipo de Operación
                        <span class="text-sm font-normal text-gray-500">(opcional)</span>
                    </h2>
                    <div class="space-y-3">
                        <button
                            v-for="op in operacionesDisponibles"
                            :key="op.value"
                            @click="selectOperacion(op.value)"
                            :class="[
                                'w-full text-left px-5 py-4 rounded-xl border-2 transition-colors duration-200 flex items-center justify-between',
                                operacionSeleccionada === op.value
                                    ? 'border-green-500 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 shadow-md'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-green-500 dark:hover:border-green-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <component :is="op.icon" :size="20" />
                                <span :class="[
                                    'font-semibold transition-colors duration-200',
                                    operacionSeleccionada === op.value
                                        ? 'text-green-600 dark:text-green-300'
                                        : 'text-gray-700 dark:text-gray-200'
                                ]">{{ op.label }}</span>
                            </div>
                            <div v-if="operacionSeleccionada === op.value" class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                <Check :size="14" class="text-white" />
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen de selección -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700/50 dark:to-gray-800/50 rounded-2xl p-5 border-2 border-blue-200 dark:border-gray-600">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <p class="font-bold mb-3 text-center text-base">Tu selección:</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-full border-2 border-gray-200 dark:border-gray-600 font-semibold shadow-sm">
                            {{ nombreCategoriaSeleccionada }}
                        </span>
                        <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-full border-2 border-gray-200 dark:border-gray-600 font-semibold shadow-sm">
                            {{ nombreOperacionSeleccionada }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Botón de acción -->
            <button
                @click="buscarPropiedades"
                :class="[
                    'w-full py-5 rounded-2xl font-bold text-xl transition-all shadow-xl flex items-center justify-center gap-3',
                    puedeBuscar
                        ? 'bg-gradient-to-r from-[#233C7A] via-[#2a4a94] to-[#233C7A] text-white hover:from-[#1a2e5f] hover:via-[#233C7A] hover:to-[#1a2e5f] hover:shadow-2xl'
                        : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                ]"
                :disabled="!puedeBuscar"
            >
                <Search :size="28" />
                Ver Mapa con Propiedades
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Transiciones suaves - Sin transform */
button {
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

button:active:not(:disabled) {
    transform: scale(0.98);
}

/* Focus visible para accesibilidad */
button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 3px solid #233C7A;
    outline-offset: 2px;
}

/* Estilos para inputs y selects */
input[type="number"],
select {
    transition: all 0.2s ease;
}

input[type="number"]:hover,
select:hover {
    border-color: #233C7A;
    box-shadow: 0 0 0 3px rgba(35, 60, 122, 0.1);
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 0.6;
}

/* Scrollbar personalizado */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #233C7A, #1a2e5f);
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #2a4a94, #233C7A);
}

/* Animación suave para el pulso */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Botón principal con efectos mejorados */
button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

/* Select mejorado */
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

select::-ms-expand {
    display: none;
}
</style>
