<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reordenar Datos',
        href: '/admin/data-reorder',
    },
];

// Estado del componente
const isAnalyzing = ref(false);
const isDryRun = ref(true);
const result = ref<any>(null);
const error = ref<string | null>(null);

// Función para obtener el token CSRF con múltiples fallbacks
const getCsrfToken = (): string => {
    // Método 1: Desde window (agregado en app.ts)
    if ((window as any).csrfToken) {
        return (window as any).csrfToken;
    }

    // Método 2: Desde meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        const content = metaTag.getAttribute('content');
        if (content) return content;
    }

    // Método 3: Desde cookies (laravel_token)
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN' || name === 'laravel_token') {
            try {
                return decodeURIComponent(value);
            } catch {
                return value;
            }
        }
    }

    throw new Error('No se pudo obtener el token CSRF. Recarga la página.');
};

// Analizar productos existentes
const analyzeProducts = async () => {
    isAnalyzing.value = true;
    error.value = null;
    result.value = null;

    try {
        const csrfToken = getCsrfToken();

        console.log('Token CSRF obtenido:', csrfToken.substring(0, 10) + '...');

        const response = await fetch('/admin/data-reorder/analyze', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                dry_run: isDryRun.value,
            }),
        });

        // Verificar si la respuesta es HTML (error del servidor)
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no JSON recibida:', text.substring(0, 200));
            throw new Error('El servidor devolvió una respuesta inválida. Verifica los logs de Laravel.');
        }

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Error al analizar los productos');
        }

        result.value = data.data;

        // Si no es dry run y hay cambios, recargar después de 2 segundos
        if (!isDryRun.value && data.data.changes_count > 0) {
            setTimeout(() => {
                router.reload();
            }, 2000);
        }
    } catch (e: any) {
        console.error('Error completo:', e);
        error.value = e.message || 'Error al analizar los productos';
    } finally {
        isAnalyzing.value = false;
    }
};

// Formatear número
const formatNumber = (num: number) => {
    return new Intl.NumberFormat('es-BO').format(num);
};
</script>

<template>
    <Head title="Reordenar Datos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Reordenar Datos Existentes
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Analiza, corrige y limpia automáticamente los datos de productos para proteger tu ACM
                    </p>
                </div>
            </div>

            <!-- Alert -->
            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Procesamiento Inteligente por Lotes
                        </h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            Este módulo analiza todos los productos de la base de datos en lotes de 1000 para optimizar el rendimiento.
                            Detecta automáticamente la categoría y operación correcta basándose en el nombre y descripción.
                            <br><br>
                            <strong>🛡️ Protección de tu ACM:</strong> Elimina automáticamente productos con datos incompletos
                            (precio sin superficie, o superficie sin precio) para evitar errores en tus cálculos de pricing.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="isDryRun" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Modo Simulación ({{ isDryRun ? 'Solo previsualizar' : 'Aplicar cambios' }})
                            </span>
                        </label>
                    </div>

                    <button
                        @click="analyzeProducts"
                        :disabled="isAnalyzing"
                        class="rounded-lg bg-primary px-6 py-2 text-white hover:bg-primary/80 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="isAnalyzing">Analizando...</span>
                        <span v-else>{{ isDryRun ? 'Analizar Productos' : 'Aplicar Correcciones' }}</span>
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
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Resumen del Análisis</h2>

                    <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(result.total) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(result.correct) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Correctos</p>
                        </div>
                        <div class="rounded-lg bg-orange-50 p-4 dark:bg-orange-900/20">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatNumber(result.incorrect_category) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Categoría Incorrecta</p>
                        </div>
                        <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(result.incorrect_operation) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Operación Incorrecta</p>
                        </div>
                        <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatNumber(result.both_incorrect) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ambos Incorrectos</p>
                        </div>
                        <div class="rounded-lg bg-red-100 p-4 dark:bg-red-900/30">
                            <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ formatNumber(result.deleted_count) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">A Eliminar</p>
                        </div>
                    </div>

                    <div v-if="result.dry_run" class="mt-4 rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/30">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            ⚠️ Este fue un modo de simulación. {{ formatNumber(result.changes_count) }} cambios detectados.
                            Desmarca "Modo Simulación" para aplicar realmente las correcciones.
                        </p>
                    </div>

                    <div v-if="result.dry_run && result.deleted_count > 0" class="mt-4 rounded-lg bg-red-100 p-3 dark:bg-red-900/30">
                        <p class="text-sm text-red-800 dark:text-red-200">
                            🗑️ Se detectaron {{ formatNumber(result.deleted_count) }} productos incompletos que afectarán tu ACM.
                            Estos serán eliminados al aplicar las correcciones.
                        </p>
                    </div>

                    <div v-if="!result.dry_run && result.applied > 0" class="mt-4 rounded-lg bg-green-100 p-3 dark:bg-green-900/30">
                        <p class="text-sm text-green-800 dark:text-green-200">
                            ✓ ¡Correcciones aplicadas exitosamente! {{ formatNumber(result.applied) }} productos actualizados.
                        </p>
                    </div>

                    <div v-if="!result.dry_run && result.deleted > 0" class="mt-4 rounded-lg bg-green-100 p-3 dark:bg-green-900/30">
                        <p class="text-sm text-green-800 dark:text-green-200">
                            ✓ ¡Productos incompletos eliminados! {{ formatNumber(result.deleted) }} productos eliminados para proteger tu ACM.
                        </p>
                    </div>
                </div>

                <!-- Changes Table -->
                <div v-if="result.changes && result.changes.length > 0" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Cambios Detectados (Primeros 50 de {{ formatNumber(result.changes_count) }})
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white bg-blue-100 dark:bg-blue-900">CÓDIGO</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Categoría</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Operación</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in result.changes" :key="item.codigo" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20">{{ item.codigo }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-xs truncate">{{ item.name }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div v-if="item.type === 'both' || item.type === 'category'">
                                            <div class="text-red-600 line-through">{{ item.current_category }}</div>
                                            <div class="text-green-600">→ {{ item.correct_category }}</div>
                                        </div>
                                        <div v-else class="text-gray-600">{{ item.current_category || 'SIN CATEGORÍA' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div v-if="item.type === 'both' || item.type === 'operation'">
                                            <div class="text-red-600 line-through">{{ item.current_operation }}</div>
                                            <div class="text-green-600">→ {{ item.correct_operation }}</div>
                                        </div>
                                        <div v-else class="text-gray-600">{{ item.current_operation || 'SIN OPERACIÓN' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        <div v-if="item.price_usd" class="text-green-600">${{ item.price_usd }}</div>
                                        <div v-if="item.price_bob" class="text-blue-600">Bs{{ item.price_bob }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span v-if="item.type === 'both'" class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Ambos
                                        </span>
                                        <span v-else-if="item.type === 'category'" class="rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            Categoría
                                        </span>
                                        <span v-else-if="item.type === 'operation'" class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            Operación
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="result.changes_count > 50" class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                        ... y {{ formatNumber(result.changes_count - 50) }} cambios más.
                    </div>
                </div>

                <!-- Products to Delete Table -->
                <div v-if="result.products_to_delete && result.products_to_delete.length > 0" class="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-900/20">
                    <h2 class="mb-4 text-lg font-semibold text-red-900 dark:text-red-200">
                        Productos Incompletos a Eliminar (Primeros 50 de {{ formatNumber(result.deleted_count) }})
                    </h2>
                    <div class="mb-3 text-sm text-red-800 dark:text-red-300">
                        <p>Estos productos tienen datos incompletos que pueden afectar negativamente tu ACM:</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-red-200 dark:divide-red-700">
                            <thead class="bg-red-100 dark:bg-red-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-red-900 dark:text-white">CÓDIGO</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-red-800 dark:text-red-300">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-red-800 dark:text-red-300">Motivo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-red-800 dark:text-red-300">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-red-800 dark:text-red-300">Superficie</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-200 dark:divide-red-700">
                                <tr v-for="item in result.products_to_delete" :key="item.codigo" class="hover:bg-red-100 dark:hover:bg-red-900/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-mono font-bold text-red-600 dark:text-red-400">{{ item.codigo }}</td>
                                    <td class="px-4 py-3 text-sm text-red-900 dark:text-red-100 max-w-xs truncate">{{ item.name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span v-if="item.reason === 'precio_sin_superficie'" class="rounded-full bg-red-200 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Precio sin superficie
                                        </span>
                                        <span v-else-if="item.reason === 'superficie_sin_precio'" class="rounded-full bg-red-200 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Superficie sin precio
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-red-900 dark:text-red-100">
                                        <div v-if="item.price_usd" class="text-green-600">${{ item.price_usd }}</div>
                                        <div v-if="item.price_bob" class="text-blue-600">Bs{{ item.price_bob }}</div>
                                        <div v-if="!item.price_usd && !item.price_bob" class="text-gray-500">Sin precio</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-red-900 dark:text-red-100">
                                        <div v-if="item.superficie_util" class="text-orange-600">{{ item.superficie_util }} m²</div>
                                        <div v-if="item.superficie_construida" class="text-purple-600">{{ item.superficie_construida }} m²</div>
                                        <div v-if="!item.superficie_util && !item.superficie_construida" class="text-gray-500">Sin superficie</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="result.deleted_count > 50" class="mt-4 text-center text-sm text-red-800 dark:text-red-300">
                        ... y {{ formatNumber(result.deleted_count - 50) }} productos más a eliminar.
                    </div>
                </div>

                <!-- No Changes -->
                <div v-if="result.changes_count === 0" class="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-900 dark:bg-green-900/20">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                ¡Todo Correcto!
                            </h3>
                            <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                Todos los productos están correctamente clasificados. No se detectaron inconsistencias.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">¿Qué hace este módulo?</h2>
                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <p>✅ <strong>Analiza todos los productos</strong> en lotes de 1000 para optimizar rendimiento</p>
                    <p>✅ <strong>Detecta categoría incorrecta</strong> basándose en el nombre y descripción</p>
                    <p>✅ <strong>Detecta operación incorrecta</strong> (venta/alquiler) en el contenido</p>
                    <p>🗑️ <strong>Elimina productos incompletos</strong> que afectan el ACM:
                        <ul class="ml-6 mt-1 space-y-1">
                            <li>• Productos con precio pero sin superficie</li>
                            <li>• Productos con superficie pero sin precio</li>
                        </ul>
                    </p>
                    <p>✅ <strong>Muestra reporte</strong> con los cambios propuestos antes de aplicar</p>
                    <p>✅ <strong>Protege contra duplicados</strong> - Solo actualiza si es necesario</p>
                    <p>✅ <strong>Transacciones seguras</strong> - Si hay error, revierte todos los cambios</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
