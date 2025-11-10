<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
  productId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  uploaded: [];
}>();

const form = useForm({
  images: [] as File[],
});

const isDragging = ref(false);
const previewUrls = ref<string[]>([]);

const handleFiles = (files: FileList | null) => {
  if (!files) return;

  const newFiles = Array.from(files).filter(file => {
    if (!file.type.startsWith('image/')) {
      alert(`${file.name} no es una imagen válida`);
      return false;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert(`${file.name} supera los 5MB`);
      return false;
    }
    return true;
  });

  form.images.push(...newFiles);
  
  // Generar previews
  newFiles.forEach(file => {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewUrls.value.push(e.target?.result as string);
    };
    reader.readAsDataURL(file);
  });
};

const onDrop = (e: DragEvent) => {
  isDragging.value = false;
  handleFiles(e.dataTransfer?.files || null);
};

const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  handleFiles(target.files);
};

const removeFile = (index: number) => {
  form.images.splice(index, 1);
  previewUrls.value.splice(index, 1);
};

const submit = () => {
  form.post(`/proyectos/${props.productId}/imagenes`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      previewUrls.value = [];
      emit('uploaded');
    },
  });
};
</script>

<template>
  <div class="space-y-4">
    <!-- Zona de arrastrar y soltar -->
    <div
      @drop.prevent="onDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'"
      class="relative cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors"
    >
      <input
        type="file"
        multiple
        accept="image/*"
        @change="onFileChange"
        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
      />
      
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-blue-600 dark:text-blue-400">Haz clic para subir</span> o arrastra y suelta
      </p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        PNG, JPG, GIF, WEBP hasta 5MB (máx. 10 imágenes)
      </p>
    </div>

    <!-- Vista previa de imágenes seleccionadas -->
    <div v-if="previewUrls.length > 0" class="space-y-3">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Imágenes seleccionadas ({{ previewUrls.length }})
      </h4>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div v-for="(url, index) in previewUrls" :key="index" class="group relative">
          <img :src="url" :alt="`Preview ${index + 1}`" class="h-32 w-full rounded-lg object-cover" />
          <button
            type="button"
            @click="removeFile(index)"
            class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <div class="absolute bottom-0 left-0 right-0 rounded-b-lg bg-black/50 px-2 py-1">
            <p class="truncate text-xs text-white">{{ form.images[index]?.name }}</p>
          </div>
        </div>
      </div>

      <!-- Botón subir -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          @click="previewUrls = []; form.reset()"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Cancelar
        </button>
        <button
          type="button"
          @click="submit"
          :disabled="form.processing"
          class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        >
          <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ form.processing ? 'Subiendo...' : 'Subir imágenes' }}
        </button>
      </div>
    </div>

    <!-- Errores -->
    <div v-if="form.errors.images" class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
      <p class="text-sm text-red-600 dark:text-red-400">{{ form.errors.images }}</p>
    </div>
  </div>
</template>