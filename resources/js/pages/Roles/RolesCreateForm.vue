<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useToast } from 'primevue/usetoast';

const emit = defineEmits<{
  save: [];
  cancel: [];
}>();

const toast = useToast();

const form = useForm({
  nombre: '',
  descripcion: '',
  activo: true,
});

const isSubmitting = ref(false);

const handleSubmit = () => {
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  form.post('/roles', {
    preserveScroll: true,
    onSuccess: (page) => {
      const message = page.props.flash?.success || 'Rol creado correctamente';
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: message,
        life: 3000
      });
      emit('save');
      form.reset();
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0] as string;
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: firstError || 'Error al crear el rol',
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
      <Label for="nombre" class="text-sm font-medium">
        Nombre del Rol <span class="text-red-500">*</span>
      </Label>
      <Input
        id="nombre"
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
      <Label for="descripcion" class="text-sm font-medium">
        Descripción
      </Label>
      <Textarea
        id="descripcion"
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
        <Label for="activo" class="text-sm font-medium">
          Estado Activo
        </Label>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          El rol estará disponible para asignar a usuarios
        </p>
      </div>
      <Switch
        id="activo"
        v-model:checked="form.activo"
      />
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
        {{ isSubmitting ? 'Creando...' : 'Crear Rol' }}
      </Button>
    </div>
  </form>
</template>