<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import RolesHeader from '@/pages/Roles/RolesHeader.vue';
import RolesTable from '@/pages/Roles/RolesTable.vue';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
  activo: boolean;
  usuarios_count: number;
  created_at: string;
}

defineProps<{
  roles: Role[];
  filters?: {
    search?: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accesos', href: '/accesos' },
  { title: 'Roles', href: '/roles' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Roles" />
    <div class="py-6">
      <RolesHeader :filters="filters" />
      <RolesTable :roles="roles" />
    </div>
  </AppLayout>
</template>