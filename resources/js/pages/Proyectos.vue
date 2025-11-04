<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProyectosHeader from '@/pages/Proyectos/ProyectosHeader.vue';
import ProyectosTable from '@/pages/Proyectos/ProyectosTable.vue';

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
  filters?: {
    search?: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Proyectos', href: '/proyectos' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Productos" />
    <div class="py-6">
      <ProyectosHeader :categorias="categorias" :filters="filters" />
      <ProyectosTable :productos="productos" :categorias="categorias" />
    </div>
  </AppLayout>
</template>