import 'primeicons/primeicons.css';
import '../css/primevue-fixes.css';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

// --- PrimeVue - Solo servicios ---
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Exponer el token CSRF globalmente
const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfTokenMeta) {
    (window as any).csrfToken = csrfTokenMeta.getAttribute('content');
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                // Configuración para compatibilidad con Tailwind
                unstyled: false, // Usar estilos de PrimeVue
                ripple: true,
            })
            .use(ToastService)
            .use(ConfirmationService);

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Inicializar el tema
initializeTheme();