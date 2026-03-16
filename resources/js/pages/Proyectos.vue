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
  price_usd?: number | null;
  price_bob?: number | null;
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

defineProps<{
  productos: Producto[];
  categorias: Category[];
  pagination?: Pagination;
  filters?: {
    search?: string;
    categoria?: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Proyectos', href: '/admin/proyectos' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Productos" />
    <div class="bg-[#F5F5F5] min-h-[calc(100vh-200px)]">
      <div class="max-w-7xl mx-auto px-8 py-10">
        <ProyectosHeader :categorias="categorias" :filters="filters" />
        <ProyectosTable :productos="productos" :categorias="categorias" :pagination="pagination" />
      </div>
    </div>
  </AppLayout>
</template>
