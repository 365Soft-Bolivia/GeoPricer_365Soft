<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import ProyectosCreateForm from './ProyectosCreateForm.vue';
import { admin } from '@/routes-custom';

const { proyectos } = admin;

interface Category {
  id: number;
  category_name: string;
}

const props = defineProps<{
  categorias: Category[];
  filters?: {
    search?: string;
    categoria?: number;
    estado?: number;
  };
}>();

const showCreateDialog = ref(false);
const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.categoria || '');
const selectedEstado = ref(props.filters?.estado || '');

// Búsqueda con debounce
const debouncedSearch = useDebounceFn(() => {
  applyFilters();
}, 300);

// Aplicar todos los filtros
const applyFilters = () => {
  const filters: Record<string, string | number> = {};

  if (search.value) {
    filters.search = search.value;
  }
  if (selectedCategory.value) {
    filters.categoria = selectedCategory.value;
  }
  if (selectedEstado.value) {
    filters.estado = selectedEstado.value;
  }

  router.get(proyectos.index().url, filters, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

// Limpiar todos los filtros
const clearFilters = () => {
  search.value = '';
  selectedCategory.value = '';
  selectedEstado.value = '';

  router.get(proyectos.index().url, {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

watch([search, selectedCategory, selectedEstado], () => {
  debouncedSearch();
});

const handleProductCreated = () => {
  showCreateDialog.value = false;
  router.reload({ only: ['productos'] });
};

//Función para ir a la página de detalles
const verDetalles = (proyectoId: number) => {
  router.visit(proyectos.show(proyectoId).url);
};

const goToDetalles = (id: number) => {
  router.visit(proyectos.show(id).url);
};

</script>

<template>
  <!-- Header Section -->
  <div class="bg-white rounded-2xl shadow-lg p-10 mb-10 border-l-4" style="border-color: #233C7A;">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
      <!-- Título y Descripción -->
      <div class="flex-1">
        <h1 class="text-4xl font-bold mb-3" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #212121;">
          Gestión de Proyectos
        </h1>
        <p class="text-lg" style="color: #212121; opacity: 0.7;">
          Administra los inmuebles y proyectos de la empresa
        </p>
      </div>

      <!-- Acciones: Buscador, Filtros y Botón Crear -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <!-- Buscador -->
        <div class="relative flex-1">
          <input
            v-model="search"
            type="text"
            placeholder="Buscar por nombre, código o categoría..."
            class="w-full rounded-xl border-2 px-5 py-3 pl-12 text-sm transition-all focus:outline-none focus:ring-2"
            style="border-color: #e0e0e0; background-color: #FAFAFA; color: #212121; focus:border-color: #233C7A; focus:ring-color: rgba(35, 60, 122, 0.1);"
          />
          <svg
            class="absolute left-4 top-3.5 h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            style="color: #212121; opacity: 0.4;"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>

        <!-- Filtro por Categoría -->
        <div class="flex-1">
          <select
            v-model="selectedCategory"
            class="w-full rounded-xl border-2 px-5 py-3 text-sm transition-all focus:outline-none focus:ring-2"
            style="border-color: #e0e0e0; background-color: #FAFAFA; color: #212121; focus:border-color: #233C7A; focus:ring-color: rgba(35, 60, 122, 0.1);"
          >
            <option value="">Todas las Categorías</option>
            <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
              {{ categoria.category_name }}
            </option>
          </select>
        </div>

        <!-- Filtro por Estado -->
        <div class="flex-1">
          <select
            v-model="selectedEstado"
            class="w-full rounded-xl border-2 px-5 py-3 text-sm transition-all focus:outline-none focus:ring-2"
            style="border-color: #e0e0e0; background-color: #FAFAFA; color: #212121; focus:border-color: #233C7A; focus:ring-color: rgba(35, 60, 122, 0.1);"
          >
            <option value="">Todos los Estados</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>

        <!-- Botón Limpiar Filtros -->
        <button
          @click="clearFilters"
          v-if="search || selectedCategory || selectedEstado"
          class="rounded-xl border-2 px-5 py-3 text-sm font-semibold transition-all hover:bg-gray-100 focus:outline-none focus:ring-2"
          style="border-color: #e0e0e0; background-color: #FAFAFA; color: #212121; focus:border-color: #233C7A; focus:ring-color: rgba(35, 60, 122, 0.1);"
        >
          Limpiar
        </button>

        <!-- Botón Crear Proyecto -->
        <button
          @click="showCreateDialog = true"
          class="inline-flex items-center justify-center rounded-xl px-6 py-3 text-base font-semibold text-white transition-all hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2"
          style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%); focus:ring-color: #233C7A;"
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
              stroke-width="2.5"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Nuevo Proyecto
        </button>
      </div>
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