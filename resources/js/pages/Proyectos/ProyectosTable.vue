<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ProyectosEditDialog from './ProyectosEditDialog.vue';
import { admin } from '@/routes-custom';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const { proyectos } = admin;

interface Category {
  id: number;
  category_name: string;
}

interface Producto {
  id: number;
  name: string;
  codigo_inmueble: string;
  price_usd?: number | null;
  price_bob?: number | null;
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
  default_image?: string;
  estado: number;
  category: Category | null;
  created_at: string;
}

interface Pagination {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
}

const props = defineProps<{
  productos: Producto[];
  categorias: Category[];
  pagination?: Pagination;
  filters?: {
    search?: string;
  };
}>();

const showEditDialog = ref(false);
const selectedProduct = ref<Producto | null>(null);

// Obtener los filtros actuales de la URL
const currentFilters = () => {
  const urlParams = new URLSearchParams(window.location.search);
  const filters: Record<string, string> = {};

  if (urlParams.has('search')) {
    filters.search = urlParams.get('search')!;
  }

  return filters;
};

// Calcular páginas visibles dinámicamente (estilo Google)
const visiblePages = computed(() => {
  if (!props.pagination) return [];

  const pages: (number | string)[] = [];
  const total = props.pagination.last_page;
  const current = props.pagination.current_page;

  // Siempre mostrar la primera página
  pages.push(1);

  if (total <= 7) {
    // Si hay 7 o menos páginas, mostrar todas
    for (let i = 2; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Lógica dinámica estilo Google
    if (current <= 3) {
      // Estamos al inicio: mostrar 1, 2, 3, 4, 5 ... última
      for (let i = 2; i <= 5; i++) pages.push(i);
      pages.push('...');
      pages.push(total);
    } else if (current >= total - 2) {
      // Estamos al final: mostrar 1 ... últimas 5 páginas
      pages.push('...');
      for (let i = total - 4; i <= total; i++) pages.push(i);
    } else {
      // Estamos en el medio: mostrar 1 ... páginas alrededor de actual ... última
      pages.push('...');
      for (let i = current - 1; i <= current + 1; i++) pages.push(i);
      pages.push('...');
      pages.push(total);
    }
  }

  return pages;
});

const toggleStatus = (id: number) => {
  if (confirm('¿Estás seguro de cambiar el estado de este proyecto?')) {
    router.post(proyectos.toggle(id).url, {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['productos'] });
      }
    });
  }
};

const deleteProduct = (id: number, name: string) => {
  if (confirm(`¿Estás seguro de eliminar el proyecto "${name}"? Esta acción no se puede deshacer.`)) {
    router.delete(proyectos.destroy(id).url, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['productos'] });
      }
    });
  }
};

// CAMBIADO: Navega a la página de detalles
const viewProduct = (id: number) => {
  router.visit(proyectos.show(id).url);
};

const editProduct = (product: Producto) => {
  selectedProduct.value = product;
  showEditDialog.value = true;
};

const handleProductUpdated = () => {
  showEditDialog.value = false;
  selectedProduct.value = null;
  router.reload({ only: ['productos'] });
};

const formatPrice = (price?: number | null, currency: string = '$') => {
  if (!price) return '';
  const symbol = currency === '$' ? '$' : 'Bs.';
  return `${symbol}${price.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Funciones de paginación
const goToPage = (page: number) => {
  const params = {
    page,
    ...currentFilters(), // Mantener los filtros actuales (búsqueda, etc.)
  };

  router.get(proyectos.index.url({ query: params }), {}, { preserveScroll: true });
};
</script>

<template>
  <!-- Tabla Container -->
  <div class="overflow-hidden rounded-2xl shadow-lg bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr style="background: #F5F5F5;">
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Código
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Nombre
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Categoría
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Precio
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Estado
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Fecha Creación
            </th>
            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" style="color: #212121; font-family: 'Montserrat', sans-serif;">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="producto in props.productos" :key="producto.id" class="transition-colors hover:bg-gray-50">
            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold" style="color: #233C7A;">
              {{ producto.codigo_inmueble }}
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium" style="color: #212121;">
                {{ producto.name }}
              </div>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm">
              <span v-if="producto.category" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold" style="background: rgba(35, 60, 122, 0.1); color: #233C7A; font-family: 'Montserrat', sans-serif;">
                {{ producto.category.category_name }}
              </span>
              <span v-else class="text-sm" style="color: #212121; opacity: 0.5;">Sin categoría</span>
            </td>
            <td class="px-6 py-4 text-sm font-medium">
              <div v-if="producto.price_usd || producto.price_bob" class="space-y-1">
                <div v-if="producto.price_usd" class="font-bold" style="color: #10b981;">
                  {{ formatPrice(producto.price_usd, '$') }}
                </div>
                <div v-if="producto.price_bob" class="font-bold" style="color: #233C7A;">
                  {{ formatPrice(producto.price_bob, 'Bs.') }}
                </div>
              </div>
              <span v-else style="color: #212121; opacity: 0.5;">Sin precio</span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm">
              <span
                :class="producto.estado === 1
                  ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold'
                  : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold'"
                :style="producto.estado === 1
                  ? 'background: rgba(16, 185, 129, 0.15); color: #10b981;'
                  : 'background: rgba(224, 8, 29, 0.15); color: #E0081D;'"
                style="font-family: 'Montserrat', sans-serif;"
              >
                {{ producto.estado === 1 ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm" style="color: #212121; opacity: 0.7;">
              {{ formatDate(producto.created_at) }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <!-- Botón Ver/Editar (Unificado) -->
                <button
                  @click="viewProduct(producto.id)"
                  class="rounded-lg p-2 transition-all hover:scale-110"
                  style="color: #233C7A;"
                  title="Ver/Editar detalles"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>

                <!-- Botón Toggle Estado -->
                <button
                  @click="toggleStatus(producto.id)"
                  class="rounded-lg p-2 transition-all hover:scale-110"
                  :style="producto.estado === 1 ? 'color: #E0081D;' : 'color: #10b981;'"
                  :title="producto.estado === 1 ? 'Desactivar' : 'Activar'"
                >
                  <svg v-if="producto.estado === 1" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                  <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>

                <!-- Botón Eliminar -->
                <button
                  @click="deleteProduct(producto.id, producto.name)"
                  class="rounded-lg p-2 transition-all hover:scale-110"
                  style="color: #E0081D;"
                  title="Eliminar"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="props.productos.length === 0" class="py-16 text-center" style="background: #FAFAFA;">
        <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #212121; opacity: 0.3;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-4 text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">No hay proyectos</h3>
        <p class="mt-2 text-sm" style="color: #212121; opacity: 0.6;">
          Comienza creando un nuevo proyecto.
        </p>
      </div>

      <!-- Paginación -->
      <div v-if="props.pagination && props.pagination.last_page > 1" class="border-t border-gray-200 px-6 py-4" style="background: #F5F5F5;">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row sm:gap-0">
          <!-- Info de resultados -->
          <div class="text-sm" style="color: #212121; opacity: 0.7;">
            <span v-if="props.pagination.from && props.pagination.to">
              Mostrando <span class="font-bold" style="color: #233C7A;">{{ props.pagination.from }}</span> a
              <span class="font-bold" style="color: #233C7A;">{{ props.pagination.to }}</span> de
              <span class="font-bold" style="color: #233C7A;">{{ props.pagination.total }}</span> resultados
            </span>
          </div>

          <!-- Controles de paginación -->
          <div class="flex items-center gap-2">
            <!-- Botón Anterior -->
            <button
              @click="goToPage(props.pagination.current_page - 1)"
              :disabled="props.pagination.current_page === 1"
              class="inline-flex items-center rounded-xl border-2 px-4 py-2 text-sm font-bold transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40"
              style="border-color: #e0e0e0; background: white; color: #212121;"
            >
              <ChevronLeft :size="16" class="mr-1" />
              Anterior
            </button>

            <!-- Botones de página -->
            <div class="hidden sm:flex">
              <button
                v-for="(page, index) in visiblePages"
                :key="index"
                @click="typeof page === 'number' && goToPage(page)"
                :disabled="page === '...'"
                :class="[
                  'inline-flex min-w-[40px] items-center justify-center rounded-xl border-2 px-3 py-2 text-sm font-bold transition-all',
                  page === '...'
                    ? 'cursor-default border-transparent bg-transparent'
                    : props.pagination && props.pagination.current_page === page
                      ? 'border-transparent text-white shadow-lg'
                      : 'border-gray-200 bg-white hover:shadow-md'
                ]"
                :style="page === '...'
                  ? ''
                  : props.pagination && props.pagination.current_page === page
                    ? 'background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);'
                    : 'border-color: #e0e0e0; color: #212121;'"
              >
                {{ page }}
              </button>
            </div>

            <!-- Página actual (móvil) -->
            <span class="hidden text-sm font-bold sm:block" style="color: #212121; opacity: 0.7;">
              Página {{ props.pagination.current_page }} de {{ props.pagination.last_page }}
            </span>

            <!-- Botón Siguiente -->
            <button
              @click="goToPage(props.pagination.current_page + 1)"
              :disabled="props.pagination.current_page === props.pagination.last_page"
              class="inline-flex items-center rounded-xl border-2 px-4 py-2 text-sm font-bold transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40"
              style="border-color: #e0e0e0; background: white; color: #212121;"
            >
              Siguiente
              <ChevronRight :size="16" class="ml-1" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dialog Editar -->
  <ProyectosEditDialog
    v-if="showEditDialog && selectedProduct"
    :product="selectedProduct"
    :categorias="props.categorias"
    @close="showEditDialog = false"
    @updated="handleProductUpdated"
  />
</template>
