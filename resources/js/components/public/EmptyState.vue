<script setup lang="ts">
import {
    Home,
    Search,
    Filter,
    MapPin,
    RefreshCw,
    Plus
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { ref, computed, onMounted, watch } from 'vue';

interface Props {
    type?: 'search' | 'filter' | 'general' | 'error';
    title?: string;
    description?: string;
    actionText?: string;
    showAction?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'general',
    showAction: true,
    loading: false
});

const emit = defineEmits<{
    action: [];
    retry: [];
}>();

const content = computed(() => {
    switch (props.type) {
        case 'search':
            return {
                icon: Search,
                title: props.title || 'No se encontraron propiedades',
                description: props.description || 'No hemos encontrado propiedades que coincidan con tu búsqueda. Intenta con otros términos o filtros.',
                actionText: props.actionText || 'Limpiar búsqueda'
            };
        case 'filter':
            return {
                icon: Filter,
                title: props.title || 'Sin resultados con estos filtros',
                description: props.description || 'No hay propiedades que coincidan con los filtros seleccionados. Intenta ajustar los criterios.',
                actionText: props.actionText || 'Ajustar filtros'
            };
        case 'error':
            return {
                icon: RefreshCw,
                title: props.title || 'Error al cargar propiedades',
                description: props.description || 'Ha ocurrido un error al cargar las propiedades. Por favor, intenta nuevamente.',
                actionText: props.actionText || 'Reintentar'
            };
        default:
            return {
                icon: Home,
                title: props.title || 'No hay propiedades disponibles',
                description: props.description || 'Actualmente no hay propiedades disponibles. Vuelve a revisar pronto.',
                actionText: props.actionText || 'Recargar página'
            };
    }
});
</script>

<template>
    <Card class="w-full max-w-2xl mx-auto">
        <CardHeader class="text-center pb-4">
            <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mb-4">
                <component
                    :is="content.icon"
                    class="w-8 h-8 text-blue-600 dark:text-blue-400"
                    :class="{ 'animate-spin': loading && content.icon.name === 'RefreshCw' }"
                />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ content.title }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-center max-w-md mx-auto">
                {{ content.description }}
            </p>
        </CardHeader>

        <CardContent class="text-center pt-0">
            <!-- Acciones principales -->
            <div v-if="showAction" class="space-y-3">
                <Button
                    @click="type === 'error' ? $emit('retry') : $emit('action')"
                    :disabled="loading"
                    class="w-full sm:w-auto"
                >
                    <component
                        :is="type === 'error' ? RefreshCw : (type === 'search' ? Search : Plus)"
                        class="w-4 h-4 mr-2"
                        :class="{ 'animate-spin': loading && type === 'error' }"
                    />
                    {{ content.actionText }}
                </Button>

                <!-- Acciones secundarias -->
                <div class="flex flex-col sm:flex-row gap-2 justify-center text-sm text-gray-600 dark:text-gray-400">
                    <Button
                        v-if="type === 'search'"
                        variant="link"
                        @click="$emit('action')"
                        :disabled="loading"
                        class="p-0 h-auto"
                    >
                        <Search class="w-4 h-4 mr-1" />
                        Intenta otra búsqueda
                    </Button>

                    <Button
                        v-if="type === 'filter'"
                        variant="link"
                        @click="$emit('action')"
                        :disabled="loading"
                        class="p-0 h-auto"
                    >
                        <Filter class="w-4 h-4 mr-1" />
                        Modificar filtros
                    </Button>

                    <Button
                        v-if="type === 'general'"
                        variant="link"
                        as-child
                        class="p-0 h-auto"
                    >
                        <a href="/contacto">
                            <MapPin class="w-4 h-4 mr-1" />
                            Contactar a un asesor
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Sugerencias cuando no hay resultados -->
            <div v-if="type === 'search' || type === 'filter'" class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Sugerencias:</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 text-left">
                    <li v-if="type === 'search'">• Verifica la ortografía de los términos de búsqueda</li>
                    <li v-if="type === 'search'">• Usa palabras más generales (ej: "departamento" en lugar de "1D al norte")</li>
                    <li>• Reduce el número de filtros aplicados</li>
                    <li>• Amplía el rango de precios o superficie</li>
                    <li>• Considera ubicaciones cercanas</li>
                </ul>
            </div>

            <!-- Contacto cuando no hay propiedades -->
            <div v-if="type === 'general'" class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">¿Necesitas ayuda?</h4>
                <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                    Nuestros asesores están disponibles para ayudarte a encontrar la propiedad perfecta.
                </p>
                <Button as-child variant="outline" size="sm">
                    <a href="/contacto">
                        <MapPin class="w-4 h-4 mr-1" />
                        Contactar asesor
                    </a>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Animación de rotación para el icono de refresco */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Mejorar contraste en modo oscuro */
.dark .bg-blue-100 {
    background-color: rgb(30 58 138);
}

.dark .bg-blue-50 {
    background-color: rgb(30 58 138 / 0.2);
}
</style>