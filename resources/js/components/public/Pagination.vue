<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    MoreHorizontal
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from?: number;
    to?: number;
    links?: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    showInfo?: boolean;
    showPerPageSelector?: boolean;
    perPageOptions?: number[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showInfo: true,
    showPerPageSelector: true,
    perPageOptions: () => [12, 24, 48, 96],
    loading: false
});

const emit = defineEmits<{
    pageChange: [page: number];
    perPageChange: [perPage: number];
}>();

// Computed properties
const startPage = computed(() => {
    if (props.currentPage <= 3) return 1;
    if (props.currentPage >= props.lastPage - 2) return Math.max(1, props.lastPage - 4);
    return props.currentPage - 2;
});

const endPage = computed(() => {
    if (props.currentPage <= 3) return Math.min(5, props.lastPage);
    if (props.currentPage >= props.lastPage - 2) return props.lastPage;
    return props.currentPage + 2;
});

const pages = computed(() => {
    const pagesArray = [];
    for (let i = startPage.value; i <= endPage.value; i++) {
        pagesArray.push(i);
    }
    return pagesArray;
});

const hasPreviousPages = computed(() => startPage.value > 1);
const hasNextPages = computed(() => endPage.value < props.lastPage);

const showLeftEllipsis = computed(() => startPage.value > 2);
const showRightEllipsis = computed(() => endPage.value < props.lastPage - 1);

const infoText = computed(() => {
    if (!props.showInfo) return '';

    const from = props.from ?? ((props.currentPage - 1) * props.perPage) + 1;
    const to = props.to ?? Math.min(props.currentPage * props.perPage, props.total);

    if (props.total === 0) {
        return 'No se encontraron resultados';
    }

    return `Mostrando ${from} a ${to} de ${props.total} resultados`;
});

// Métodos
const goToPage = (page: number) => {
    if (page < 1 || page > props.lastPage || page === props.currentPage) {
        return;
    }
    emit('pageChange', page);
};

const goToFirst = () => goToPage(1);
const goToLast = () => goToPage(props.lastPage);
const goToPrevious = () => goToPage(props.currentPage - 1);
const goToNext = () => goToPage(props.currentPage + 1);

const handlePerPageChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const newPerPage = parseInt(target.value);
    emit('perPageChange', newPerPage);
    // Resetear a la primera página
    emit('pageChange', 1);
};

const getPageButtonClass = (page: number) => {
    const baseClasses = 'h-10 w-10 p-0 relative inline-flex items-center justify-center text-sm font-medium transition-all duration-200';

    if (page === props.currentPage) {
        return `${baseClasses} bg-blue-600 text-white border-blue-600 hover:bg-blue-700 focus:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`;
    }

    return `${baseClasses} bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:bg-blue-50 dark:focus:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`;
};

const getNavigationButtonClass = () => {
    return 'h-10 px-3 py-0 relative inline-flex items-center text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:bg-blue-50 dark:focus:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
};
</script>

<template>
    <div class="flex flex-col space-y-4">
        <!-- Información de resultados -->
        <div
            v-if="showInfo && infoText"
            class="text-sm text-gray-600 dark:text-gray-400"
        >
            {{ infoText }}
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Selector de resultados por página -->
            <div
                v-if="showPerPageSelector && total > 0"
                class="flex items-center gap-2"
            >
                <span class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    Resultados por página:
                </span>
                <select
                    :value="perPage"
                    @change="handlePerPageChange"
                    class="h-10 w-20 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option
                        v-for="option in perPageOptions"
                        :key="option"
                        :value="option"
                    >
                        {{ option }}
                    </option>
                </select>
            </div>

            <!-- Navegación de páginas -->
            <div class="flex items-center gap-1">
                <!-- Primera página -->
                <Button
                    v-if="hasPreviousPages"
                    @click="goToFirst"
                    :disabled="loading || currentPage === 1"
                    variant="outline"
                    size="sm"
                    :class="getNavigationButtonClass()"
                    aria-label="Ir a la primera página"
                >
                    <ChevronsLeft class="w-4 h-4" />
                </Button>

                <!-- Página anterior -->
                <Button
                    @click="goToPrevious"
                    :disabled="loading || currentPage === 1"
                    variant="outline"
                    size="sm"
                    :class="getNavigationButtonClass()"
                    aria-label="Página anterior"
                >
                    <ChevronLeft class="w-4 h-4" />
                </Button>

                <!-- Páginas numeradas -->
                <div class="flex items-center gap-1">
                    <!-- Primera página cuando hay ellipsis -->
                    <Button
                        v-if="hasPreviousPages"
                        @click="goToFirst"
                        :disabled="loading"
                        variant="outline"
                        size="sm"
                        :class="getPageButtonClass(1)"
                    >
                        1
                    </Button>

                    <!-- Ellipsis izquierdo -->
                    <div
                        v-if="showLeftEllipsis"
                        class="flex items-center justify-center w-10 h-10 text-gray-400"
                    >
                        <MoreHorizontal class="w-4 h-4" />
                    </div>

                    <!-- Páginas principales -->
                    <Button
                        v-for="page in pages"
                        :key="page"
                        @click="goToPage(page)"
                        :disabled="loading"
                        variant="outline"
                        size="sm"
                        :class="getPageButtonClass(page)"
                        :aria-label="`Ir a la página ${page}`"
                        :aria-current="page === currentPage ? 'page' : undefined"
                    >
                        {{ page }}
                    </Button>

                    <!-- Ellipsis derecho -->
                    <div
                        v-if="showRightEllipsis"
                        class="flex items-center justify-center w-10 h-10 text-gray-400"
                    >
                        <MoreHorizontal class="w-4 h-4" />
                    </div>

                    <!-- Última página cuando hay ellipsis -->
                    <Button
                        v-if="hasNextPages && endPage < lastPage"
                        @click="goToLast"
                        :disabled="loading"
                        variant="outline"
                        size="sm"
                        :class="getPageButtonClass(lastPage)"
                    >
                        {{ lastPage }}
                    </Button>
                </div>

                <!-- Página siguiente -->
                <Button
                    @click="goToNext"
                    :disabled="loading || currentPage === lastPage"
                    variant="outline"
                    size="sm"
                    :class="getNavigationButtonClass()"
                    aria-label="Página siguiente"
                >
                    <ChevronRight class="w-4 h-4" />
                </Button>

                <!-- Última página -->
                <Button
                    v-if="hasNextPages"
                    @click="goToLast"
                    :disabled="loading || currentPage === lastPage"
                    variant="outline"
                    size="sm"
                    :class="getNavigationButtonClass()"
                    aria-label="Ir a la última página"
                >
                    <ChevronsRight class="w-4 h-4" />
                </Button>
            </div>
        </div>

        <!-- Indicador de página actual (para móvil) -->
        <div class="sm:hidden text-center text-sm text-gray-600 dark:text-gray-400">
            Página {{ currentPage }} de {{ lastPage }}
        </div>
    </div>
</template>

<style scoped>
/* Estilos para asegurar buen contraste y accesibilidad */
button:focus {
    outline: none;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Mejorar visibilidad en modo oscuro */
.dark button {
    border-color: rgb(75 85 99);
}

.dark button:hover:not(:disabled) {
    background-color: rgb(55 65 81);
}

/* Animaciones suaves */
button {
    transition: all 0.2s ease-in-out;
}
</style>