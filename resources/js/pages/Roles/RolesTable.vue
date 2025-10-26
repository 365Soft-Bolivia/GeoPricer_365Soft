<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle 
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Users, Power } from 'lucide-vue-next';
import Toast from 'primevue/toast';
import RolesEditForm from './RolesEditForm.vue';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
  activo: boolean;
  usuarios_count: number;
  created_at: string;
}

const props = defineProps<{
  roles: Role[];
}>();

const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedRole = ref<Role | null>(null);

const openEditDialog = (role: Role) => {
  selectedRole.value = role;
  showEditDialog.value = true;
};

const openDeleteDialog = (role: Role) => {
  selectedRole.value = role;
  showDeleteDialog.value = true;
};

const handleEdit = () => {
  showEditDialog.value = false;
  selectedRole.value = null;
};

const handleToggleStatus = (role: Role) => {
  router.post(
    `/roles/${role.id}/toggle-status`,
    {},
    {
      preserveScroll: true,
      onSuccess: (page) => {
        const message = page.props.flash?.success;
        if (message) {
          Toast.success(message);
        }
      },
      onError: (errors) => {
        const errorMessage = errors?.error || 'Error al cambiar el estado del rol';
        Toast.error(errorMessage);
      },
    }
  );
};

const handleDelete = () => {
  if (!selectedRole.value) return;

  router.delete(`/roles/${selectedRole.value.id}`, {
    preserveScroll: true,
    onSuccess: (page) => {
      const message = page.props.flash?.success;
      if (message) {
        toast.success(message);
      }
      showDeleteDialog.value = false;
      selectedRole.value = null;
    },
    onError: (errors) => {
      const errorMessage = errors?.error || 'Error al eliminar el rol';
      Toast.error(errorMessage);
      showDeleteDialog.value = false;
      selectedRole.value = null;
    },
  });
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>

<template>
  <div class="px-6">
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
      <Table>
        <TableHeader>
          <TableRow class="bg-gray-50 dark:bg-gray-900">
            <TableHead class="font-semibold">Nombre</TableHead>
            <TableHead class="font-semibold">Descripción</TableHead>
            <TableHead class="font-semibold text-center">Usuarios</TableHead>
            <TableHead class="font-semibold text-center">Estado</TableHead>
            <TableHead class="font-semibold">Creado</TableHead>
            <TableHead class="font-semibold text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow 
            v-for="role in roles" 
            :key="role.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
          >
            <TableCell class="font-medium">
              {{ role.nombre }}
            </TableCell>
            <TableCell class="text-gray-600 dark:text-gray-400">
              {{ role.descripcion || 'Sin descripción' }}
            </TableCell>
            <TableCell class="text-center">
              <div class="flex items-center justify-center gap-1">
                <Users class="h-4 w-4 text-gray-500" />
                <span class="font-medium">{{ role.usuarios_count }}</span>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <Badge 
                :variant="role.activo ? 'default' : 'secondary'"
                class="cursor-pointer"
                @click="handleToggleStatus(role)"
              >
                <Power class="h-3 w-3 mr-1" />
                {{ role.activo ? 'Activo' : 'Inactivo' }}
              </Badge>
            </TableCell>
            <TableCell class="text-gray-600 dark:text-gray-400">
              {{ formatDate(role.created_at) }}
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-2">
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="openEditDialog(role)"
                  class="hover:bg-blue-50 dark:hover:bg-blue-900/20"
                >
                  <Edit2 class="h-4 w-4" />
                </Button>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="openDeleteDialog(role)"
                  class="hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400"
                  :disabled="role.usuarios_count > 0"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </TableCell>
          </TableRow>
          
          <!-- Empty state -->
          <TableRow v-if="roles.length === 0">
            <TableCell :colspan="6" class="text-center py-12">
              <div class="flex flex-col items-center gap-2">
                <Users class="h-12 w-12 text-gray-400" />
                <p class="text-gray-600 dark:text-gray-400 font-medium">
                  No se encontraron roles
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                  Intenta ajustar los filtros de búsqueda
                </p>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Edit Dialog -->
    <Dialog v-model:open="showEditDialog">
      <DialogContent class="sm:max-w-[500px] dark:bg-gray-900" :show-close="false">
        <DialogHeader>
          <DialogTitle>Editar Rol</DialogTitle>
        </DialogHeader>
        <RolesEditForm 
          v-if="selectedRole"
          :role="selectedRole"
          @save="handleEdit" 
          @cancel="showEditDialog = false" 
        />
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent class="dark:bg-gray-900">
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription class="space-y-2">
            <p>
              Esta acción eliminará permanentemente el rol 
              <span class="font-semibold text-gray-900 dark:text-white">
                "{{ selectedRole?.nombre }}"
              </span>.
            </p>
            <p class="text-red-600 dark:text-red-400 font-medium" v-if="selectedRole && selectedRole.usuarios_count > 0">
              ⚠️ Este rol tiene {{ selectedRole.usuarios_count }} usuario(s) asignado(s).
            </p>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showDeleteDialog = false">
            Cancelar
          </AlertDialogCancel>
          <AlertDialogAction
            @click="handleDelete"
            class="bg-red-600 hover:bg-red-700 focus:ring-red-600"
            :disabled="selectedRole && selectedRole.usuarios_count > 0"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>