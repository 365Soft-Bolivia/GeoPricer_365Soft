<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import PublicHeader from '@/components/public/PublicHeader.vue';
import PublicFooter from '@/components/public/PublicFooter.vue';

import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

import { useNotification } from '@/composables/useNotification';
import { onMounted } from 'vue';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Inmobiliaria'
});

// Notificaciones
const page = usePage();
const { showSuccess, showError } = useNotification();

// Cuando Inertia recibe flash messages del backend
onMounted(() => {
    if (page.props.flash?.success) {
        showSuccess('Éxito', page.props.flash.success);
    }
    if (page.props.flash?.error) {
        showError('Error', page.props.flash.error);
    }
});
</script>

<template>
    <!-- Componente global de notificaciones -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <PublicHeader />

        <!-- Contenido principal -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <PublicFooter />
    </div>
</template>
