<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

interface ProductImage {
  id: number;
  image_path: string;
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

const getImageUrl = (imagePath: string) => {
  return `/storage/${imagePath}`;
};

const currentIndex = computed(() => {
  if (!selectedImage.value) return 0;
  return props.images.findIndex(img => img.id === selectedImage.value?.id);
});

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
  console.log('Abriendo lightbox para:', image);
  selectedImage.value = image;
  showLightbox.value = true;
  document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
  showLightbox.value = false;
  selectedImage.value = null;
  document.body.style.overflow = '';
};

const nextImage = () => {
  const nextIndex = (currentIndex.value + 1) % props.images.length;
  selectedImage.value = props.images[nextIndex];
};

const prevImage = () => {
  const prevIndex = (currentIndex.value - 1 + props.images.length) % props.images.length;
  selectedImage.value = props.images[prevIndex];
};

// Navegación con teclado
const handleKeydown = (e: KeyboardEvent) => {
  if (!showLightbox.value) return;
  
  switch(e.key) {
    case 'Escape':
      closeLightbox();
      break;
    case 'ArrowRight':
      nextImage();
      break;
    case 'ArrowLeft':
      prevImage();
      break;
  }
};

// Agregar listener de teclado
if (typeof window !== 'undefined') {
  window.addEventListener('keydown', handleKeydown);
}
</script>

<template>
  <div>
    <!-- Estado vacío -->
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
        class="group relative aspect-square cursor-pointer overflow-hidden rounded-xl bg-gray-100 shadow-md transition-all duration-300 hover:shadow-2xl dark:bg-gray-800"
      >
        <img
          :src="getImageUrl(image.image_path)"
          :alt="image.original_name"
          class="h-full w-full object-cover transition-all duration-500 group-hover:scale-110 group-hover:brightness-75"
          @click="openLightbox(image)"
          loading="lazy"
        />

        <!-- Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

        <!-- Badge principal -->
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-0 opacity-0"
          enter-to-class="scale-100 opacity-100"
        >
          <div v-if="image.is_primary" class="absolute left-3 top-3">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 px-3 py-1.5 text-xs font-bold text-white shadow-lg">
              <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              Principal
            </span>
          </div>
        </Transition>

        <!-- 🔍 Botón de zoom funcional -->
        <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
          <button
            @click.stop="openLightbox(image)"
            class="rounded-full bg-white/90 p-4 shadow-xl backdrop-blur-sm hover:scale-110 transition-transform"
            title="Ver imagen ampliada"
          >
            <svg class="h-8 w-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
            </svg>
          </button>
        </div>

        <!-- Acciones inferiores (editar / eliminar) -->
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="translate-y-full opacity-0"
          enter-to-class="translate-y-0 opacity-100"
        >
          <div v-if="canEdit" class="absolute bottom-0 left-0 right-0 flex gap-2 bg-gradient-to-t from-black/80 to-transparent p-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <button
              v-if="!image.is_primary"
              @click.stop="setPrimary(image.id)"
              class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-white/90 px-3 py-2 text-xs font-semibold text-gray-900 backdrop-blur-sm transition-all hover:bg-white hover:scale-105"
              title="Establecer como principal"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
              </svg>
              Principal
            </button>
            <button
              @click.stop="deleteImage(image.id)"
              class="flex items-center justify-center rounded-lg bg-red-500/90 px-3 py-2 text-white backdrop-blur-sm transition-all hover:bg-red-600 hover:scale-105"
              title="Eliminar imagen"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showLightbox && selectedImage"
          class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/95 backdrop-blur-sm"
          @click="closeLightbox"
        >
          <!-- Header -->
          <div class="absolute left-0 right-0 top-0 flex items-center justify-between bg-gradient-to-b from-black/80 to-transparent p-6">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white/10 px-3 py-1 backdrop-blur-sm">
                <span class="text-sm font-medium text-white">
                  {{ currentIndex + 1 }} / {{ images.length }}
                </span>
              </div>
              <div class="max-w-md truncate text-sm text-white/80">
                {{ selectedImage.original_name }}
              </div>
            </div>

            <!-- Botón cerrar -->
            <button
              @click.stop="closeLightbox"
              class="group rounded-full bg-white/10 p-3 backdrop-blur-sm transition-all hover:bg-white/20 hover:rotate-90"
            >
              <svg class="h-6 w-6 text-white transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Botón anterior -->
          <button
            v-if="images.length > 1"
            @click.stop="prevImage"
            class="group absolute left-6 rounded-full bg-white/10 p-4 backdrop-blur-sm transition-all hover:bg-white/20 hover:scale-110"
          >
            <svg class="h-8 w-8 text-white transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Imagen principal -->
          <div :key="selectedImage.id" class="flex max-h-[85vh] max-w-[90vw] items-center justify-center" @click.stop>
            <img
              :src="getImageUrl(selectedImage.image_path)"
              :alt="selectedImage.original_name"
              class="max-h-full max-w-full rounded-lg object-contain shadow-2xl"
            />
          </div>

          <!-- Botón siguiente -->
          <button
            v-if="images.length > 1"
            @click.stop="nextImage"
            class="group absolute right-6 rounded-full bg-white/10 p-4 backdrop-blur-sm transition-all hover:bg-white/20 hover:scale-110"
          >
            <svg class="h-8 w-8 text-white transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Thumbnails -->
          <div v-if="images.length > 1" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6">
            <div class="mx-auto flex max-w-4xl gap-3 overflow-x-auto pb-2">
              <button
                v-for="(image, index) in images"
                :key="image.id"
                @click.stop="selectedImage = image"
                :class="image.id === selectedImage.id 
                  ? 'ring-4 ring-white scale-110' 
                  : 'opacity-50 hover:opacity-100 hover:scale-105'"
                class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg transition-all"
              >
                <img
                  :src="getImageUrl(image.image_path)"
                  :alt="`Thumbnail ${index + 1}`"
                  class="h-full w-full object-cover"
                />
                <div v-if="image.id === selectedImage.id" class="absolute inset-0 bg-white/20"></div>
              </button>
            </div>
          </div>

          <!-- Atajo de teclado -->
          <div class="absolute bottom-6 left-6 rounded-lg bg-black/50 px-3 py-2 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-xs text-white/80">
              <kbd class="rounded bg-white/10 px-2 py-1 font-mono">←</kbd>
              <kbd class="rounded bg-white/10 px-2 py-1 font-mono">→</kbd>
              <span>Navegar</span>
              <span class="mx-2">•</span>
              <kbd class="rounded bg-white/10 px-2 py-1 font-mono">ESC</kbd>
              <span>Cerrar</span>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
