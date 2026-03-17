import { ref, onMounted, onUnmounted } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

const APPEARANCE_KEY = 'appearance';

// Estado reactivo compartido entre todas las instancias
const appearance = ref<Appearance>('light'); // Tema claro por defecto
let isInitialized = false;
let mediaQuery: MediaQueryList | null = null;
let mediaQueryHandler: (() => void) | null = null;

function getSystemAppearance(): 'light' | 'dark' {
    if (typeof window === 'undefined') return 'light';
    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

function applyAppearance(newAppearance: Appearance) {
    const effectiveAppearance = newAppearance === 'system' 
        ? getSystemAppearance() 
        : newAppearance;
    
    if (effectiveAppearance === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

export function useAppearance() {
    function updateAppearance(newAppearance: Appearance) {
        appearance.value = newAppearance;
        localStorage.setItem(APPEARANCE_KEY, newAppearance);
        applyAppearance(newAppearance);
    }

    // Inicializar solo una vez
    onMounted(() => {
        if (!isInitialized) {
            // Cargar desde localStorage
            const savedAppearance = localStorage.getItem(APPEARANCE_KEY) as Appearance | null;
            if (savedAppearance) {
                appearance.value = savedAppearance;
            }
            
            // Aplicar el tema
            applyAppearance(appearance.value);

            // Escuchar cambios en las preferencias del sistema
            if (typeof window !== 'undefined') {
                mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                mediaQueryHandler = () => {
                    if (appearance.value === 'system') {
                        applyAppearance('system');
                    }
                };
                mediaQuery.addEventListener('change', mediaQueryHandler);
            }

            isInitialized = true;
        } else {
            // Si ya está inicializado, solo aplicar el tema actual
            applyAppearance(appearance.value);
        }
    });

    // Limpiar listener cuando el componente se desmonte
    onUnmounted(() => {
        if (mediaQuery && mediaQueryHandler) {
            mediaQuery.removeEventListener('change', mediaQueryHandler);
        }
    });

    return {
        appearance,
        updateAppearance,
        getSystemAppearance,
    };
}

// Función de inicialización que se ejecuta antes de montar Vue
export function initializeTheme() {
    if (typeof window === 'undefined') return;
    
    const savedAppearance = localStorage.getItem(APPEARANCE_KEY) as Appearance | null;
    const currentAppearance = savedAppearance || 'system';
    
    const effectiveAppearance = currentAppearance === 'system' 
        ? getSystemAppearance() 
        : currentAppearance;
    
    if (effectiveAppearance === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    // Guardar en el estado reactivo
    appearance.value = currentAppearance;
    isInitialized = true;
}