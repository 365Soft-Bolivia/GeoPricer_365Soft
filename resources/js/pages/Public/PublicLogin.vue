<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';
import PublicAuthLayout from '@/layouts/PublicAuthLayout.vue';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, AlertCircle, CheckCircle2, Shield, Lock, User } from 'lucide-vue-next';
import { computed } from 'vue';
import publicRoutes from '@/routes/public';

const props = defineProps<{
    status?: string;
    message?: string;
    error?: string;
}>();

// Crear el form usando la ruta específica
const form = {
    action: publicRoutes.login.post(),
    method: 'post',
};

// Computed para mostrar alertas
const showSuccess = computed(() => {
    return props.status || props.message;
});

const showError = computed(() => {
    return props.error;
});
</script>

<template>
    <PublicAuthLayout
        title="Acceso al Sistema Público"
        description="Ingresa tus credenciales para acceder a ACM Analytics"
    >
        <Head title="Login Público - ACM Analytics 365Soft" />

        <!-- Alertas de éxito -->
        <div v-if="showSuccess" class="mb-4">
            <Alert variant="success" class="bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <div class="text-sm text-green-800 dark:text-green-200">
                    {{ status || message }}
                </div>
            </Alert>
        </div>

        <!-- Alertas de error -->
        <div v-if="showError" class="mb-4">
            <Alert variant="destructive" class="bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800">
                <AlertCircle class="h-4 w-4 text-red-600 dark:text-red-400" />
                <div class="text-sm text-red-800 dark:text-red-200">
                    {{ error }}
                </div>
            </Alert>
        </div>

        <Form
            v-bind="form"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <!-- Campo de Usuario -->
                <div class="grid gap-2">
                    <Label for="username" class="flex items-center gap-2">
                        <Shield class="h-4 w-4" />
                        Usuario
                    </Label>
                    <Input
                        id="username"
                        type="text"
                        name="username"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        placeholder="Tu usuario"
                        class="bg-white/50 dark:bg-gray-900/50 border-indigo-200 focus:border-indigo-500 dark:border-gray-700"
                    />
                    <InputError :message="errors.username" />
                </div>

                <!-- Campo de Contraseña -->
                <div class="grid gap-2">
                    <Label for="password" class="flex items-center gap-2">
                        <Lock class="h-4 w-4" />
                        Contraseña
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="bg-white/50 dark:bg-gray-900/50 border-indigo-200 focus:border-indigo-500 dark:border-gray-700"
                    />
                    <InputError :message="errors.password" />
                </div>

                <!-- Opciones adicionales -->
                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-2 cursor-pointer">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span class="text-sm">Recordarme en este dispositivo</span>
                    </Label>
                </div>

                <!-- Botón de submit -->
                <Button
                    type="submit"
                    class="mt-4 w-full bg-indigo-600 hover:bg-indigo-700 text-white transition-all"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin mr-2"
                    />
                    <Shield v-else class="h-4 w-4 mr-2" />
                    {{ processing ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                </Button>

                <!-- Información de seguridad -->
                <div class="mt-4 text-center">
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <Lock class="h-3 w-3" />
                        <span>Conexión segura con auditoría de intentos</span>
                    </div>
                </div>
            </div>
        </Form>
    </PublicAuthLayout>
</template>