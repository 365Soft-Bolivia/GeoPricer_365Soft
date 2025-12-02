<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProyectosImageUploader from './ProyectosImageUploader.vue';
import ProyectosImageGallery from './ProyectosImageGallery.vue';
import { admin } from '@/routes-custom';

const { proyectos } = admin;

interface Category {
  id: number;
  category_name: string;
}

interface ProductImage {
  id: number;
  url: string;
  original_name: string;
  is_primary: boolean;
  order: number;
}

interface Producto {
  id: number;
  name: string;
  codigo_inmueble: string;
  price: number;
  superficie_util?: number;
  superficie_construida?: number;
  ambientes?: number;
  habitaciones?: number;
  banos?: number;
  cocheras?: number;
  ano_construccion?: number;
  operacion: string;
  comision?: number;
  taxes?: number;
  description?: string;
  sku?: string;
  hsn_sac_code?: string;
  allow_purchase?: boolean;
  is_public?: boolean;
  downloadable?: boolean;
  downloadable_file?: string;
  estado: number;
  category: Category | null;
  images: ProductImage[];
  created_at: string;
}

const props = defineProps<{
  producto: Producto;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Proyectos', href: proyectos.index().url },
  { title: props.producto.name, href: proyectos.show(props.producto.id).url },
];

const currentTab = ref<'imagenes' | 'general' | 'detalles' | 'otros'>('imagenes');

const formatPrice = (price?: number) => {
  if (!price) return 'No especificado';
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(price);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getOperacionLabel = (operacion: string) => {
  const labels: Record<string, string> = {
    'venta': 'Venta',
    'alquiler': 'Alquiler',
    'anticretico': 'Anticrético'
  };
  return labels[operacion] || operacion;
};

const handleImageUploaded = () => {
  // La página se recargará automáticamente por Inertia
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Proyecto: ${producto.name}`" />
    
    <div class="py-6">
      <!-- Header -->
      <div class="mb-6 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 shadow-lg">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-4">
            <div class="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
              <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-white">
                {{ producto.name }}
              </h1>
              <p class="mt-1 text-lg text-blue-100">
                Código: {{ producto.codigo_inmueble }}
              </p>
              <div class="mt-3 flex items-center gap-3">
                <span 
                  :class="producto.estado === 1 
                    ? 'bg-green-400 text-green-900' 
                    : 'bg-red-400 text-red-900'"
                  class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold"
                >
                  <span class="h-2 w-2 rounded-full" :class="producto.estado === 1 ? 'bg-green-900' : 'bg-red-900'"></span>
                  {{ producto.estado === 1 ? 'Activo' : 'Inactivo' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-sm font-semibold text-white backdrop-blur-sm">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ producto.images.length }} {{ producto.images.length === 1 ? 'imagen' : 'imágenes' }}
                </span>
              </div>
            </div>
          </div>
          <Link 
            :href="`/admin/proyectos`"
            class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm hover:bg-white/20"
          >
            Volver
          </Link>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex space-x-8 px-6">
            <button
              type="button"
              @click="currentTab = 'imagenes'"
              :class="currentTab === 'imagenes' 
                ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
              class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Imágenes
            </button>
            <button
              type="button"
              @click="currentTab = 'general'"
              :class="currentTab === 'general' 
                ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
              class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
            >
              Información General
            </button>
            <button
              type="button"
              @click="currentTab = 'detalles'"
              :class="currentTab === 'detalles' 
                ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
              class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
            >
              Detalles del Inmueble
            </button>
            <button
              type="button"
              @click="currentTab = 'otros'"
              :class="currentTab === 'otros' 
                ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
              class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
            >
              Información Adicional
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Tab: Imágenes -->
          <div v-show="currentTab === 'imagenes'" class="space-y-6">
            <!-- Subir imágenes -->
            <div>
              <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                Subir Nuevas Imágenes
              </h3>
              <ProyectosImageUploader
                :product-id="producto.id"
                @uploaded="handleImageUploaded"
              />
            </div>

            <!-- Galería existente -->
            <div>
              <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                Galería de Imágenes
              </h3>
              <ProyectosImageGallery
                :images="producto.images"
                :product-id="producto.id"
                :can-edit="true"
              />
            </div>
          </div>

          <!-- Tab: General (igual que antes) -->
          <div v-show="currentTab === 'general'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
              <!-- Precio Principal -->
              <div class="lg:col-span-2">
                <div class="rounded-lg bg-gradient-to-br from-green-50 to-emerald-50 p-6 dark:from-green-900/20 dark:to-emerald-900/20">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Precio</p>
                      <p class="mt-2 text-4xl font-bold text-green-600 dark:text-green-400">
                        {{ formatPrice(producto.price) }}
                      </p>
                    </div>
                    <div class="rounded-lg bg-green-600 p-4">
                      <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Operación</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ getOperacionLabel(producto.operacion) }}
                </p>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoría</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ producto.category?.category_name || 'Sin categoría' }}
                </p>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700" v-if="producto.comision">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Comisión</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ producto.comision }}%
                </p>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700" v-if="producto.taxes">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Impuestos</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ formatPrice(producto.taxes) }}
                </p>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700" v-if="producto.sku">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</p>
                <p class="mt-2 font-mono text-xl font-semibold text-gray-900 dark:text-white">
                  {{ producto.sku }}
                </p>
              </div>

              <div class="lg:col-span-2" v-if="producto.description">
                <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</p>
                  <p class="mt-3 text-base text-gray-700 dark:text-gray-300">
                    {{ producto.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab: Detalles -->
          <div v-show="currentTab === 'detalles'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              <div class="rounded-lg bg-blue-50 p-6 dark:bg-blue-900/20" v-if="producto.superficie_util">
                <div class="flex items-center gap-4">
                  <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-800">
                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Superficie Útil</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ producto.superficie_util }} m²</p>
                  </div>
                </div>
              </div>

              <div class="rounded-lg bg-purple-50 p-6 dark:bg-purple-900/20" v-if="producto.superficie_construida">
                <div class="flex items-center gap-4">
                  <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-800">
                    <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Superficie Construida</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ producto.superficie_construida }} m²</p>
                  </div>
                </div>
              </div>

              <div class="rounded-lg bg-orange-50 p-6 dark:bg-orange-900/20" v-if="producto.ano_construccion">
                <div class="flex items-center gap-4">
                  <div class="rounded-lg bg-orange-100 p-3 dark:bg-orange-800">
                    <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Año Construcción</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ producto.ano_construccion }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
              <div class="text-center rounded-lg border-2 border-gray-200 p-6 dark:border-gray-700" v-if="producto.ambientes">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ producto.ambientes }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ambientes</p>
              </div>

              <div class="text-center rounded-lg border-2 border-gray-200 p-6 dark:border-gray-700" v-if="producto.habitaciones">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ producto.habitaciones }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Habitaciones</p>
              </div>

              <div class="text-center rounded-lg border-2 border-gray-200 p-6 dark:border-gray-700" v-if="producto.banos">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9V7a2 2 0 012-2h2m12 4V7a2 2 0 00-2-2h-2M3 9v8a2 2 0 002 2h2M3 9h18m-3 0v8a2 2 0 01-2 2h-2m-8-10h8" />
                </svg>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ producto.banos }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Baños</p>
              </div>

              <div class="text-center rounded-lg border-2 border-gray-200 p-6 dark:border-gray-700" v-if="producto.cocheras">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ producto.cocheras }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cocheras</p>
              </div>
            </div>
          </div>

          <!-- Tab: Otros -->
          <div v-show="currentTab === 'otros'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700" v-if="producto.hsn_sac_code">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Código HSN/SAC</p>
                <p class="mt-2 font-mono text-lg font-semibold text-gray-900 dark:text-white">
                  {{ producto.hsn_sac_code }}
                </p>
              </div>

              <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</p>
                <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                  {{ formatDate(producto.created_at) }}
                </p>
              </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
              <p class="mb-4 text-sm font-medium text-gray-500 dark:text-gray-400">Configuraciones</p>
              <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Permitir compra</span>
                  <span :class="producto.allow_purchase ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'" class="rounded-full px-3 py-1 text-xs font-semibold">
                    {{ producto.allow_purchase ? 'Sí' : 'No' }}
                  </span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Visible públicamente</span>
                  <span :class="producto.is_public ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'" class="rounded-full px-3 py-1 text-xs font-semibold">
                    {{ producto.is_public ? 'Sí' : 'No' }}
                  </span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Archivo descargable</span>
                  <span :class="producto.downloadable ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'" class="rounded-full px-3 py-1 text-xs font-semibold">
                    {{ producto.downloadable ? 'Sí' : 'No' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700" v-if="producto.downloadable_file">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Archivo Descargable</p>
              <a :href="producto.downloadable_file" target="_blank" class="mt-2 inline-flex items-center gap-2 text-base font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar archivo
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
