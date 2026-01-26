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
import publicRoutes from '@/routes/public';

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
            'group overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-[1.02] cursor-pointer',
            cardSizeClasses
        ]"
        data-testid="property-card"
    >
        <!-- Imagen principal -->
        <div class="relative overflow-hidden" :class="imageHeightClasses">
            <img
                :src="getPropertyImage(propiedad.imagen_principal)"
                :alt="propiedad.name"
                :loading="lazy ? 'lazy' : 'eager'"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                :class="{ 'blur-sm': !propiedad.imagen_principal }"
            >

            <!-- Overlay para badges y acciones -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">

                <!-- Badge de operación y destacado -->
                <div class="absolute top-3 left-3 flex gap-2">
                    <Badge
                        :variant="getOperacionBadgeVariant(propiedad.operacion)"
                        class="bg-white/90 backdrop-blur-sm text-gray-900 font-semibold px-3 py-1"
                    >
                        {{ getOperacionLabel(propiedad.operacion) }}
                    </Badge>
                    <Badge
                        v-if="propiedad.featured"
                        variant="secondary"
                        class="bg-yellow-400/90 backdrop-blur-sm text-gray-900 font-semibold px-3 py-1"
                    >
                        ⭐ Destacado
                    </Badge>
                </div>

                <!-- Acciones rápidas -->
                <div class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <Button
                        v-if="showFavorite"
                        size="sm"
                        variant="secondary"
                        class="h-8 w-8 p-0 bg-white/80 backdrop-blur-sm hover:bg-white"
                        @click.stop="$emit('favorite', propiedad.id)"
                    >
                        <Heart class="w-4 h-4" />
                    </Button>
                    <Button
                        size="sm"
                        variant="secondary"
                        class="h-8 w-8 p-0 bg-white/80 backdrop-blur-sm hover:bg-white"
                        @click.stop="$emit('share', propiedad)"
                    >
                        <Eye class="w-4 h-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Contenido de la tarjeta -->
        <CardContent class="p-4 space-y-3">
            <!-- Código y precio -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-white truncate group-hover:text-blue-600 transition-colors">
                        {{ propiedad.name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Código: {{ propiedad.codigo_inmueble }}
                    </p>
                </div>
                <div class="text-right">
                    <div v-if="getMainPrice()" class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ getMainPrice()?.formatted }}
                    </div>
                    <div v-if="getSecondaryPrice()" class="text-sm font-semibold text-green-600 dark:text-green-400">
                        {{ getSecondaryPrice() }}
                    </div>
                    <div v-if="propiedad.operacion === 'alquiler'" class="text-xs text-gray-500">
                        /mes
                    </div>
                    <div v-if="!getMainPrice()" class="text-sm text-gray-400">
                        Precio no disponible
                    </div>
                </div>
            </div>

            <!-- Características principales -->
            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                <div v-if="propiedad.ambientes" class="flex items-center gap-1">
                    <Home class="w-4 h-4" />
                    <span>{{ propiedad.ambientes }} amb.</span>
                </div>
                <div v-if="propiedad.habitaciones" class="flex items-center gap-1">
                    <BedDouble class="w-4 h-4" />
                    <span>{{ propiedad.habitaciones }} hab.</span>
                </div>
                <div v-if="propiedad.banos" class="flex items-center gap-1">
                    <Bath class="w-4 h-4" />
                    <span>{{ propiedad.banos }} baños</span>
                </div>
                <div v-if="propiedad.cocheras" class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-bold">C</div>
                    <span>{{ propiedad.cocheras }} coch.</span>
                </div>
            </div>

            <!-- Superficie y ubicación -->
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <div v-if="propiedad.superficie_construida" class="flex items-center gap-1">
                    <Maximize2 class="w-4 h-4" />
                    <span>{{ propiedad.superficie_construida }}m² construidos</span>
                </div>
                <div v-if="propiedad.direccion" class="flex items-center gap-1">
                    <MapPin class="w-4 h-4" />
                    <span class="truncate">{{ propiedad.direccion }}</span>
                </div>
            </div>

            <!-- Descripción corta -->
            <p
                v-if="propiedad.descripcion"
                class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2"
            >
                {{ propiedad.descripcion }}
            </p>
        </CardContent>

        <!-- Footer con acciones -->
        <CardFooter class="p-4 pt-0 flex gap-2">
            <Button
                :as="Link"
                :href="publicRoutes.propiedad.show.url(propiedad.id)"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                size="sm"
            >
                Ver detalles
            </Button>
            <Button
                variant="outline"
                size="sm"
                @click.stop="$emit('share', propiedad)"
            >
                <Eye class="w-4 h-4" />
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