<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Pencil, Trash2, Ban, CheckCircle } from 'lucide-vue-next';
import AccesosEditDialog from './AccesosEditDialog.vue';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from 'primevue/useconfirm';

interface Role {
  id: number;
  nombre: string;
}

interface Usuario {
  id: number;
  name: string;
  email: string;
  estado: number;
  status: string;
  role: Role | null;
  created_at: string;
}

const props = defineProps<{
  usuario: Usuario;
  roles: Role[];
}>();

const showEditDialog = ref(false);
const { showSuccess, showError } = useNotification();
const confirm = useConfirm();

const getRoleBadgeVariant = (roleName: string) => {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    admin: 'destructive',
    editor: 'default',
    test: 'secondary',
  };
  return variants[roleName] || 'outline';
};

const handleToggleStatus = () => {
  // Usa route() helper o construye la ruta correctamente
  router.post(
    `/accesos/${props.usuario.id}/toggle-status`, 
    {}, 
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        const wasActive = props.usuario.estado === 1;
        showSuccess(
          'Estado actualizado',
          `El usuario ${props.usuario.name} ha sido ${wasActive ? 'desactivado' : 'activado'} exitosamente.`
        );
      },
      onError: (errors) => {
        console.error('Error al cambiar estado:', errors);
        
        // ✅ Mostrar el mensaje de error del servidor si existe
        const errorMessage = errors.message || 'No se pudo cambiar el estado del usuario.';
        
        showError('Error al cambiar estado', errorMessage);
      }
    }
  );
};

const handleDelete = () => {
  router.delete(`/accesos/${props.usuario.id}`, {
    preserveScroll: true,
    preserveState: true,
    only: ['usuarios'],
    onSuccess: () => {
      showSuccess(
        'Usuario eliminado',
        `El usuario ${props.usuario.name} ha sido eliminado exitosamente.`
      );
    },
    onError: (errors) => {
      console.error('Error al eliminar:', errors);
      showError(
        'Error al eliminar',
        'No se pudo eliminar el usuario. Por favor, intenta nuevamente.'
      );
    }
  });
};

const confirmToggleStatus = () => {
  confirm.require({
    message: `¿Estás seguro de ${props.usuario.estado === 1 ? 'desactivar' : 'activar'} a ${props.usuario.name}?`,
    header: props.usuario.estado === 1 ? 'Desactivar Usuario' : 'Activar Usuario',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: props.usuario.estado === 1 ? 'Desactivar' : 'Activar',
    rejectLabel: 'Cancelar',
    acceptClass: props.usuario.estado === 1 ? 'p-button-danger' : 'p-button-success',
    accept: handleToggleStatus
  });
};

const confirmDelete = () => {
  confirm.require({
    message: `¿Estás seguro de eliminar al usuario ${props.usuario.name}? Esta acción no se puede deshacer.`,
    header: 'Eliminar Usuario',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: handleDelete
  });
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm font-medium text-gray-900 dark:text-white">
        {{ usuario.name }}
      </div>
      <div class="text-xs text-gray-500 dark:text-gray-400">
        {{ formatDate(usuario.created_at) }}
      </div>
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        {{ usuario.email }}
      </div>
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
      <Badge v-if="usuario.role" :variant="getRoleBadgeVariant(usuario.role.nombre)">
        {{ usuario.role.nombre }}
      </Badge>
      <span v-else class="text-sm italic text-gray-400">
        Sin rol
      </span>
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
      <Badge :variant="usuario.estado === 1 ? 'default' : 'destructive'">
        {{ usuario.estado === 1 ? 'Activo' : 'Inactivo' }}
      </Badge>
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center gap-2">
        <!-- Botón activar/desactivar -->
        <Button
          variant="ghost"
          size="icon"
          :title="usuario.estado === 1 ? 'Desactivar' : 'Activar'"
          @click="confirmToggleStatus"
        >
          <Ban v-if="usuario.estado === 1" class="h-4 w-4 text-orange-600" />
          <CheckCircle v-else class="h-4 w-4 text-green-600" />
        </Button>

        <!-- Botón editar -->
        <Button variant="ghost" size="icon" @click="showEditDialog = true" title="Editar">
          <Pencil class="h-4 w-4 text-blue-600" />
        </Button>

        <!-- Botón eliminar -->
        <Button variant="ghost" size="icon" @click="confirmDelete" title="Eliminar">
          <Trash2 class="h-4 w-4 text-red-600" />
        </Button>
      </div>
    </td>

    <!-- Dialog para editar -->
    <AccesosEditDialog
      v-if="showEditDialog"
      :open="showEditDialog"
      :usuario="usuario"
      :roles="roles"
      @update:open="showEditDialog = $event"
    />
  </tr>
</template>
