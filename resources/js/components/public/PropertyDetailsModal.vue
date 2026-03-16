<script setup lang="ts">
import { ref } from 'vue';
import {
    X,
    BedDouble,
    Bath,
    MapPin,
    DollarSign,
    Maximize2,
    Calendar,
    Share2,
    Heart,
    Phone,
    Mail,
    Check
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Property {
    id: number;
    name: string;
    codigo_inmueble: string;
    price_usd?: number | null;
    price_bob?: number | null;
    operacion: string;
    category?: string;
    category_id?: number | null;
    superficie_util?: number;
    superficie_construida?: number;
    ambientes?: number;
    habitaciones?: number;
    banos?: number;
    cocheras?: number;
    ano_construccion?: number;
    descripcion?: string;
    location?: {
        latitude: number;
        longitude: number;
        address?: string;
    };
}

const props = defineProps<{
    property: Property;
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const isFavorite = ref(false);

// Formatear precio
const formatPrice = (price: number | null, currency: string = 'USD') => {
    if (!price) return null;
    const symbol = currency === 'USD' ? '$' : 'Bs.';
    return `${symbol}${price.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
};

// Obtener etiqueta de operación
const getOperacionLabel = (operacion: string) => {
    const labels: Record<string, { text: string; color: string }> = {
        'venta': { text: 'Venta', color: 'bg-green-600' },
        'alquiler': { text: 'Alquiler', color: 'bg-blue-600' },
        'anticretico': { text: 'Anticrético', color: 'bg-red-600' }
    };
    return labels[operacion] || { text: operacion, color: 'bg-gray-600' };
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="emit('close')"
                @keydown.esc="emit('close')"
            >
                <!-- Modal Container -->
                <div
                    v-if="show"
                    class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <Badge :class="['text-white font-bold px-4 py-1.5', getOperacionLabel(property.operacion).color]">
                                {{ getOperacionLabel(property.operacion).text }}
                            </Badge>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white line-clamp-1">
                                {{ property.name }}
                            </h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-gray-600 hover:text-red-600 dark:text-gray-300"
                                @click="isFavorite = !isFavorite"
                            >
                                <Heart :class="['w-5 h-5', isFavorite ? 'fill-red-500 text-red-500' : '']" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                                @click="emit('close')"
                            >
                                <X class="w-6 h-6" />
                            </Button>
                        </div>
                    </div>

                    <!-- Content Scrollable -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- Details Section -->
                        <div class="p-6 space-y-6">
                            <!-- Code and Category -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-[#233C7A] dark:text-[#FAB90E] uppercase tracking-wider">
                                    Código: {{ property.codigo_inmueble }}
                                </p>
                                <p v-if="property.category" class="text-sm text-gray-600 dark:text-gray-400">
                                    Categoría: {{ property.category }}
                                </p>
                            </div>

                            <!-- Prices -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 space-y-3">
                                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    Precio
                                </h3>
                                <div v-if="property.price_usd" class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                        <DollarSign class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Precio USD</p>
                                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ formatPrice(property.price_usd, 'USD') }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="property.price_bob" class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                        <DollarSign class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Precio BOB</p>
                                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ formatPrice(property.price_bob, 'BOB') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Features Grid -->
                            <div class="grid grid-cols-2 gap-3">
                                <div v-if="property.ambientes" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <Home class="w-5 h-5 text-[#233C7A] dark:text-[#FAB90E]" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Ambientes</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.ambientes }}</p>
                                    </div>
                                </div>
                                <div v-if="property.habitaciones" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <BedDouble class="w-5 h-5 text-[#E0081D]" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Habitaciones</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.habitaciones }}</p>
                                    </div>
                                </div>
                                <div v-if="property.banos" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <Bath class="w-5 h-5 text-[#233C7A] dark:text-[#FAB90E]" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Baños</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.banos }}</p>
                                    </div>
                                </div>
                                <div v-if="property.cocheras" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <div class="w-5 h-5 rounded bg-[#FAB90E] flex items-center justify-center text-[10px] font-black text-[#212121]">C</div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Cocheras</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.cocheras }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Surface Area -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide flex items-center gap-2">
                                    <Maximize2 class="w-4 h-4" />
                                    Superficie
                                </h4>
                                <div class="flex gap-3">
                                    <div v-if="property.superficie_construida" class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Construida</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.superficie_construida }} m²</p>
                                    </div>
                                    <div v-if="property.superficie_util" class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Útil</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ property.superficie_util }} m²</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Year Built -->
                            <div v-if="property.ano_construccion" class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <Calendar class="w-4 h-4" />
                                <span>Año de construcción: <strong class="text-gray-900 dark:text-white">{{ property.ano_construccion }}</strong></span>
                            </div>

                            <!-- Description -->
                            <div v-if="property.descripcion" class="flex items-start gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="w-5 h-5 flex items-center justify-center bg-[#E0081D] rounded-full flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012 2 12 6.01l6-6-6.01a2 2 0 012-2 6.01l-6 6a2 2 0 012 6 01"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Descripción</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ property.descripcion }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between gap-4 p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Share2 class="w-4 h-4" />
                                    Compartir
                                </Button>
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Heart class="w-4 h-4" />
                                    Guardar
                                </Button>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <Phone class="w-4 h-4" />
                                    Llamar
                                </Button>
                                <Button size="sm" class="gap-2 bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] hover:from-[#E0081D] hover:to-[#233C7A]">
                                    <Mail class="w-4 h-4" />
                                    Contactar
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: #374151;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
