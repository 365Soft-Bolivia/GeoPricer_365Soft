<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useToast } from 'primevue/usetoast';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
  activo: boolean;
  usuarios_count: number;
}

const props = defineProps<{
  role: Role;
}>();

const emit = defineEmits<{
  save: [];
  cancel: [];
}>();

const toast = useToast();

const form = useForm({
  nombre: props.role.nombre,
  descripcion: props.role.descripcion || '',
  activo: props.role.activo,
});

const isSubmitting = ref(false);

// Watch para actualizar el formulario si cambia el rol
watch(() => props.role, (newRole) => {
  form.nombre = newRole.nombre;
  form.descripcion = newRole.descripcion || '';
  form.activo = newRole.activo;
}, { immediate: true });

const handleSubmit = () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  form.put(`/roles/${props.role.id}`, {
    preserveScroll: true,
    onSuccess: (page) => {
      const message = page.props.flash?.success || 'Rol actualizado correctamente';
      toast.add({ 
        severity: 'success', 
        summary: 'Éxito', 
        detail: message,
        life: 3000 
      });
      emit('save');
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0] as string;
      toast.add({ 
        severity: 'error', 
        summary: 'Error', 
        detail: firstError || 'Error al actualizar el rol',
        life: 3000 
      });
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const handleCancel = () => {
  form.reset();
  emit('cancel');
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-5">
    <div class="space-y-2">
      <Label for="edit-nombre" class="text-sm font-medium">
        Nombre del Rol <span class="text-red-500">*</span>
      </Label>
      <Input
        id="edit-nombre"
        v-model="form.nombre"
        placeholder="Ej: Administrador, Editor, Supervisor"
        :class="{ 'border-red-500': form.errors.nombre }"
        required
      />
      <p v-if="form.errors.nombre" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.nombre }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="edit-descripcion" class="text-sm font-medium">
        Descripción
      </Label>
      <Textarea
        id="edit-descripcion"
        v-model="form.descripcion"
        placeholder="Describe las responsabilidades y permisos de este rol..."
        rows="4"
        :class="{ 'border-red-500': form.errors.descripcion }"
      />
      <p v-if="form.errors.descripcion" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.descripcion }}
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Máximo 500 caracteres
      </p>
    </div>

    <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
      <div class="space-y-0.5">
        <Label for="edit-activo" class="text-sm font-medium">
          Estado Activo
        </Label>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          El rol estará disponible para asignar a usuarios
        </p>
      </div>
      <Switch
        id="edit-activo"
        v-model:checked="form.activo"
      />
    </div>

    <!-- Advertencia si tiene usuarios asignados -->
    <div 
      v-if="role.usuarios_count > 0" 
      class="flex items-start gap-3 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800"
    >
      <div class="text-amber-600 dark:text-amber-400 mt-0.5">⚠️</div>
      <div class="flex-1 text-sm">
        <p class="font-medium text-amber-800 dark:text-amber-200">
          Este rol tiene {{ role.usuarios_count }} usuario(s) asignado(s)
        </p>
        <p class="text-amber-700 dark:text-amber-300 mt-1">
          Los cambios afectarán a todos los usuarios con este rol.
        </p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-gray-700">
      <Button
        type="button"
        variant="outline"
        @click="handleCancel"
        :disabled="isSubmitting"
      >
        Cancelar
      </Button>
      <Button
        type="submit"
        :disabled="isSubmitting || !form.nombre"
      >
        {{ isSubmitting ? 'Guardando...' : 'Guardar Cambios' }}
      </Button>
    </div>
  </form>
</template>