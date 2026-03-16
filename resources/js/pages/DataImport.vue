<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inyección de Datos',
        href: '/admin/data-import',
    },
];

// Estado del componente
const file = ref<File | null>(null);
const isDragging = ref(false);
const isProcessing = ref(false);
const isDryRun = ref(true);
const result = ref<any>(null);
const error = ref<string | null>(null);
const categories = ref<any[]>([]);
const showSuccessModal = ref(false);

// Función para obtener el token CSRF con múltiples fallbacks
const getCsrfToken = (): string => {
    /**
     * Laravel actualizado: el token está disponible en:
     * 1. window.csrfToken (de app.ts)
     * 2. meta[name="csrf-token"] (tradicional)
     * 3. Cookie XSRF-TOKEN (del middleware EncryptCookies)
     * 4. Cookie laravel_token (alternativa)
     */

    // Método 1: Desde window (agregado en app.ts)
    if ((window as any).csrfToken && (window as any).csrfToken.trim()) {
        console.debug('✓ Token CSRF obtenido desde window.csrfToken');
        return (window as any).csrfToken;
    }

    // Método 2: Desde meta tag (tradicional)
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        const content = metaTag.getAttribute('content');
        if (content && content.trim()) {
            console.debug('✓ Token CSRF obtenido desde meta[csrf-token]');
            return content;
        }
    }

    // Método 3: Desde cookies - buscar XSRF-TOKEN (primero), luego laravel_token
    const cookieNames = ['XSRF-TOKEN', 'laravel_token'];
    for (const cookieName of cookieNames) {
        const value = getCookieValue(cookieName);
        if (value) {
            console.debug(`✓ Token CSRF obtenido desde cookie ${cookieName}`);
            return value;
        }
    }

    // Si llegamos aquí, no hay token
    console.error('✗ No se encontró el token CSRF en ninguna ubicación');
    console.debug('Ubicaciones buscadas:', {
        'window.csrfToken': (window as any).csrfToken,
        'meta[csrf-token]': metaTag?.getAttribute('content'),
        'cookies': document.cookie,
    });
    throw new Error('No se pudo obtener el token CSRF. Por favor, recarga la página.');
};

// Helper para obtener el valor de una cookie
const getCookieValue = (name: string): string | null => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        try {
            return decodeURIComponent(parts.pop()?.split(';').shift() || '');
        } catch (e) {
            return parts.pop()?.split(';').shift() || null;
        }
    }
    return null;
};

// Cargar categorías
const fetchCategories = async () => {
    try {
        const response = await fetch('/admin/data-import/categories');
        const data = await response.json();
        if (data.success) {
            categories.value = data.data;
        }
    } catch (e) {
        console.error('Error al cargar categorías:', e);
    }
};

fetchCategories();

// Manejar selección de archivo
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        file.value = target.files[0];
        error.value = null;
        result.value = null;
    }
};

// Manejar drag and drop
const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        const droppedFile = event.dataTransfer.files[0];
        if (droppedFile.type === 'application/json' || droppedFile.name.endsWith('.json')) {
            file.value = droppedFile;
            error.value = null;
            result.value = null;
        } else {
            error.value = 'Por favor, selecciona un archivo JSON válido.';
        }
    }
};

// Procesar archivo
const processFile = async () => {
    if (!file.value) {
        error.value = 'Por favor, selecciona un archivo.';
        return;
    }

    isProcessing.value = true;
    error.value = null;
    result.value = null;

    const formData = new FormData();
    formData.append('json_file', file.value);
    formData.append('dry_run', isDryRun.value ? '1' : '0');

    try {
        let csrfToken = '';
        try {
            csrfToken = getCsrfToken();
        } catch (e) {
            error.value = 'Error: No se pudo obtener el token de seguridad. Recarga la página.';
            isProcessing.value = false;
            return;
        }

        const response = await fetch('/admin/data-import/process', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
            credentials: 'same-origin', // Asegurar que se envíen cookies
        });

        // Manejar respuesta
        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            if (contentType?.includes('application/json')) {
                const errorData = await response.json();
                throw new Error(errorData.error || errorData.message || `Error ${response.status}: ${response.statusText}`);
            } else {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || data.message || 'Error al procesar el archivo');
        }

        result.value = data.data;

        // Si no es dry run y hay éxito, mostrar modal de éxito y limpiar
        if (!isDryRun.value && data.data?.processed > 0) {
            showSuccessModal.value = true;
            // Limpiar formulario
            resetForm();
        }
    } catch (e: any) {
        console.error('Error en processFile:', e);
        error.value = e.message || 'Error al procesar el archivo. Por favor, intenta de nuevo.';
    } finally {
        isProcessing.value = false;
    }
};

// Limpiar formulario
const resetForm = () => {
    file.value = null;
    error.value = null;
    result.value = null;
    // Resetear el input file
    const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
};

// Cerrar modal de éxito
const closeSuccessModal = () => {
    showSuccessModal.value = false;
};

// Formatear número
const formatNumber = (num: number) => {
    return new Intl.NumberFormat('es-BO').format(num);
};
</script>

<template>
    <Head title="Inyección de Datos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Inyección de Datos
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Importa propiedades desde archivos JSON con clasificación automática
                    </p>
                </div>
            </div>

            <!-- Upload Area -->
            <div class="rounded-lg border-2 border-dashed p-8"
                 :class="isDragging ? 'border-primary bg-primary/5' : 'border-gray-300 dark:border-gray-600'"
                 @dragover="handleDragOver"
                 @dragleave="handleDragLeave"
                 @drop="handleDrop">
                <div class="flex flex-col items-center justify-center text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="mt-4 flex text-sm text-gray-600 dark:text-gray-400">
                        <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none">
                            <span>Sube un archivo JSON</span>
                            <input id="file-upload" name="file-upload" type="file" accept=".json" class="sr-only" @change="handleFileSelect">
                        </label>
                        <p class="pl-1">o arrástralo aquí</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-500">JSON hasta 10 MB</p>
                </div>

                <!-- File Selected -->
                <div v-if="file" class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ file.name }}</span>
                            <span class="ml-2 text-sm text-gray-500">({{ (file.size / 1024).toFixed(2) }} KB)</span>
                        </div>
                        <button @click="file = null; result = null; error = null" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mode Toggle -->
                <div class="mt-4 flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="isDryRun" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Modo Simulación ({{ isDryRun ? 'Solo previsualizar' : 'Guardar en BD' }})
                        </span>
                    </label>
                </div>

                <!-- Process Button -->
                <div class="mt-4 flex justify-center">
                    <button
                        @click="processFile"
                        :disabled="!file || isProcessing"
                        class="rounded-lg bg-primary px-6 py-2 text-white hover:bg-primary/80 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="isProcessing">Procesando...</span>
                        <span v-else>{{ isDryRun ? 'Previsualizar Datos' : 'Importar Datos' }}</span>
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="result" class="space-y-4">
                <!-- Summary -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Resumen del Procesamiento</h2>

                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(result.total) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(result.processed) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Procesados</p>
                        </div>
                        <div class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ formatNumber(result.skipped) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Omitidos</p>
                        </div>
                        <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatNumber(result.errors) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Errores</p>
                        </div>
                    </div>

                    <div v-if="result.dry_run" class="mt-4 rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/30">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            ⚠️ Este fue un modo de simulación. Los datos NO fueron guardados en la base de datos.
                            Desmarca "Modo Simulación" para importar realmente.
                        </p>
                    </div>
                </div>

                <!-- Processed Items -->
                <div v-if="result.items && result.items.length > 0" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Ejemplos de Items Procesados (Primeros 20)</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Categoría</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Operación</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Datos Guardados</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in result.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.id }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ item.detected_category }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ item.detected_operation }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                        <div v-if="item.data_saved" class="space-y-1">
                                            <!-- Precios -->
                                            <div v-if="item.data_saved.price_usd" class="text-green-600">Precio USD: ${{ item.data_saved.price_usd }}</div>
                                            <div v-if="item.data_saved.price_bob" class="text-green-600">Precio BOB: Bs{{ item.data_saved.price_bob }}</div>
                                            <div v-if="item.data_saved.superficie_util" class="text-blue-600">Superficie: {{ item.data_saved.superficie_util }} m²</div>
                                            <div v-if="item.data_saved.superficie_construida" class="text-blue-600">Construida: {{ item.data_saved.superficie_construida }} m²</div>
                                            <div v-if="item.data_saved.habitaciones" class="text-gray-700">Habitaciones: {{ item.data_saved.habitaciones }}</div>
                                            <div v-if="item.data_saved.banos" class="text-gray-700">Baños: {{ item.data_saved.banos }}</div>
                                            <div v-if="item.data_saved.cocheras" class="text-gray-700">Cocheras: {{ item.data_saved.cocheras }}</div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span v-if="result.dry_run" class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            Simulado
                                        </span>
                                        <span v-else class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Guardado
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Skipped Items -->
                <div v-if="result.skipped_list && result.skipped_list.length > 0" class="rounded-lg border border-orange-200 bg-orange-50 p-6 dark:border-orange-900 dark:bg-orange-900/20">
                    <h2 class="mb-4 text-lg font-semibold text-orange-900 dark:text-orange-200">Items Omitidos (Primeros 20 de {{ formatNumber(result.skipped) }})</h2>
                    <div class="mb-3 text-sm text-orange-800 dark:text-orange-300">
                        <p>Estos items tienen datos incompletos que pueden afectar negativamente tu ACM:</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-orange-200 dark:divide-orange-700">
                            <thead class="bg-orange-100 dark:bg-orange-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-orange-900 dark:text-white">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-orange-800 dark:text-orange-300">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-orange-800 dark:text-orange-300">Motivo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-orange-800 dark:text-orange-300">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-orange-800 dark:text-orange-300">Superficie</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-orange-200 dark:divide-orange-700">
                                <tr v-for="item in result.skipped_list" :key="item.id" class="hover:bg-orange-100 dark:hover:bg-orange-900/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-mono font-bold text-orange-600 dark:text-orange-400">{{ item.id }}</td>
                                    <td class="px-4 py-3 text-sm text-orange-900 dark:text-orange-100 max-w-xs truncate">{{ item.name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span v-if="item.reason === 'sin_precio_superficie'" class="rounded-full bg-red-200 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Sin precio
                                        </span>
                                        <span v-else-if="item.reason === 'precio_sin_superficie'" class="rounded-full bg-orange-200 px-2 py-1 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            Precio sin superficie
                                        </span>
                                        <span v-else class="rounded-full bg-gray-200 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ item.reason }}
                                        </span>
                                        <div v-if="item.reason_text" class="mt-1 text-xs text-orange-700 dark:text-orange-300">{{ item.reason_text }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-orange-900 dark:text-orange-100">
                                        <div v-if="item.price_usd" class="text-green-600">${{ item.price_usd }}</div>
                                        <div v-if="item.price_bob" class="text-blue-600">Bs{{ item.price_bob }}</div>
                                        <div v-if="!item.price_usd && !item.price_bob" class="text-gray-500">Sin precio</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-orange-900 dark:text-orange-100">
                                        <div v-if="item.superficie_util" class="text-orange-600">{{ item.superficie_util }} m²</div>
                                        <div v-if="item.superficie_construida" class="text-purple-600">{{ item.superficie_construida }} m²</div>
                                        <div v-if="!item.superficie_util && !item.superficie_construida" class="text-gray-500">Sin superficie</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="result.skipped > 20" class="mt-4 text-center text-sm text-orange-800 dark:text-orange-300">
                        ... y {{ formatNumber(result.skipped - 20) }} items más omitidos.
                    </div>
                </div>

                <!-- Errors -->
                <div v-if="result.errors_list && result.errors_list.length > 0" class="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-900/20">
                    <h2 class="mb-4 text-lg font-semibold text-red-900 dark:text-red-200">Errores (Primeros 10)</h2>
                    <div class="space-y-2">
                        <div v-for="err in result.errors_list" :key="err.id" class="rounded bg-red-100 p-3 dark:bg-red-900/40">
                            <p class="font-medium text-red-900 dark:text-red-200">ID: {{ err.id }} - {{ err.name }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300">{{ err.error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Reference -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Categorías Disponibles en la BD</h2>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6">
                    <span v-for="cat in categories" :key="cat.id" class="rounded-full bg-gray-100 px-3 py-1 text-center text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ cat.category_name }}
                    </span>
                </div>
            </div>

            <!-- Modal de Éxito -->
            <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <!-- Header -->
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    ¡Importación Exitosa!
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Los datos han sido guardados en la base de datos
                                </p>
                            </div>
                        </div>
                        <button @click="closeSuccessModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Resumen -->
                    <div class="mb-6 grid grid-cols-3 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ result?.processed || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Procesados</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ result?.skipped || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Omitidos</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ result?.errors || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Errores</p>
                        </div>
                    </div>

                    <!-- Alerta de Productos Omitidos -->
                    <div v-if="result?.skipped > 0" class="mb-6 rounded-lg border-2 border-orange-300 bg-orange-50 p-4 dark:border-orange-900 dark:bg-orange-900/20">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Productos Omitidos</h3>
                                <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                    <p>
                                        <strong class="text-orange-800 dark:text-orange-300">{{ result.skipped }}</strong> productos fueron omitidos porque tienen datos incompletos.
                                    </p>
                                    <div v-if="result?.skipped_reasons" class="mt-2 space-y-1">
                                        <p v-if="result.skipped_reasons['sin_precio_superficie']" class="text-red-800 dark:text-red-300">
                                            • <strong>{{ result.skipped_reasons['sin_precio_superficie'] }}</strong> sin precio (USD ni BOB)
                                        </p>
                                        <p v-if="result.skipped_reasons['precio_sin_superficie']" class="text-orange-800 dark:text-orange-300">
                                            • <strong>{{ result.skipped_reasons['precio_sin_superficie'] }}</strong> con precio pero sin superficie
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        Esta protección evita que datos incompletos afecten los cálculos de tu ACM/Radar.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas de Categorías -->
                    <div v-if="result?.categories_stats && Object.keys(result.categories_stats).length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Categorías Encontradas</h3>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                            <div v-for="(count, category) in result.categories_stats" :key="category" class="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 dark:bg-blue-900/20">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ category }}</span>
                                <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas de Operaciones -->
                    <div v-if="result?.operations_stats && Object.keys(result.operations_stats).length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Tipos de Operación</h3>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="(count, operation) in result.operations_stats" :key="operation" class="flex items-center justify-between rounded-lg bg-purple-50 px-4 py-2 dark:bg-purple-900/20">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ operation }}</span>
                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200">{{ count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de cierre -->
                    <div class="flex justify-end gap-3">
                        <button @click="closeSuccessModal" class="rounded-lg border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
