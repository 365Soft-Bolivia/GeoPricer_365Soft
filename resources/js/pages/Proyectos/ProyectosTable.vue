<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ProyectosEditDialog from './ProyectosEditDialog.vue';

interface Category {
  id: number;
  category_name: string;
}

interface Producto {
  id: number;
  name: string;
  codigo_inmueble: string;
  price: number;
  estado: number;
  category: Category | null;
  created_at: string;
}

defineProps<{
  productos: Producto[];
  categorias: Category[];
}>();

const showEditDialog = ref(false);
const selectedProduct = ref<Producto | null>(null);

const toggleStatus = (id: number) => {
  if (confirm('¿Estás seguro de cambiar el estado de este proyecto?')) {
    router.post(`/proyectos/${id}/toggle-status`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['productos'] });
      }
    });
  }
};

const deleteProduct = (id: number, name: string) => {
  if (confirm(`¿Estás seguro de eliminar el proyecto "${name}"? Esta acción no se puede deshacer.`)) {
    router.delete(`/proyectos/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['productos'] });
      }
    });
  }
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

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(price);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
  <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Código
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Nombre
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Categoría
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Precio
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Estado
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Fecha Creación
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
          <tr v-for="producto in productos" :key="producto.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ producto.codigo_inmueble }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
              {{ producto.name }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
              <span v-if="producto.category" class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ producto.category.category_name }}
              </span>
              <span v-else class="text-gray-400">Sin categoría</span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ formatPrice(producto.price) }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm">
              <span 
                :class="producto.estado === 1 
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                  : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
              >
                {{ producto.estado === 1 ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
              {{ formatDate(producto.created_at) }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <!-- Botón Editar -->
                <button
                  @click="editProduct(producto)"
                  class="rounded p-1 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-gray-700"
                  title="Editar"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <!-- Botón Toggle Estado -->
                <button
                  @click="toggleStatus(producto.id)"
                  :class="producto.estado === 1 ? 'text-red-600 hover:bg-red-50 dark:text-red-400' : 'text-green-600 hover:bg-green-50 dark:text-green-400'"
                  class="rounded p-1 dark:hover:bg-gray-700"
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
                  class="rounded p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-700"
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
      <div v-if="productos.length === 0" class="py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay proyectos</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Comienza creando un nuevo proyecto.
        </p>
      </div>
    </div>
  </div>

  <!-- Dialog Editar -->
  <ProyectosEditDialog
    v-if="showEditDialog && selectedProduct"
    :product="selectedProduct"
    :categorias="categorias"
    @close="showEditDialog = false"
    @updated="handleProductUpdated"
  />
</template>