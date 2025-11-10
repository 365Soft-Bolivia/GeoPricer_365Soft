<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface ProductImage {
  id: number;
  image_path: string; // ✅ Cambiar url por image_path
  original_name: string;
  is_primary: boolean;
  order: number;
}

interface Props {
  images: ProductImage[];
  productId: number;
  canEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
});

const selectedImage = ref<ProductImage | null>(null);
const showLightbox = ref(false);

// ✅ Helper para generar URL pública
const getImageUrl = (imagePath: string) => {
  return `/storage/${imagePath}`;
};

const setPrimary = (imageId: number) => {
  if (confirm('¿Establecer como imagen principal?')) {
    router.post(`/proyectos/${props.productId}/imagenes/${imageId}/principal`, {}, {
      preserveScroll: true,
    });
  }
};

const deleteImage = (imageId: number) => {
  if (confirm('¿Estás seguro de eliminar esta imagen?')) {
    router.delete(`/proyectos/${props.productId}/imagenes/${imageId}`, {
      preserveScroll: true,
    });
  }
};

const openLightbox = (image: ProductImage) => {
  selectedImage.value = image;
  showLightbox.value = true;
};

const closeLightbox = () => {
  showLightbox.value = false;
  selectedImage.value = null;
};

const nextImage = () => {
  const currentIndex = props.images.findIndex(img => img.id === selectedImage.value?.id);
  const nextIndex = (currentIndex + 1) % props.images.length;
  selectedImage.value = props.images[nextIndex];
};

const prevImage = () => {
  const currentIndex = props.images.findIndex(img => img.id === selectedImage.value?.id);
  const prevIndex = (currentIndex - 1 + props.images.length) % props.images.length;
  selectedImage.value = props.images[prevIndex];
};
</script>

<template>
  <div>
    <!-- Empty state -->
    <div v-if="images.length === 0" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
      <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Sin imágenes</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Este proyecto aún no tiene imágenes
      </p>
    </div>

    <!-- Galería -->
    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <div
        v-for="image in images"
        :key="image.id"
        class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
      >
        <!-- ✅ CORRECCIÓN: Usar getImageUrl() -->
        <img
          :src="getImageUrl(image.image_path)"
          :alt="image.original_name"
          class="h-full w-full cursor-pointer object-cover transition-transform duration-300 group-hover:scale-110"
          @click="openLightbox(image)"
          @error="(e) => (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EError%3C/text%3E%3C/svg%3E'"
        />

        <!-- Badge principal -->
        <div v-if="image.is_primary" class="absolute left-2 top-2">
          <span class="inline-flex items-center gap-1 rounded-full bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            Principal
          </span>
        </div>

        <!-- Acciones (solo si canEdit) -->
        <div v-if="canEdit" class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
          <button
            v-if="!image.is_primary"
            @click.stop="setPrimary(image.id)"
            class="rounded-full bg-white p-2 text-gray-900 hover:bg-gray-100"
            title="Establecer como principal"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </button>
          <button
            @click.stop="deleteImage(image.id)"
            class="rounded-full bg-red-600 p-2 text-white hover:bg-red-700"
            title="Eliminar imagen"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showLightbox && selectedImage"
          class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/90"
          @click="closeLightbox"
        >
          <button
            @click.stop="closeLightbox"
            class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <button
            v-if="images.length > 1"
            @click.stop="prevImage"
            class="absolute left-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- ✅ CORRECCIÓN: Usar getImageUrl() en lightbox -->
          <img
            :src="getImageUrl(selectedImage.image_path)"
            :alt="selectedImage.original_name"
            class="max-h-[90vh] max-w-[90vw] object-contain"
            @click.stop
          />

          <button
            v-if="images.length > 1"
            @click.stop="nextImage"
            class="absolute right-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <div class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-4 py-2 text-sm text-white">
            {{ images.findIndex(img => img.id === selectedImage.id) + 1 }} / {{ images.length }}
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>