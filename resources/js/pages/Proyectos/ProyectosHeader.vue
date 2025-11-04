<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import ProyectosCreateForm from './ProyectosCreateForm.vue';

interface Category {
  id: number;
  category_name: string;
}

const props = defineProps<{
  categorias: Category[];
  filters?: {
    search?: string;
  };
}>();

const showCreateDialog = ref(false);
const search = ref(props.filters?.search || '');

// Búsqueda con debounce
const debouncedSearch = useDebounceFn(() => {
  router.get('/proyectos', 
    { search: search.value },
    { 
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );
}, 300);

watch(search, () => {
  debouncedSearch();
});

const handleProductCreated = () => {
  showCreateDialog.value = false;
  router.reload({ only: ['productos'] });
};
</script>

<template>
  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex-1">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Gestión de Proyectos
      </h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Administra los inmuebles y proyectos de la empresa
      </p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <!-- Buscador -->
      <div class="relative">
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por nombre, código o categoría..."
          class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:w-80"
        />
        <svg
          class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>

      <!-- Botón Crear Proyecto -->
      <button
        @click="showCreateDialog = true"
        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        <svg
          class="-ml-1 mr-2 h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Nuevo Proyecto
      </button>
    </div>
  </div>

  <!-- Dialog Crear Proyecto -->
  <ProyectosCreateForm
    v-if="showCreateDialog"
    :categorias="categorias"
    @close="showCreateDialog = false"
    @created="handleProductCreated"
  />
</template>