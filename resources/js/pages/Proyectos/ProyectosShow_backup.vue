<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import ProyectosImageUploader from './ProyectosImageUploader.vue';

interface Category {
  id: number;
  category_name: string;
}

interface ProductImage {
  id: number;
  image_path: string;
  original_name: string;
  is_primary: boolean;
  order: number;
}

interface Producto {
  id: number;
  name: string;
  codigo_inmueble: string;
  price_usd?: number | null;
  price_bob?: number | null;
  superficie_util?: number;
  superficie_construida?: number;
  ambientes?: number;
  habitaciones?: number;
  banos?: number;
  cocheras?: number;
  ano_construccion?: number;
  operacion: string;
  comision?: number;
  taxes?: number;
  description?: string;
  sku?: string;
  hsn_sac_code?: string;
  allow_purchase?: boolean;
  is_public?: boolean;
  downloadable?: boolean;
  downloadable_file?: string;
  estado: number;
  category: Category | null;
  images: ProductImage[];
  created_at: string;
}

const props = defineProps<{
  producto: Producto;
  categorias: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Proyectos', href: '/admin/proyectos' },
  { title: props.producto.name, href: `/admin/proyectos/${props.producto.id}` },
];

// Estado para modales
const showEditModal = ref(false);
const editSection = ref<string>('');
const editForm = ref<any>({});
const isSaving = ref(false);
const showSuccessModal = ref(false);
const successMessage = ref('');

// Estado para el carrusel de imágenes
const showImageModal = ref(false);
const currentImageIndex = ref(0);
const isPlaying = ref(true);
const autoplayInterval = ref<number | null>(null);

const formatPrice = (price?: number | null, symbol: string = '$') => {
  if (!price) return '';
  return `${symbol}${price.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getOperacionLabel = (operacion: string) => {
  const labels: Record<string, string> = {
    'venta': 'Venta',
    'alquiler': 'Alquiler',
    'anticretico': 'Anticrético'
  };
  return labels[operacion] || operacion;
};

const getImageUrl = (imagePath: string) => {
  return `/storage/${imagePath}`;
};

// Abrir modal de edición
const openEditModal = (section: string) => {
  editSection.value = section;

  if (section === 'basica') {
    editForm.value = {
      name: props.producto.name,
      operacion: props.producto.operacion,
      category_id: props.producto.category?.id,
      price_usd: props.producto.price_usd,
      price_bob: props.producto.price_bob,
      comision: props.producto.comision,
      taxes: props.producto.taxes,
    };
  } else if (section === 'caracteristicas') {
    editForm.value = {
      superficie_util: props.producto.superficie_util,
      superficie_construida: props.producto.superficie_construida,
      ambientes: props.producto.ambientes,
      habitaciones: props.producto.habitaciones,
      banos: props.producto.banos,
      cocheras: props.producto.cocheras,
      ano_construccion: props.producto.ano_construccion,
    };
  } else if (section === 'descripcion') {
    editForm.value = {
      description: props.producto.description,
    };
  } else if (section === 'configuracion') {
    editForm.value = {
      allow_purchase: props.producto.allow_purchase,
      is_public: props.producto.is_public,
      downloadable: props.producto.downloadable,
    };
  } else if (section === 'tecnica') {
    editForm.value = {
      sku: props.producto.sku,
      hsn_sac_code: props.producto.hsn_sac_code,
    };
  }

  showEditModal.value = true;
};

// Guardar cambios
const saveChanges = async () => {
  isSaving.value = true;

  try {
    if (editSection.value === 'basica') {
      await router.put(`/admin/proyectos/${props.producto.id}`, editForm.value);
    } else if (editSection.value === 'caracteristicas') {
      await router.put(`/admin/proyectos/${props.producto.id}`, editForm.value);
    } else if (editSection.value === 'descripcion') {
      await router.put(`/admin/proyectos/${props.producto.id}`, editForm.value);
    } else if (editSection.value === 'configuracion') {
      await router.put(`/admin/proyectos/${props.producto.id}`, editForm.value);
    } else if (editSection.value === 'tecnica') {
      await router.put(`/admin/proyectos/${props.producto.id}`, editForm.value);
    }

    showEditModal.value = false;
    editForm.value = {};
    successMessage.value = 'Proyecto actualizado correctamente';
    showSuccessModal.value = true;

    // Recargar la página después de 2 segundos para mostrar los datos actualizados
    setTimeout(() => {
      router.reload({ only: ['producto'] });
      showSuccessModal.value = false;
    }, 2000);
  } catch (error) {
    console.error('Error al guardar:', error);
    alert('Error al guardar los cambios. Por favor intenta nuevamente.');
  } finally {
    isSaving.value = false;
  }
};

const handleImageUploaded = () => {
  router.reload({ only: ['producto'] });
};

// Toggle rápido para configuraciones
const toggleConfig = async (field: string) => {
  const newValue = !props.producto[field as keyof Producto];

  try {
    await router.put(`/admin/proyectos/${props.producto.id}`, {
      [field]: newValue
    });
  } catch (error) {
    console.error('Error al actualizar:', error);
  }
};

// Funciones del carrusel de imágenes
const openImageModal = (index: number = 0) => {
  currentImageIndex.value = index;
  showImageModal.value = true;
  startAutoplay();
};

const closeImageModal = () => {
  showImageModal.value = false;
  stopAutoplay();
};

const nextImage = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % props.producto.images.length;
};

const prevImage = () => {
  currentImageIndex.value = currentImageIndex.value === 0
    ? props.producto.images.length - 1
    : currentImageIndex.value - 1;
};

const goToImage = (index: number) => {
  currentImageIndex.value = index;
};

const toggleAutoplay = () => {
  if (isPlaying.value) {
    stopAutoplay();
  } else {
    startAutoplay();
  }
};

const startAutoplay = () => {
  isPlaying.value = true;
  autoplayInterval.value = window.setInterval(() => {
    nextImage();
  }, 3000);
};

const stopAutoplay = () => {
  isPlaying.value = false;
  if (autoplayInterval.value) {
    clearInterval(autoplayInterval.value);
    autoplayInterval.value = null;
  }
};

// Marcar como principal
const setAsPrimary = async (imageId: number) => {
  try {
    await router.put(`/admin/product-images/${imageId}/set-primary`);
    router.reload({ only: ['producto'] });
  } catch (error) {
    console.error('Error al establecer como principal:', error);
  }
};

// Eliminar imagen
const deleteImage = async (imageId: number) => {
  if (!confirm('¿Estás seguro de eliminar esta imagen?')) return;

  try {
    await router.delete(`/admin/product-images/${imageId}`);
    router.reload({ only: ['producto'] });
  } catch (error) {
    console.error('Error al eliminar imagen:', error);
  }
};

// Limpiar interval al desmontar
onUnmounted(() => {
  stopAutoplay();
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Proyecto: ${producto.name}`" />

    <div class="bg-[#F5F5F5] min-h-[calc(100vh-200px)]">
      <div class="max-w-7xl mx-auto px-8 py-10">
        <!-- Header Compacto -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-l-4" style="border-color: #233C7A;">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
              <div class="rounded-xl p-3" style="background: rgba(35, 60, 122, 0.1);">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #233C7A;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div>
                <h1 class="text-3xl font-bold" style="font-family: 'Montserrat', sans-serif; font-weight: 700; color: #212121;">
                  {{ producto.name }}
                </h1>
                <p class="text-base" style="color: #212121; opacity: 0.7;">
                  Código: {{ producto.codigo_inmueble }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <Link
                href="/admin/proyectos"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:shadow-xl"
                style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver
              </Link>
            </div>
          </div>
        </div>

        <!-- FICHA TÉCNICA COMPACTA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Columna Izquierda: Información Principal -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Datos Básicos y Precios -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Información del Inmueble</h2>
                <button
                  @click="openEditModal('basica')"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                  Editar
                </button>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <!-- Operación -->
                <div class="p-3 rounded-xl border-2" style="border-color: #e0e0e0; background: #FAFAFA;">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">OPERACIÓN</p>
                  <p class="text-lg font-bold" style="color: #233C7A;">{{ getOperacionLabel(producto.operacion) }}</p>
                </div>

                <!-- Categoría -->
                <div class="p-3 rounded-xl border-2" style="border-color: #e0e0e0; background: #FAFAFA;">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">CATEGORÍA</p>
                  <p class="text-base font-bold" style="color: #212121;">{{ producto.category?.category_name || 'Sin categoría' }}</p>
                </div>

                <!-- Precio USD -->
                <div class="p-3 rounded-xl border-2" style="border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05);">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">PRECIO USD</p>
                  <p class="text-xl font-bold" style="color: #10b981;">{{ formatPrice(producto.price_usd, '$') }}</p>
                </div>

                <!-- Precio BOB -->
                <div class="p-3 rounded-xl border-2" style="border-color: rgba(35, 60, 122, 0.3); background: rgba(35, 60, 122, 0.05);">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">PRECIO BOB</p>
                  <p class="text-xl font-bold" style="color: #233C7A;">{{ formatPrice(producto.price_bob, 'Bs.') }}</p>
                </div>

                <!-- Comisión -->
                <div v-if="producto.comision" class="p-3 rounded-xl border-2" style="border-color: #e0e0e0; background: #FAFAFA;">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">COMISIÓN</p>
                  <p class="text-lg font-bold" style="color: #212121;">{{ producto.comision }}%</p>
                </div>

                <!-- Impuestos -->
                <div v-if="producto.taxes" class="p-3 rounded-xl border-2" style="border-color: #e0e0e0; background: #FAFAFA;">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">IMPUESTOS</p>
                  <p class="text-lg font-bold" style="color: #212121;">{{ formatPrice(producto.taxes) }}</p>
                </div>
              </div>
            </div>

            <!-- Características del Inmueble -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Características</h2>
                <button
                  @click="openEditModal('caracteristicas')"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                  Editar
                </button>
              </div>

              <div class="grid grid-cols-4 gap-3">
                <!-- Superficie Útil -->
                <div v-if="producto.superficie_util" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(35, 60, 122, 0.3); background: rgba(35, 60, 122, 0.05);">
                  <p class="text-2xl font-bold" style="color: #233C7A;">{{ producto.superficie_util }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">M² ÚTIL</p>
                </div>

                <!-- Superficie Construida -->
                <div v-if="producto.superficie_construida" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(250, 185, 14, 0.3); background: rgba(250, 185, 14, 0.05);">
                  <p class="text-2xl font-bold" style="color: #FAB90E;">{{ producto.superficie_construida }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">M² CONST</p>
                </div>

                <!-- Ambientes -->
                <div v-if="producto.ambientes" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(35, 60, 122, 0.3); background: rgba(35, 60, 122, 0.05);">
                  <p class="text-2xl font-bold" style="color: #233C7A;">{{ producto.ambientes }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">AMBIENTES</p>
                </div>

                <!-- Habitaciones -->
                <div v-if="producto.habitaciones" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(35, 60, 122, 0.3); background: rgba(35, 60, 122, 0.05);">
                  <p class="text-2xl font-bold" style="color: #233C7A;">{{ producto.habitaciones }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">HABITACIONES</p>
                </div>

                <!-- Baños -->
                <div v-if="producto.banos" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(250, 185, 14, 0.3); background: rgba(250, 185, 14, 0.05);">
                  <p class="text-2xl font-bold" style="color: #FAB90E;">{{ producto.banos }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">BAÑOS</p>
                </div>

                <!-- Cocheras -->
                <div v-if="producto.cocheras" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(250, 185, 14, 0.3); background: rgba(250, 185, 14, 0.05);">
                  <p class="text-2xl font-bold" style="color: #FAB90E;">{{ producto.cocheras }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">COCHERAS</p>
                </div>

                <!-- Año Construcción -->
                <div v-if="producto.ano_construccion" class="p-3 rounded-xl border-2 text-center" style="border-color: rgba(224, 8, 29, 0.3); background: rgba(224, 8, 29, 0.05);">
                  <p class="text-2xl font-bold" style="color: #E0081D;">{{ producto.ano_construccion }}</p>
                  <p class="text-xs font-bold mt-1" style="color: #212121; opacity: 0.6;">AÑO</p>
                </div>
              </div>
            </div>

            <!-- Descripción -->
            <div v-if="producto.description" class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Descripción</h2>
                <button
                  @click="openEditModal('descripcion')"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                  Editar
                </button>
              </div>
              <p class="text-base leading-relaxed" style="color: #212121;">{{ producto.description }}</p>
            </div>
          </div>

          <!-- Columna Derecha: Info Adicional e Imágenes -->
          <div class="space-y-6">
            <!-- Configuraciones -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Configuración</h2>
                <button
                  @click="openEditModal('configuracion')"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                  Editar
                </button>
              </div>

              <div class="space-y-3">
                <div
                  class="flex items-center justify-between py-2 border-b cursor-pointer hover:bg-gray-50 rounded px-2 -mx-2 transition-colors"
                  style="border-color: rgba(35, 60, 122, 0.1);"
                  @click="toggleConfig('is_public')"
                >
                  <span class="text-sm" style="color: #212121;">Visible públicamente</span>
                  <span :style="producto.is_public ? 'background: rgba(16, 185, 129, 0.15); color: #10b981;' : 'background: rgba(212, 212, 212, 0.3);'" class="rounded-full px-3 py-1 text-xs font-bold">
                    {{ producto.is_public ? 'Sí' : 'No' }}
                  </span>
                </div>
                <div
                  class="flex items-center justify-between py-2 border-b cursor-pointer hover:bg-gray-50 rounded px-2 -mx-2 transition-colors"
                  style="border-color: rgba(35, 60, 122, 0.1);"
                  @click="toggleConfig('allow_purchase')"
                >
                  <span class="text-sm" style="color: #212121;">Permitir compra</span>
                  <span :style="producto.allow_purchase ? 'background: rgba(16, 185, 129, 0.15); color: #10b981;' : 'background: rgba(212, 212, 212, 0.3);'" class="rounded-full px-3 py-1 text-xs font-bold">
                    {{ producto.allow_purchase ? 'Sí' : 'No' }}
                  </span>
                </div>
                <div
                  class="flex items-center justify-between py-2 cursor-pointer hover:bg-gray-50 rounded px-2 -mx-2 transition-colors"
                  @click="toggleConfig('downloadable')"
                >
                  <span class="text-sm" style="color: #212121;">Archivo descargable</span>
                  <span :style="producto.downloadable ? 'background: rgba(16, 185, 129, 0.15); color: #10b981;' : 'background: rgba(212, 212, 212, 0.3);'" class="rounded-full px-3 py-1 text-xs font-bold">
                    {{ producto.downloadable ? 'Sí' : 'No' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Información Técnica -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Información Técnica</h2>
                <button
                  @click="openEditModal('tecnica')"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                  Editar
                </button>
              </div>

              <div class="space-y-3">
                <div v-if="producto.sku" class="py-2 border-b" style="border-color: rgba(35, 60, 122, 0.1);">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">SKU</p>
                  <p class="text-base font-mono font-bold" style="color: #212121;">{{ producto.sku }}</p>
                </div>
                <div v-if="producto.hsn_sac_code" class="py-2 border-b" style="border-color: rgba(35, 60, 122, 0.1);">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">HSN/SAC</p>
                  <p class="text-base font-mono font-bold" style="color: #212121;">{{ producto.hsn_sac_code }}</p>
                </div>
                <div class="py-2">
                  <p class="text-xs font-bold mb-1" style="color: #212121; opacity: 0.6;">FECHA DE CREACIÓN</p>
                  <p class="text-sm font-bold" style="color: #233C7A;">{{ formatDate(producto.created_at) }}</p>
                </div>
              </div>

              <a v-if="producto.downloadable_file" :href="producto.downloadable_file" target="_blank" class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:shadow-xl" style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar Archivo
              </a>
            </div>

            <!-- Galería de Imágenes Mejorada -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">Imágenes ({{ producto.images.length }})</h2>
                <button
                  v-if="producto.images.length > 0"
                  @click="openImageModal(0)"
                  class="text-xs font-semibold rounded-lg px-3 py-1.5 transition-all hover:shadow-md flex items-center gap-1.5"
                  style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  Ver Galería
                </button>
              </div>

              <!-- Grid de Imágenes Compactas -->
              <div v-if="producto.images.length > 0" class="grid grid-cols-3 gap-3 mb-4">
                <div
                  v-for="(image, index) in producto.images"
                  :key="image.id"
                  class="relative group cursor-pointer rounded-xl overflow-hidden border-2 transition-all hover:shadow-xl"
                  :class="image.is_primary ? 'ring-2 ring-offset-2' : ''"
                  :style="image.is_primary ? 'border-color: #FAB90E; ring-color: #FAB90E;' : 'border-color: #e0e0e0;'"
                  @click="openImageModal(index)"
                >
                  <!-- Imagen -->
                  <div class="aspect-square overflow-hidden">
                    <img
                      :src="getImageUrl(image.image_path)"
                      :alt="image.original_name"
                      class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                    />
                  </div>

                  <!-- Badge Principal -->
                  <div
                    v-if="image.is_primary"
                    class="absolute top-2 left-2 px-2 py-1 rounded-lg text-xs font-bold"
                    style="background: #FAB90E; color: white;"
                  >
                    Principal
                  </div>

                  <!-- Overlay con acciones -->
                  <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button
                      @click.stop="setAsPrimary(image.id)"
                      class="rounded-lg p-2 transition-all hover:scale-110"
                      style="background: rgba(250, 185, 14, 0.9);"
                      title="Marcar como principal"
                    >
                      <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                    </button>
                    <button
                      @click.stop="deleteImage(image.id)"
                      class="rounded-lg p-2 transition-all hover:scale-110"
                      style="background: rgba(224, 8, 29, 0.9);"
                      title="Eliminar imagen"
                    >
                      <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else class="py-8 text-center rounded-xl border-2 border-dashed" style="border-color: #e0e0e0;">
                <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #212121; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-bold" style="color: #212121; opacity: 0.6;">No hay imágenes</p>
              </div>

              <!-- Subir Nuevas Imágenes -->
              <div class="rounded-xl border-2 p-4" style="border-color: #233C7A; background: rgba(35, 60, 122, 0.05);">
                <div class="mb-3">
                  <h3 class="text-sm font-bold mb-1" style="color: #212121;">Agregar Imágenes</h3>
                  <p class="text-xs" style="color: #212121; opacity: 0.6;">Sube nuevas imágenes a la galería</p>
                </div>
                <ProyectosImageUploader
                  :product-id="producto.id"
                  @uploaded="handleImageUploaded"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL DE EDICIÓN -->
      <div v-if="showEditModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditModal = false"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">
              {{ editSection === 'basica' ? 'Editar Información Básica' : '' }}
              {{ editSection === 'caracteristicas' ? 'Editar Características' : '' }}
              {{ editSection === 'descripcion' ? 'Editar Descripción' : '' }}
              {{ editSection === 'configuracion' ? 'Editar Configuración' : '' }}
              {{ editSection === 'tecnica' ? 'Editar Información Técnica' : '' }}
            </h3>
            <button
              @click="showEditModal = false"
              class="rounded-lg p-2 transition-colors hover:bg-gray-100"
              style="color: #212121; opacity: 0.6;"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Formulario (mismo que antes) -->
          <form @submit.prevent="saveChanges">
            <!-- Información Básica -->
            <div v-if="editSection === 'basica'" class="space-y-4">
              <div>
                <label class="block text-sm font-bold mb-2" style="color: #212121;">Nombre del Inmueble</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                  style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  required
                >
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Operación</label>
                  <select
                    v-model="editForm.operacion"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                    required
                  >
                    <option value="venta">Venta</option>
                    <option value="alquiler">Alquiler</option>
                    <option value="anticretico">Anticrético</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Categoría</label>
                  <select
                    v-model="editForm.category_id"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                    <option :value="null">Sin categoría</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Precio USD</label>
                  <input
                    v-model.number="editForm.price_usd"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #10b981;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Precio BOB</label>
                  <input
                    v-model.number="editForm.price_bob"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Comisión (%)</label>
                  <input
                    v-model.number="editForm.comision"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Impuestos</label>
                  <input
                    v-model.number="editForm.taxes"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>
              </div>
            </div>

            <!-- Características -->
            <div v-if="editSection === 'caracteristicas'" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Superficie Útil (m²)</label>
                  <input
                    v-model.number="editForm.superficie_util"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Superficie Construida (m²)</label>
                  <input
                    v-model.number="editForm.superficie_construida"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #FAB90E;"
                  >
                </div>
              </div>

              <div class="grid grid-cols-4 gap-3">
                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Ambientes</label>
                  <input
                    v-model.number="editForm.ambientes"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Habitaciones</label>
                  <input
                    v-model.number="editForm.habitaciones"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Baños</label>
                  <input
                    v-model.number="editForm.banos"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #FAB90E;"
                  >
                </div>

                <div>
                  <label class="block text-sm font-bold mb-2" style="color: #212121;">Cocheras</label>
                  <input
                    v-model.number="editForm.cocheras"
                    type="number"
                    placeholder="0"
                    class="w-full rounded-xl border-2 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: #e0e0e0; color: #212121; focus:border-color: #FAB90E;"
                  >
                </div>
              </div>

              <div>
                <label class="block text-sm font-bold mb-2" style="color: #212121;">Año de Construcción</label>
                <input
                  v-model.number="editForm.ano_construccion"
                  type="number"
                  placeholder="2024"
                  class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                  style="border-color: #e0e0e0; color: #212121; focus:border-color: #E0081D;"
                >
              </div>
            </div>

            <!-- Descripción -->
            <div v-if="editSection === 'descripcion'" class="space-y-4">
              <div>
                <label class="block text-sm font-bold mb-2" style="color: #212121;">Descripción del Inmueble</label>
                <textarea
                  v-model="editForm.description"
                  rows="6"
                  placeholder="Describe las características del inmueble..."
                  class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 resize-none"
                  style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                ></textarea>
              </div>
            </div>

            <!-- Configuración -->
            <div v-if="editSection === 'configuracion'" class="space-y-4">
              <div class="space-y-3">
                <label class="flex items-center justify-between p-3 rounded-xl border-2 cursor-pointer transition-colors hover:bg-gray-50" style="border-color: #e0e0e0;">
                  <span class="text-sm font-bold" style="color: #212121;">Visible públicamente</span>
                  <div class="relative">
                    <input
                      type="checkbox"
                      v-model="editForm.is_public"
                      class="sr-only"
                    >
                    <div
                      class="w-11 h-6 rounded-full transition-colors"
                      :style="editForm.is_public ? 'background: #10b981;' : 'background: #d1d5db;'"
                    >
                      <div
                        class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full transition-transform shadow"
                        :style="editForm.is_public ? 'transform: translateX(20px);' : 'transform: translateX(0);'"
                      ></div>
                    </div>
                  </div>
                </label>

                <label class="flex items-center justify-between p-3 rounded-xl border-2 cursor-pointer transition-colors hover:bg-gray-50" style="border-color: #e0e0e0;">
                  <span class="text-sm font-bold" style="color: #212121;">Permitir compra</span>
                  <div class="relative">
                    <input
                      type="checkbox"
                      v-model="editForm.allow_purchase"
                      class="sr-only"
                    >
                    <div
                      class="w-11 h-6 rounded-full transition-colors"
                      :style="editForm.allow_purchase ? 'background: #10b981;' : 'background: #d1d5db;'"
                    >
                      <div
                        class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full transition-transform shadow"
                        :style="editForm.allow_purchase ? 'transform: translateX(20px);' : 'transform: translateX(0);'"
                      ></div>
                    </div>
                  </div>
                </label>

                <label class="flex items-center justify-between p-3 rounded-xl border-2 cursor-pointer transition-colors hover:bg-gray-50" style="border-color: #e0e0e0;">
                  <span class="text-sm font-bold" style="color: #212121;">Archivo descargable</span>
                  <div class="relative">
                    <input
                      type="checkbox"
                      v-model="editForm.downloadable"
                      class="sr-only"
                    >
                    <div
                      class="w-11 h-6 rounded-full transition-colors"
                      :style="editForm.downloadable ? 'background: #10b981;' : 'background: #d1d5db;'"
                    >
                      <div
                        class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full transition-transform shadow"
                        :style="editForm.downloadable ? 'transform: translateX(20px);' : 'transform: translateX(0);'"
                      ></div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Información Técnica -->
            <div v-if="editSection === 'tecnica'" class="space-y-4">
              <div>
                <label class="block text-sm font-bold mb-2" style="color: #212121;">SKU</label>
                <input
                  v-model="editForm.sku"
                  type="text"
                  placeholder="SKU-001"
                  class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 font-mono"
                  style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                >
              </div>

              <div>
                <label class="block text-sm font-bold mb-2" style="color: #212121;">Código HSN/SAC</label>
                <input
                  v-model="editForm.hsn_sac_code"
                  type="text"
                  placeholder="12345"
                  class="w-full rounded-xl border-2 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 font-mono"
                  style="border-color: #e0e0e0; color: #212121; focus:border-color: #233C7A;"
                >
              </div>
            </div>

            <!-- Botones del Modal -->
            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t" style="border-color: #e0e0e0;">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-5 py-2.5 text-sm font-semibold rounded-xl transition-all hover:shadow-md"
                style="background: #FAFAFA; color: #212121;"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="px-5 py-2.5 text-sm font-semibold rounded-xl text-white transition-all hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #233C7A 0%, #1a2e5f 100%);"
              >
                {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- MODAL DE CARRUSEL DE IMÁGENES -->
      <div v-if="showImageModal && producto.images.length > 0" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeImageModal"></div>

        <!-- Modal Carrusel -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b" style="border-color: #e0e0e0;">
            <div class="flex items-center gap-3">
              <h3 class="text-xl font-bold" style="font-family: 'Montserrat', sans-serif; color: #212121;">
                Galería de Imágenes
              </h3>
              <span class="text-sm font-bold px-3 py-1 rounded-full" style="background: rgba(35, 60, 122, 0.1); color: #233C7A;">
                {{ currentImageIndex + 1 }} / {{ producto.images.length }}
              </span>
            </div>
            <button
              @click="closeImageModal"
              class="rounded-lg p-2 transition-colors hover:bg-gray-100"
              style="color: #212121; opacity: 0.6;"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del Modal con scroll -->
          <div class="flex-1 overflow-y-auto p-6">
            <!-- Contenedor del Carrusel -->
            <div class="relative">
              <!-- Imagen Principal -->
              <div class="rounded-xl overflow-hidden mb-4" style="background: #FAFAFA; max-height: 50vh;">
                <img
                  :src="getImageUrl(producto.images[currentImageIndex]?.image_path || '')"
                  :alt="producto.images[currentImageIndex]?.original_name"
                  class="w-full h-full object-contain"
                  style="max-height: 50vh;"
                >
              </div>

            <!-- Controles de Navegación -->
            <div class="flex items-center justify-between">
              <!-- Botón Anterior -->
              <button
                @click="prevImage"
                class="rounded-xl p-3 transition-all hover:shadow-lg disabled:opacity-30 disabled:cursor-not-allowed"
                style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                :disabled="producto.images.length <= 1"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <!-- Indicadores -->
              <div class="flex gap-2">
                <button
                  v-for="(image, index) in producto.images"
                  :key="image.id"
                  @click="goToImage(index)"
                  class="w-3 h-3 rounded-full transition-all"
                  :class="currentImageIndex === index ? 'scale-125' : ''"
                  :style="currentImageIndex === index ? 'background: #233C7A;' : 'background: #e0e0e0;'"
                ></button>
              </div>

              <!-- Botón Siguiente -->
              <button
                @click="nextImage"
                class="rounded-xl p-3 transition-all hover:shadow-lg disabled:opacity-30 disabled:cursor-not-allowed"
                style="background: rgba(35, 60, 122, 0.1); color: #233C7A;"
                :disabled="producto.images.length <= 1"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            <!-- Control de Autoplay -->
            <div class="flex items-center justify-center gap-3 mt-4">
              <button
                @click="toggleAutoplay"
                class="rounded-xl px-4 py-2 text-sm font-semibold transition-all hover:shadow-md flex items-center gap-2"
                :style="isPlaying ? 'background: rgba(224, 8, 29, 0.1); color: #E0081D;' : 'background: rgba(16, 185, 129, 0.1); color: #10b981;'"
              >
                <svg v-if="isPlaying" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
                {{ isPlaying ? 'Pausar' : 'Reproducir' }}
              </button>
            </div>

            <!-- Acciones de la imagen actual -->
            <div class="flex items-center justify-center gap-3 mt-4 pt-4 border-t" style="border-color: #e0e0e0;">
              <button
                v-if="!producto.images[currentImageIndex]?.is_primary"
                @click="setAsPrimary(producto.images[currentImageIndex]?.id)"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all hover:shadow-lg flex items-center gap-2"
                style="background: linear-gradient(135deg, #FAB90E 0%, #e0a600 100%);"
              >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
                Marcar como Principal
              </button>

              <button
                @click="deleteImage(producto.images[currentImageIndex]?.id); closeImageModal();"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all hover:shadow-lg flex items-center gap-2"
                style="background: linear-gradient(135deg, #E0081D 0%, #b30613 100%);"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar Imagen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </AppLayout>
</template>
