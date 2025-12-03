<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { admin } from '@/routes-custom';

const { proyectos } = admin;

interface Category {
  id: number;
  category_name: string;
}

const props = defineProps<{
  categorias: Category[];
}>();

const emit = defineEmits<{
  close: [];
  created: [];
}>();

const form = useForm({
  name: '',
  codigo_inmueble: '',
  price: 0,
  superficie_util: null as number | null,
  superficie_construida: null as number | null,
  ambientes: null as number | null,
  habitaciones: null as number | null,
  banos: null as number | null,
  cocheras: null as number | null,
  ano_construccion: null as number | null,
  operacion: 'venta',
  comision: null as number | null,
  taxes: null as number | null,
  description: '',
  category_id: null as number | null,
  sku: '',
  hsn_sac_code: '',
  allow_purchase: true,
  is_public: true,
  downloadable: false,
  downloadable_file: '',
});

const currentTab = ref<'general' | 'detalles' | 'otros'>('general');

const submit = () => {
  // -------- VALIDACIÓN OBLIGATORIA DE CATEGORÍA --------
  if (!form.category_id) {
    form.setError('category_id', 'Debes seleccionar una categoría.');
    currentTab.value = 'general'; // vuelve a la pestaña correcta
    return;
  }

  form.post(proyectos.store().url, {
    preserveScroll: true,
    onSuccess: () => {
      emit('created');
    },
  });
};
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="emit('close')"
            aria-hidden="true"></div>
        </Transition>

        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

        <Transition enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
          <div
            class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-3xl sm:align-middle">
            <form @submit.prevent="submit">
              <!-- Header -->
              <div class="bg-white px-4 pb-4 pt-5 dark:bg-gray-800 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                    Crear Nuevo Proyecto
                  </h3>
                  <button type="button" @click="emit('close')"
                    class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                  <nav class="-mb-px flex space-x-8">
                    <button type="button" @click="currentTab = 'general'"
                      :class="currentTab === 'general' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
                      class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                      Información General
                    </button>
                    <button type="button" @click="currentTab = 'detalles'"
                      :class="currentTab === 'detalles' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
                      class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                      Detalles del Inmueble
                    </button>
                    <button type="button" @click="currentTab = 'otros'"
                      :class="currentTab === 'otros' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400'"
                      class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                      Otros Datos
                    </button>
                  </nav>
                </div>

                <!-- Tab Content -->
                <div class="mt-6 max-h-[60vh] overflow-y-auto">
                  <!-- Tab: Información General -->
                  <div v-show="currentTab === 'general'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre del Proyecto <span class="text-red-500">*</span>
                      </label>
                      <input v-model="form.name" type="text" required placeholder="Ej: Departamento en Zona Norte"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Código de Inmueble <span class="text-red-500">*</span>
                      </label>
                      <input v-model="form.codigo_inmueble" type="text" required placeholder="INM-001"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.codigo_inmueble" class="mt-1 text-sm text-red-600">{{
                        form.errors.codigo_inmueble }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        SKU
                      </label>
                      <input v-model="form.sku" type="text" placeholder="SKU-001"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipo de Operación <span class="text-red-500">*</span>
                      </label>
                      <select v-model="form.operacion" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="venta">Venta</option>
                        <option value="alquiler">Alquiler</option>
                        <option value="anticretico">Anticrético</option>
                      </select>
                      <p v-if="form.errors.operacion" class="mt-1 text-sm text-red-600">{{ form.errors.operacion }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Precio (BOB) <span class="text-red-500">*</span>
                      </label>
                      <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">Bs.</span>
                        <input v-model.number="form.price" type="number" step="0.01" min="0" required placeholder="0.00"
                          class="block w-full rounded-md border border-gray-300 py-2 pl-10 pr-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      </div>
                      <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Comisión (%)
                      </label>
                      <input v-model.number="form.comision" type="number" step="0.01" min="0" max="100"
                        placeholder="0.00"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.comision" class="mt-1 text-sm text-red-600">{{ form.errors.comision }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Impuestos (BOB)
                      </label>
                      <input v-model.number="form.taxes" type="number" step="0.01" min="0" placeholder="0.00"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.taxes" class="mt-1 text-sm text-red-600">{{ form.errors.taxes }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Categoría <span class="text-red-500">*</span>
                      </label>

                      <select v-model="form.category_id" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                                                        focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500
                                                                        dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">Seleccione una categoría</option>
                        <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                          {{ cat.category_name }}
                        </option>
                      </select>

                      <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.category_id }}
                      </p>
                    </div>

                    <div class="sm:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                      </label>
                      <textarea v-model="form.description" rows="3" placeholder="Descripción detallada del proyecto..."
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"></textarea>
                      <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}
                      </p>
                    </div>
                  </div>

                  <!-- Tab: Detalles del Inmueble -->
                  <div v-show="currentTab === 'detalles'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Superficie Útil (m²)
                      </label>
                      <input v-model.number="form.superficie_util" type="number" step="0.01" min="0" placeholder="0.00"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.superficie_util" class="mt-1 text-sm text-red-600">{{
                        form.errors.superficie_util }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Superficie Construida (m²)
                      </label>
                      <input v-model.number="form.superficie_construida" type="number" step="0.01" min="0"
                        placeholder="0.00"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.superficie_construida" class="mt-1 text-sm text-red-600">{{
                        form.errors.superficie_construida }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Ambientes
                      </label>
                      <input v-model.number="form.ambientes" type="number" min="0" placeholder="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.ambientes" class="mt-1 text-sm text-red-600">{{ form.errors.ambientes }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Habitaciones
                      </label>
                      <input v-model.number="form.habitaciones" type="number" min="0" placeholder="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.habitaciones" class="mt-1 text-sm text-red-600">{{ form.errors.habitaciones
                        }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Baños
                      </label>
                      <input v-model.number="form.banos" type="number" min="0" placeholder="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.banos" class="mt-1 text-sm text-red-600">{{ form.errors.banos }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Cocheras
                      </label>
                      <input v-model.number="form.cocheras" type="number" min="0" placeholder="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.cocheras" class="mt-1 text-sm text-red-600">{{ form.errors.cocheras }}</p>
                    </div>

                    <div class="sm:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Año de Construcción
                      </label>
                      <input v-model.number="form.ano_construccion" type="number" min="1900"
                        :max="new Date().getFullYear()" placeholder="2024"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.ano_construccion" class="mt-1 text-sm text-red-600">{{
                        form.errors.ano_construccion }}</p>
                    </div>
                  </div>

                  <!-- Tab: Otros Datos -->
                  <div v-show="currentTab === 'otros'" class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Código HSN/SAC
                      </label>
                      <input v-model="form.hsn_sac_code" type="text" placeholder="HSN/SAC"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.hsn_sac_code" class="mt-1 text-sm text-red-600">{{ form.errors.hsn_sac_code
                        }}</p>
                    </div>

                    <div class="space-y-3">
                      <div class="flex items-center">
                        <input v-model="form.allow_purchase" type="checkbox" id="allow_purchase"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <label for="allow_purchase" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Permitir compra
                        </label>
                      </div>

                      <div class="flex items-center">
                        <input v-model="form.is_public" type="checkbox" id="is_public"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <label for="is_public" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Visible públicamente
                        </label>
                      </div>

                      <!-- <div class="flex items-center">
                        <input
                          v-model="form.downloadable"
                          type="checkbox"
                          id="downloadable"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="downloadable" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Archivo descargable
                        </label>
                      </div> -->
                    </div>

                    <div v-if="form.downloadable">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Archivo Descargable (URL)
                      </label>
                      <input v-model="form.downloadable_file" type="text" placeholder="https://..."
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                      <p v-if="form.errors.downloadable_file" class="mt-1 text-sm text-red-600">{{
                        form.errors.downloadable_file }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="submit" :disabled="form.processing"
                  class="inline-flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm">
                  <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                  </svg>
                  {{ form.processing ? 'Creando...' : 'Crear Proyecto' }}
                </button>
                <button type="button" @click="emit('close')" :disabled="form.processing"
                  class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">
                  Cancelar
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>