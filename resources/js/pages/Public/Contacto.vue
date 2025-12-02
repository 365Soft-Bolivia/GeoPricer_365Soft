<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({
    layout: PublicLayout
});

// Estado del mensaje de éxito
const successMessageVisible = ref(false);

// Errores locales (frontend)
const errors = ref<Record<string, string>>({});

// FORM DATA
const form = useForm({
    nombre: '',
    apellido: '',
    carnet: '',
    telefono: '',
    email: '',
    mensaje: ''
});

// VALIDACIÓN FRONT-END
const validateForm = () => {
    errors.value = {};

    const onlyLetters = /^[A-Za-zÁÉÍÓÚÑáéíóúñ\s]+$/;

    // Nombre
    if (!form.nombre.trim() || !onlyLetters.test(form.nombre)) {
        errors.value.nombre = "El nombre solo debe contener letras.";
    }

    // Apellido
    if (!form.apellido.trim() || !onlyLetters.test(form.apellido)) {
        errors.value.apellido = "El apellido solo debe contener letras.";
    }

    // Email debe ser @gmail.com
    if (!/^[\w.+-]+@gmail\.com$/.test(form.email)) {
        errors.value.email = "Debe ingresar un correo válido que termine en @gmail.com";
    }

    // Carnet solo números
    if (!/^\d+$/.test(form.carnet)) {
        errors.value.carnet = "El carnet solo debe contener números enteros.";
    }

    // Teléfono solo números
    if (!/^\d+$/.test(form.telefono)) {
        errors.value.telefono = "El teléfono solo debe contener números enteros.";
    }

    // Mensaje obligatorio
    if (!form.mensaje.trim()) {
        errors.value.mensaje = "El mensaje es obligatorio.";
    }

    return Object.keys(errors.value).length === 0;
};

// SUBMIT
const handleSubmit = () => {
    if (!validateForm()) {
        return;
    }

    form.post('/contacto', {
        onSuccess: () => {
            successMessageVisible.value = true;
            form.reset();
        }
    });
};
</script>

<template>
    <section
        class="relative py-20 bg-cover bg-center bg-no-repeat text-white placeholder-white transition-colors duration-300"
        :style="{ backgroundImage: 'url(/contact.jpeg)' }"
    >

        <!-- Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/50 via-black/40 to-black/20"></div>

        <div class="relative max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 px-6 lg:px-10">

            <!-- FORMULARIO DENTRO DE UN CUADRO CAFÉ -->
            <div
                class="bg-[#5c4a3b] p-8 rounded-2xl shadow-xl backdrop-blur-md border border-[#8b7a61] transition-all"
            >
                <h2 class="text-3xl font-bold mb-6 text-white placeholder-white">Cuéntanos tu experiencia</h2>

                <form @submit.prevent="handleSubmit" class="space-y-3">

                    <!-- Nombre -->
                    <div>
                        <label class="block font-semibold mb-1">Nombre *</label>
                        <p v-if="errors.nombre" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.nombre }}</p>

                        <input
                            v-model="form.nombre"
                            type="text"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        >
                    </div>

                    <!-- Apellido -->
                    <div>
                        <label class="block font-semibold mb-1">Apellido *</label>
                        <p v-if="errors.apellido" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.apellido }}</p>

                        <input
                            v-model="form.apellido"
                            type="text"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        >
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block font-semibold mb-1">Email *</label>
                        <p v-if="errors.email" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.email }}</p>

                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        >
                    </div>

                    <!-- Carnet -->
                    <div>
                        <label class="block font-semibold mb-1">Carnet *</label>
                        <p v-if="errors.carnet" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.carnet }}</p>

                        <input
                            v-model="form.carnet"
                            type="text"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        >
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="block font-semibold mb-1">Teléfono *</label>
                        <p v-if="errors.telefono" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.telefono }}</p>

                        <input
                            v-model="form.telefono"
                            type="text"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        >
                    </div>

                    <!-- Mensaje -->
                    <div>
                        <label class="block font-semibold mb-1">Mensaje *</label>
                        <p v-if="errors.mensaje" class="text-red-700 dark:text-red-300 text-sm mb-1">{{ errors.mensaje }}</p>

                        <textarea
                            v-model="form.mensaje"
                            rows="5"
                            class="w-full bg-white/80 dark:bg-black/40 border border-gray-300 dark:border-gray-600
                                px-4 py-3 rounded-md focus:ring-2 focus:ring-yellow-400 
                                text-white placeholder-white backdrop-blur-sm"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="bg-yellow-500 text-black dark:text-white dark:bg-yellow-600 
                            font-bold px-10 py-3 rounded-md hover:bg-yellow-400 
                            dark:hover:bg-yellow-500 transition w-full text-center mt-4"
                    >
                        Contactar
                    </button>
                </form>
            </div>

            <!-- TEXTO LATERAL EN UNA TARJETA CAFÉ -->
                <div class="flex flex-col">

                <!-- Cuadro del texto -->
            <div 
                    class="bg-[#bfa78c]/90 dark:bg-[#5c4a3b]/80 
                        p-8 rounded-2xl shadow-xl backdrop-blur-md 
                        border border-[#a38b73] dark:border-[#8b7a61] 
                        w-fit max-w-lg"
                >
                    <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
                        Contáctanos
                    </h3>

                <p class="mb-4 text-gray-900 dark:text-gray-200">
                    ¿Desea más información de nuestros servicios o desea buscar una Oficina
                    que le atienda? Envíenos un correo con sus datos para referirlo.
                </p>

                <p class="font-bold text-yellow-800 dark:text-yellow-400">Línea de soporte</p>
                <p class="text-gray-900 dark:text-gray-200">
                    Nuestra asistencia telefónica es de Lunes a Viernes, de 8:00 a 17:00 hrs
                </p>
            </div>

            <!-- BLOQUE DE ÉXITO ABAJO DEL CUADRO -->
            <div
                v-if="successMessageVisible"
                    class="mt-6 bg-green-200 dark:bg-green-900/50 border border-green-500 
                    text-green-800 dark:text-green-300 p-6 rounded-xl shadow-lg 
                    backdrop-blur-sm animate-fade-in max-w-lg"
            >
                <div class="flex items-center gap-3 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        class="h-10 w-10 text-green-600 dark:text-green-300" 
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" 
                        stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                        <h3 class="text-2xl font-semibold">¡Gracias por contactarnos!</h3>
                </div>

                <p class="text-lg">Tu mensaje ha sido enviado correctamente.</p>
                <p class="text-lg">Te responderemos lo antes posible.</p>
            </div>
</div>

        </div>
    </section>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.4s ease-out forwards;
}
</style>