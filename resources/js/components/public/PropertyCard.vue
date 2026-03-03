<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    BedDouble,
    Bath,
    Home,
    MapPin,
    DollarSign,
    Heart,
    Eye,
    Calendar,
    Maximize2
} from 'lucide-vue-next';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import publicRoutes from '@/routes/public/index.ts';

interface Props {
    propiedad: {
        id: number;
        name: string;
        codigo_inmueble: string;
        price_usd?: number | null;
        price_bob?: number | null;
        operacion: string;
        categoria?: string;
        ambientes?: number;
        habitaciones?: number;
        banos?: number;
        cocheras?: number;
        superficie_construida?: number;
        superficie_util?: number;
        direccion?: string;
        imagen_principal?: string | null;
        descripcion?: string;
        antiguedad?: number;
        is_public?: boolean;
        created_at?: string;
        updated_at?: string;
        featured?: boolean;
    };
    lazy?: boolean;
    showFavorite?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    lazy: true,
    showFavorite: true,
    size: 'md'
});

const emits = defineEmits<{
    favorite: [id: number];
    share: [propiedad: Props['propiedad']];
}>();

// Computed properties
const formatPrice = (price: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const getMainPrice = () => {
    if (props.propiedad.price_usd) {
        return {
            amount: props.propiedad.price_usd,
            currency: 'USD',
            formatted: formatPrice(props.propiedad.price_usd, 'USD')
        };
    }
    if (props.propiedad.price_bob) {
        return {
            amount: props.propiedad.price_bob,
            currency: 'BOB',
            formatted: formatPrice(props.propiedad.price_bob, 'BOB')
        };
    }
    return null;
};

const getSecondaryPrice = () => {
    if (props.propiedad.price_bob && props.propiedad.price_usd) {
        return formatPrice(props.propiedad.price_bob, 'BOB');
    }
    return null;
};

const getOperacionBadgeVariant = (operacion: string) => {
    switch (operacion.toLowerCase()) {
        case 'venta':
            return 'default';
        case 'alquiler':
            return 'secondary';
        case 'anticretico':
            return 'outline';
        default:
            return 'default';
    }
};

const getOperacionLabel = (operacion: string) => {
    const labels: Record<string, string> = {
        'venta': 'Venta',
        'alquiler': 'Alquiler',
        'anticretico': 'Anticrético'
    };
    return labels[operacion.toLowerCase()] || operacion;
};

const getPropertyImage = (imagePath: string | null) => {
    if (!imagePath) {
        return 'data:image/svg+xml,%3Csvg width="400" height="300" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="400" height="300" fill="%23f3f4f6"/%3E%3Cg fill="%239ca3af"%3E%3Crect x="150" y="100" width="100" height="80" rx="4"/%3E%3Crect x="120" y="190" width="40" height="30" rx="2"/%3E%3Crect x="170" y="190" width="40" height="30" rx="2"/%3E%3Crect x="220" y="190" width="40" height="30" rx="2"/%3E%3Cpolygon points="200,60 220,100 180,100"/%3E%3C/g%3E%3Ctext x="200" y="250" text-anchor="middle" fill="%236b7280" font-family="Arial" font-size="14"%3ESin imagen%3C/text%3E%3C/svg%3E';
    }
    return `/storage/${imagePath}`;
};

const cardSizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'max-w-sm';
        case 'lg':
            return 'max-w-lg';
        default:
            return 'max-w-md';
    }
});

const imageHeightClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-40';
        case 'lg':
            return 'h-64';
        default:
            return 'h-48';
    }
});
</script>

<template>
    <Card
        :class="[
            'group overflow-hidden transition-all duration-500 hover:shadow-2xl hover:scale-[1.03] cursor-pointer border-2 border-transparent hover:border-[#FAB90E] relative',
            cardSizeClasses
        ]"
        data-testid="property-card"
    >
        <!-- Banner superior decorativo -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#233C7A] via-[#FAB90E] to-[#E0081D] z-20"></div>

        <!-- Imagen principal -->
        <div class="relative overflow-hidden" :class="imageHeightClasses">
            <img
                :src="getPropertyImage(propiedad.imagen_principal)"
                :alt="propiedad.name"
                :loading="lazy ? 'lazy' : 'eager'"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-115"
                :class="{ 'blur-sm': !propiedad.imagen_principal }"
            >

            <!-- Gradiente overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#212121]/80 via-transparent to-transparent opacity-60"></div>

            <!-- Badges prominentes -->
            <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
                <Badge
                    :class="[
                        'font-bold px-4 py-1.5 shadow-lg backdrop-blur-sm border-2 border-white/30',
                        propiedad.operacion === 'venta' ? 'bg-[#10b981] text-white' :
                        propiedad.operacion === 'alquiler' ? 'bg-[#233C7A] text-white' :
                        'bg-[#E0081D] text-white'
                    ]"
                >
                    {{ getOperacionLabel(propiedad.operacion) }}
                </Badge>
                <Badge
                    v-if="propiedad.featured"
                    class="bg-[#FAB90E] text-[#212121] font-bold px-4 py-1.5 shadow-lg animate-pulse border-2 border-white/30"
                >
                    ⭐ DESTACADO
                </Badge>
            </div>

            <!-- Badge de precio flotante -->
            <div v-if="getMainPrice()" class="absolute bottom-3 right-3 z-10">
                <div class="bg-[#FAB90E]/95 backdrop-blur-md px-4 py-2 rounded-xl shadow-2xl border-2 border-white/30">
                    <p class="text-xs font-bold text-[#212121]/70 uppercase tracking-wide" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Precio</p>
                    <p class="text-xl font-black text-[#212121]" style="font-family: 'Montserrat', sans-serif; font-weight: 800;">{{ getMainPrice()?.formatted }}</p>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="absolute top-3 right-3 flex gap-2 z-10">
                <Button
                    v-if="showFavorite"
                    size="sm"
                    class="h-9 w-9 p-0 bg-white/90 backdrop-blur-md hover:bg-[#E0081D] hover:text-white border-2 border-white/50 transition-all shadow-lg group-hover:scale-110"
                    @click.stop="$emit('favorite', propiedad.id)"
                >
                    <Heart class="w-4 h-4" />
                </Button>
            </div>
        </div>

        <!-- Contenido de la tarjeta -->
        <CardContent class="p-5 space-y-4">
            <!-- Título y código -->
            <div>
                <h3 class="font-bold text-xl text-[#212121] dark:text-white truncate mb-1 group-hover:text-[#233C7A] dark:group-hover:text-[#FAB90E] transition-colors" style="font-family: 'Montserrat', sans-serif; font-weight: 700;">
                    {{ propiedad.name }}
                </h3>
                <p class="text-xs font-semibold text-[#233C7A] dark:text-[#FAB90E] uppercase tracking-wider" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">
                    Código: {{ propiedad.codigo_inmueble }}
                </p>
            </div>

            <!-- Características principales -->
            <div class="grid grid-cols-2 gap-3">
                <div v-if="propiedad.ambientes" class="flex items-center gap-2 bg-[#F5F5F5] dark:bg-[#233C7A]/20 px-3 py-2 rounded-lg">
                    <Home class="w-4 h-4 text-[#233C7A] dark:text-[#FAB90E]" />
                    <span class="text-sm font-semibold text-[#212121] dark:text-white">{{ propiedad.ambientes }} amb.</span>
                </div>
                <div v-if="propiedad.habitaciones" class="flex items-center gap-2 bg-[#F5F5F5] dark:bg-[#233C7A]/20 px-3 py-2 rounded-lg">
                    <BedDouble class="w-4 h-4 text-[#E0081D]" />
                    <span class="text-sm font-semibold text-[#212121] dark:text-white">{{ propiedad.habitaciones }} hab.</span>
                </div>
                <div v-if="propiedad.banos" class="flex items-center gap-2 bg-[#F5F5F5] dark:bg-[#233C7A]/20 px-3 py-2 rounded-lg">
                    <Bath class="w-4 h-4 text-[#233C7A] dark:text-[#FAB90E]" />
                    <span class="text-sm font-semibold text-[#212121] dark:text-white">{{ propiedad.banos }} baños</span>
                </div>
                <div v-if="propiedad.cocheras" class="flex items-center gap-2 bg-[#F5F5F5] dark:bg-[#233C7A]/20 px-3 py-2 rounded-lg">
                    <div class="w-4 h-4 rounded bg-[#FAB90E] flex items-center justify-center text-[10px] font-black text-[#212121]">C</div>
                    <span class="text-sm font-semibold text-[#212121] dark:text-white">{{ propiedad.cocheras }} coch.</span>
                </div>
            </div>

            <!-- Superficie y ubicación -->
            <div class="space-y-2">
                <div v-if="propiedad.superficie_construida" class="flex items-center gap-2 text-sm text-[#212121]/80 dark:text-white/80">
                    <Maximize2 class="w-4 h-4 text-[#233C7A]" />
                    <span class="font-medium">{{ propiedad.superficie_construida }}m² construidos</span>
                </div>
                <div v-if="propiedad.direccion" class="flex items-center gap-2 text-sm text-[#212121]/80 dark:text-white/80">
                    <MapPin class="w-4 h-4 text-[#E0081D]" />
                    <span class="truncate font-medium">{{ propiedad.direccion }}</span>
                </div>
            </div>
        </CardContent>

        <!-- Footer con CTA -->
        <CardFooter class="p-5 pt-0">
            <Button
                :as="Link"
                :href="publicRoutes.propiedad.show.url(propiedad.id)"
                class="w-full bg-gradient-to-r from-[#233C7A] to-[#1e2d4d] hover:from-[#E0081D] hover:to-[#233C7A] text-white font-bold py-3 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02]" style="font-family: 'Montserrat', sans-serif; font-weight: 700;"
                size="default"
            >
                <span class="flex items-center justify-center gap-2">
                    Ver Detalles
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </span>
            </Button>
        </CardFooter>
    </Card>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Aseguramos que la imagen mantenga proporciones */
img {
    aspect-ratio: 4/3;
    object-fit: cover;
}
</style>