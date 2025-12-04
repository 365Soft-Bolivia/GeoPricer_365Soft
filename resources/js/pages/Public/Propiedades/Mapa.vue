<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import { ArrowLeft, Navigation, X, MapPin, Home, Filter, Search, ChevronDown } from 'lucide-vue-next';

// Fix para iconos de Leaflet
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';
import iconRetina from 'leaflet/dist/images/marker-icon-2x.png';

delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: iconRetina,
    iconUrl: icon,
    shadowUrl: iconShadow,
});

interface ProductImage {
    id: number;
    image_path: string;
    original_name: string;
    is_primary: boolean;
    order: number;
}

interface Product {
    id: number;
    name: string;
    codigo_inmueble: string;
    price: number;
    operacion: string;
    default_image: string | null;
    category: string | null;
    category_id: number | null;
    // Campos informativos adicionales
    habitaciones?: number | null;
    banos?: number | null;
    ambientes?: number | null;
    cocheras?: number | null;
    superficie_util?: number | null;
    superficie_construida?: number | null;
    ano_construccion?: number | null;
    antiguedad?: number | null;
    comision?: number | null;
    images?: ProductImage[];
    location: {
        id: number;
        latitude: number;
        longitude: number;
        address: string | null;
        is_active: boolean;
    };
}

const props = defineProps<{
    productsConUbicacion: Product[];
    categoriasDisponibles: Record<number, string>;
    defaultCenter?: { lat: number; lng: number };
    totalPropiedades: number;
}>();


const mapContainer = ref<HTMLElement | null>(null);
const selectedProduct = ref<Product | null>(null);
const isLocatingUser = ref(false);
const currentImageIndex = ref(0);
const categoriaSeleccionada = ref<number | null>(null);
const showCategoryDropdown = ref(false);
let map: L.Map | null = null;
const markers: L.Marker[] = [];
let markerClusterGroup: L.MarkerClusterGroup | null = null;
let userLocationMarker: L.Marker | null = null;
let userLocationCircle: L.Circle | null = null;

const defaultCenter = props.defaultCenter || { lat: -17.0000, lng: -64.0000 };

const sortedImages = computed(() => {
    if (!selectedProduct.value?.images?.length) return [];

    return [...selectedProduct.value.images].sort((a, b) => {
        if (a.is_primary && !b.is_primary) return -1;
        if (!a.is_primary && b.is_primary) return 1;
        return a.order - b.order;
    });
});

const productosFiltrados = computed(() => {
    if (!categoriaSeleccionada.value) {
        return props.productsConUbicacion;
    }

    return props.productsConUbicacion.filter(product => {
        return product.category_id === categoriaSeleccionada.value;
    });
});

const totalPropiedadesFiltradas = computed(() => {
    return productosFiltrados.value.length;
});

const nombreCategoriaSeleccionada = computed(() => {
    if (!categoriaSeleccionada.value) return 'Todas las categorías';
    return props.categoriasDisponibles[categoriaSeleccionada.value] || 'Todas las categorías';
});

const getImageUrl = (imagePath: string) => {
    return `/storage/${imagePath}`;
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(price);
};

const getOperacionIcon = (operacion: string) => {
    switch (operacion) {
        case 'venta':
            return '🏠';
        case 'alquiler':
            return '🔑';
        case 'anticretico':
            return '📄';
        default:
            return '🏠';
    }
};

const generateProductSpecs = (product: Product) => {
    const specs = [];

    // Especificaciones principales (dormitorios, baños, ambientes, parqueos)
    const mainSpecs = [];
    if (product.habitaciones) {
        mainSpecs.push(`${product.habitaciones} ${product.habitaciones === 1 ? 'Dormitorio' : 'Dormitorios'}`);
    }
    if (product.banos) {
        mainSpecs.push(`${product.banos} ${product.banos === 1 ? 'Baño' : 'Baños'}`);
    }
    if (product.ambientes) {
        mainSpecs.push(`${product.ambientes} ${product.ambientes === 1 ? 'Ambiente' : 'Ambientes'}`);
    }
    if (product.cocheras) {
        mainSpecs.push(`${product.cocheras} ${product.cocheras === 1 ? 'Parqueo' : 'Parqueos'}`);
    }

    if (mainSpecs.length > 0) {
        specs.push(mainSpecs.join(' • '));
    }

    // Superficies
    const surfaceSpecs = [];
    if (product.superficie_construida) {
        surfaceSpecs.push(`${product.superficie_construida} m² construidos`);
    }
    if (product.superficie_util) {
        surfaceSpecs.push(`${product.superficie_util} m² útiles`);
    }

    if (surfaceSpecs.length > 0) {
        specs.push(surfaceSpecs.join(' • '));
    }

    // Antigüedad
    if (product.antiguedad !== null && product.antiguedad !== undefined) {
        specs.push(`${product.antiguedad} ${product.antiguedad === 1 ? 'año de antigüedad' : 'años de antigüedad'}`);
    }

    // Comisión si existe
    if (product.comision) {
        specs.push(`${product.comision}% de comisión`);
    }

    return specs;
};

const nextImage = () => {
    if (!sortedImages.value.length) return;
    currentImageIndex.value = (currentImageIndex.value + 1) % sortedImages.value.length;
};

const prevImage = () => {
    if (!sortedImages.value.length) return;
    currentImageIndex.value = (currentImageIndex.value - 1 + sortedImages.value.length) % sortedImages.value.length;
};

const goToImage = (index: number) => {
    currentImageIndex.value = index;
};

const initMap = () => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView([defaultCenter.lat, defaultCenter.lng], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Inicializar el grupo de clustering con configuración optimizada
    markerClusterGroup = L.markerClusterGroup({
        // Configuración de clustering
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        removeOutsideVisibleBounds: true,

        // Configuración de estilos para diferentes niveles de clustering
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();

            // Determinar el color según el número de propiedades
            let color = '#10B981'; // verde para pocos
            let size = 40;

            if (count >= 50) {
                color = '#DC2626'; // rojo para muchos
                size = 60;
            } else if (count >= 20) {
                color = '#F59E0B'; // naranja para cantidad media
                size = 50;
            } else if (count >= 10) {
                color = '#3B82F6'; // azul para varios
                size = 45;
            }

            return L.divIcon({
                className: 'custom-cluster-icon',
                html: `
                    <div style="
                        background: ${color};
                        border: 3px solid white;
                        border-radius: 50%;
                        width: ${size}px;
                        height: ${size}px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-weight: bold;
                        font-size: ${size >= 50 ? '16px' : '14px'};
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        position: relative;
                    ">
                        <div style="
                            position: absolute;
                            top: -8px;
                            left: -8px;
                            right: -8px;
                            bottom: -8px;
                            border: 2px solid ${color};
                            border-radius: 50%;
                            opacity: 0.3;
                            animation: pulse-cluster 2s infinite;
                        "></div>
                        ${count}
                    </div>
                    <style>
                        @keyframes pulse-cluster {
                            0% { transform: scale(0.9); opacity: 0.3; }
                            50% { transform: scale(1.1); opacity: 0.2; }
                            100% { transform: scale(0.9); opacity: 0.3; }
                        }
                    </style>
                `,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
                popupAnchor: [0, -size / 2]
            });
        },

        // Configuración de cuándo agrupar/desagrupar
        maxClusterRadius: function (zoom) {
            // Radio adaptativo según el nivel de zoom
            if (zoom <= 10) return 80;  // zoom lejano, clusters grandes
            if (zoom <= 12) return 60;  // zoom medio
            if (zoom <= 14) return 40;  // zoom cercano
            return 20;                 // zoom muy cercano, clusters pequeños
        }
    });

    // Agregar el grupo de clustering al mapa
    map.addLayer(markerClusterGroup);

    // Cargar los marcadores iniciales
    updateMarkers();
};

const centerOnMyLocation = () => {
    if (!map) return;

    if ('geolocation' in navigator) {
        isLocatingUser.value = true;

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;

                map!.setView([latitude, longitude], 17, {
                    animate: true,
                    duration: 1.5
                });

                if (userLocationMarker) {
                    map!.removeLayer(userLocationMarker);
                }
                if (userLocationCircle) {
                    map!.removeLayer(userLocationCircle);
                }

                userLocationCircle = L.circle([latitude, longitude], {
                    radius: accuracy,
                    color: '#3B82F6',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.1,
                    weight: 2
                }).addTo(map!);

                const pulsingIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div style="position: relative;">
                            <div style="
                                width: 20px;
                                height: 20px;
                                background: #3B82F6;
                                border: 4px solid white;
                                border-radius: 50%;
                                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
                                animation: pulse 2s infinite;
                                position: relative;
                                z-index: 1000;
                            "></div>
                            <style>
                                @keyframes pulse {
                                    0% {
                                        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
                                    }
                                    70% {
                                        box-shadow: 0 0 0 20px rgba(59, 130, 246, 0);
                                    }
                                    100% {
                                        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
                                    }
                                }
                            </style>
                        </div>
                    `,
                    iconSize: [12, 12],
                    iconAnchor: [12, 12]
                });

                userLocationMarker = L.marker([latitude, longitude], {
                    icon: pulsingIcon
                }).addTo(map!)
                    .bindPopup(`
                        <div class="w-max text-center text-xs">
                            <p class="font-bold text-blue-600">📍 Tu ubicación actual</p>
                        </div>
                    `)
                    .openPopup();

                setTimeout(() => {
                    if (userLocationMarker && map) {
                        userLocationMarker.closePopup();
                    }
                }, 2000);

                isLocatingUser.value = false;
            },
            (error) => {
                isLocatingUser.value = false;
                console.error('Error obteniendo ubicación:', error);

                let errorMessage = 'No se pudo obtener tu ubicación.';

                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Permiso de ubicación denegado.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'La información de ubicación no está disponible.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'La solicitud de ubicación ha expirado.';
                        break;
                }

                alert(errorMessage);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Tu navegador no soporta geolocalización');
    }
};

const resetView = () => {
    if (!map || markers.length === 0) return;

    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
        userLocationMarker = null;
    }
    if (userLocationCircle) {
        map.removeLayer(userLocationCircle);
        userLocationCircle = null;
    }

    // Usar el grupo de clustering para ajustar los límites
    if (markerClusterGroup) {
        map.fitBounds(markerClusterGroup.getBounds().pad(0.1), {
            animate: true,
            duration: 1
        });
    }
};

const closeDetails = () => {
    selectedProduct.value = null;
    currentImageIndex.value = 0;
};

const goBack = () => {
    router.visit('/propiedades');
};

const goToHome = () => {
    router.visit('/');
};

const goToProperties = () => {
    router.visit('/propiedades');
};

const selectCategoria = (categoryId: number | null) => {
    categoriaSeleccionada.value = categoryId;
    showCategoryDropdown.value = false;
    updateMarkers();
};

const updateMarkers = () => {
    if (!map || !markerClusterGroup) return;

    // Limpiar marcadores existentes
    markerClusterGroup.clearLayers();
    markers.length = 0;

    // Función del popup con manejo correcto de imágenes y datos informativos
    const popupContent = (product: Product) => {
        const images = product.images && product.images.length > 0
            ? product.images.sort((a, b) => (a.is_primary ? -1 : 1))
            : [];

        const imageUrl = (path: string) => `/storage/${path}`;
        const hasImages = images.length > 0;

        const firstImage = hasImages ? imageUrl(images[0].image_path) : null;
        const operacionIcon = getOperacionIcon(product.operacion);
        const specs = generateProductSpecs(product);

        return `
            <div class="popup-card"
                 style="width:320px;border-radius:12px;overflow:hidden;
                        position:relative;
                        box-shadow:0 4px 12px rgba(0,0,0,0.2);
                        font-family:sans-serif;background:white;">

                <div class="image-container"
                     data-images='${JSON.stringify(images.map(i => imageUrl(i.image_path)))}'
                     data-has-images="${hasImages}"
                     style="position:relative;width:100%;height:180px;overflow:hidden;">

                    ${hasImages ? `
                        <img class="carousel-image"
                            src="${firstImage}"
                            style="width:100%;height:180px;object-fit:cover;display:block;"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                        />
                        <div class="fallback-icon"
                             style="display:none;width:100%;height:100%;
                                    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    align-items:center;justify-content:center;color:white;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>

                        ${images.length > 1 ? `
                            <button class="carousel-prev"
                                style="position:absolute;top:50%;left:8px;transform:translateY(-50%);
                                       background:rgba(0,0,0,0.7);color:white;border:none;
                                       width:32px;height:32px;border-radius:50%;cursor:pointer;
                                       font-size:20px;font-weight:bold;z-index:10;
                                       display:flex;align-items:center;justify-content:center;">
                                ‹
                            </button>
                            <button class="carousel-next"
                                style="position:absolute;top:50%;right:8px;transform:translateY(-50%);
                                       background:rgba(0,0,0,0.7);color:white;border:none;
                                       width:32px;height:32px;border-radius:50%;cursor:pointer;
                                       font-size:20px;font-weight:bold;z-index:10;
                                       display:flex;align-items:center;justify-content:center;">
                                ›
                            </button>

                            <div class="image-counter"
                                 style="position:absolute;bottom:8px;right:8px;
                                        background:rgba(0,0,0,0.7);color:white;
                                        padding:4px 10px;border-radius:12px;font-size:12px;z-index:10;">
                                <span class="current-index">1</span> / ${images.length}
                            </div>
                        ` : ''}
                    ` : `
                        <div style="display:flex;width:100%;height:100%;
                                    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    align-items:center;justify-content:center;color:white;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                    `}

                    <!-- Badge de operación -->
                    <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.8);color:white;
                               padding:4px 8px;border-radius:12px;font-size:12px;z-index:10;">
                        ${operacionIcon} ${product.operacion.charAt(0).toUpperCase() + product.operacion.slice(1)}
                    </div>
                </div>

                <div style="padding:12px;background:white;">
                    <h3 style="font-size:16px;font-weight:bold;margin-bottom:6px;color:#333;line-height:1.2;">
                        ${product.name}
                    </h3>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:11px;color:#666;background:#f3f4f6;padding:2px 6px;border-radius:4px;">
                            🏷️ ${product.codigo_inmueble}
                        </span>
                        <span style="font-size:11px;color:#666;background:#f3f4f6;padding:2px 6px;border-radius:4px;">
                            📁 ${product.category ?? 'N/A'}
                        </span>
                    </div>

                    <!-- Especificaciones del inmueble -->
                    ${specs.length > 0 ? `
                        <div style="margin:8px 0;padding:8px;background:#f9fafb;border-radius:6px;border-left:3px solid #3B82F6;">
                            ${specs.map(spec => `
                                <div style="font-size:11px;color:#374151;margin:2px 0;line-height:1.3;">
                                    ${spec}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}

                    <!-- Precio -->
                    <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
                        <p style="font-size:20px;font-weight:bold;color:#0a8a0a;margin:0;">
                            ${formatPrice(product.price)}
                        </p>
                    </div>

                    <a
                        href="/propiedad/${product.id}"
                        style="
                            display:inline-block;width:100%;margin-top:12px;background:#3B82F6;
                            color:white;padding:10px 0;border-radius:6px;border:none;
                            font-weight:bold;cursor:pointer;text-align:center;text-decoration:none;
                            transition:background-color 0.2s;
                        "
                        onmouseover="this.style.backgroundColor='#2563EB'"
                        onmouseout="this.style.backgroundColor='#3B82F6'"
                    >
                        Ver Detalle Completo
                    </a>
                </div>
            </div>
        `;
    };

    // Agregar marcadores filtrados al grupo de clustering
    productosFiltrados.value.forEach((product) => {
        if (!product.location.is_active) return;

        // Icono personalizado según la operación
        const operacionIcon = getOperacionIcon(product.operacion);
        const customIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `
                <div style="
                    background: ${product.operacion === 'venta' ? '#10B981' : product.operacion === 'alquiler' ? '#3B82F6' : '#8B5CF6'};
                    border: 3px solid white;
                    border-radius: 50%;
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    z-index: 1000;
                ">${operacionIcon}</div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        });

        const marker = L.marker([product.location.latitude, product.location.longitude], {
            icon: customIcon
        });

        marker.bindPopup(popupContent(product), {
            maxWidth: 300,
            className: 'custom-popup'
        });

        // Event listener del carrusel
        marker.on("popupopen", (e) => {
            const popup = e.popup.getElement();
            if (!popup) return;

            const container = popup.querySelector(".image-container");
            if (!container) return;

            const hasImages = container.getAttribute("data-has-images") === "true";
            if (!hasImages) return;

            const imgEl = popup.querySelector(".carousel-image") as HTMLImageElement;
            const prevBtn = popup.querySelector(".carousel-prev");
            const nextBtn = popup.querySelector(".carousel-next");
            const counterEl = popup.querySelector(".current-index");

            const images = JSON.parse(container.getAttribute("data-images") || "[]");
            if (images.length === 0) return;

            let index = 0;

            const updateImage = () => {
                if (imgEl) {
                    imgEl.src = images[index];
                    imgEl.style.display = 'block';
                    const fallbackIcon = imgEl.nextElementSibling as HTMLElement;
                    if (fallbackIcon) fallbackIcon.style.display = 'none';
                }
                if (counterEl) {
                    counterEl.textContent = (index + 1).toString();
                }
            };

            prevBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                index = (index - 1 + images.length) % images.length;
                updateImage();
            });

            nextBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                index = (index + 1) % images.length;
                updateImage();
            });

            imgEl?.addEventListener('error', () => {
                imgEl.style.display = 'none';
                const fallbackIcon = imgEl.nextElementSibling as HTMLElement;
                if (fallbackIcon) fallbackIcon.style.display = 'flex';
            });
        });

        marker.on('click', () => {
            selectedProduct.value = product;
        });

        // Agregar marcador al grupo de clustering
        if (markerClusterGroup) {
            markerClusterGroup.addLayer(marker);
        }
        markers.push(marker);
    });

    // Ajustar el mapa para mostrar los marcadores filtrados
    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

// Función para cerrar dropdown al hacer clic fuera
const closeCategoryDropdown = () => {
    showCategoryDropdown.value = false;
};

onMounted(() => {
    addFullScreenStyles();
    initMap();
});

onUnmounted(() => {
    removeFullScreenStyles();
    if (map) {
        if (markerClusterGroup) {
            map.removeLayer(markerClusterGroup);
        }
        map.remove();
        map = null;
    }
});

// Estilos para pantalla completa
const addFullScreenStyles = () => {
    const style = document.createElement('style');
    style.setAttribute('data-fullscreen-map', 'true');
    style.textContent = `
        body, html {
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100%;
            width: 100%;
        }
        #app {
            height: 100vh !important;
            width: 100vw !important;
            overflow: hidden !important;
        }
        .leaflet-control-container .leaflet-top.leaflet-left {
            top: 80px !important;
        }
        .leaflet-control-container .leaflet-top.leaflet-right {
            top: 80px !important;
        }
        @media (max-width: 640px) {
            .leaflet-control-container .leaflet-top.leaflet-left,
            .leaflet-control-container .leaflet-top.leaflet-right {
                top: 120px !important;
            }
        }
    `;
    document.head.appendChild(style);
};

// Remover estilos al desmontar
const removeFullScreenStyles = () => {
    const styles = document.querySelectorAll('style[data-fullscreen-map]');
    styles.forEach(style => style.remove());
};
</script>

<template>
    <Head title="Mapa Interactivo de Propiedades" />

    <div class="relative w-screen h-screen overflow-hidden" @click="closeCategoryDropdown">
        <!-- Mapa -->
        <div ref="mapContainer" class="absolute inset-0 w-full h-full"></div>

            <!-- Controles superiores -->
            <div class="absolute top-4 left-4 right-4 z-[1000] flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3" @click.stop>
                <!-- Controles de navegación y categorías -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 flex-wrap">
                    <button
                        @click="goToHome"
                        class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
                        title="Inicio"
                    >
                        <Home :size="18" />
                        <span class="font-semibold hidden sm:inline text-sm">Inicio</span>
                    </button>

                    <button
                        @click="goToProperties"
                        class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
                        title="Ver todas las propiedades"
                    >
                        <Filter :size="18" />
                        <span class="font-semibold hidden sm:inline text-sm">Propiedades</span>
                    </button>

                    <!-- Dropdown de categorías -->
                    <div class="relative">
                        <button
                            @click.stop="showCategoryDropdown = !showCategoryDropdown"
                            class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm min-w-[160px]"
                            title="Filtrar por categoría"
                        >
                            <Filter :size="18" />
                            <span class="font-medium text-sm truncate">{{ nombreCategoriaSeleccionada }}</span>
                            <ChevronDown :size="16" :class="{ 'rotate-180': showCategoryDropdown }" class="transition-transform flex-shrink-0" />
                        </button>

                        <!-- Dropdown menu -->
                        <div
                            v-if="showCategoryDropdown"
                            class="absolute top-full left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-80 overflow-y-auto"
                            @click.stop
                        >
                            <div class="p-2">
                                <!-- Opción "Todas" -->
                                <button
                                    @click="selectCategoria(null)"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                                        !categoriaSeleccionada
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'hover:bg-gray-50 text-gray-700'
                                    ]"
                                >
                                    <span>Todas las categorías</span>
                                    <span v-if="!categoriaSeleccionada" class="text-blue-600">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div class="border-t border-gray-100 my-1"></div>

                                <!-- Lista de categorías -->
                                <button
                                    v-for="(nombre, id) in categoriasDisponibles"
                                    :key="id"
                                    @click="selectCategoria(parseInt(id as string))"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between',
                                        categoriaSeleccionada === parseInt(id as string)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'hover:bg-gray-50 text-gray-700'
                                    ]"
                                >
                                    <span class="truncate">{{ nombre }}</span>
                                    <span v-if="categoriaSeleccionada === parseInt(id as string)" class="text-blue-600 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controles del mapa -->
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="bg-white/95 px-3 py-2 rounded-lg shadow-lg backdrop-blur-sm">
                        <p class="text-xs sm:text-sm font-semibold text-gray-700">
                            {{ totalPropiedadesFiltradas }} de {{ totalPropiedades }} propiedades
                        </p>
                    </div>

                    <button
                        @click="centerOnMyLocation"
                        :disabled="isLocatingUser"
                        :class="[
                            'p-2 sm:p-3 rounded-lg shadow-lg transition-all flex items-center gap-2 backdrop-blur-sm',
                            isLocatingUser
                                ? 'bg-blue-400/90 cursor-not-allowed'
                                : 'bg-blue-600/90 hover:bg-blue-700/90'
                        ]"
                        class="text-white"
                        title="Centrar en mi ubicación"
                    >
                        <Navigation :size="18" :class="isLocatingUser ? 'animate-pulse' : ''" />
                        <span v-if="isLocatingUser" class="text-xs sm:text-sm font-medium hidden sm:inline">Ubicando...</span>
                    </button>

                    <button
                        @click="resetView"
                        class="bg-white/95 hover:bg-white text-gray-700 px-3 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all backdrop-blur-sm"
                        title="Ver todas las propiedades"
                    >
                        <MapPin :size="18" />
                        <span class="font-semibold hidden sm:inline text-sm">Ver Todo</span>
                    </button>
                </div>
            </div>

            <!-- Panel de Detalles del Producto -->
            <Transition
                @click.stop
                enter-active-class="transition-transform duration-300"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-300"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div
                    v-if="selectedProduct"
                    class="absolute top-0 right-0 h-full w-full sm:max-w-sm md:w-96 bg-white shadow-2xl z-[1001] overflow-y-auto"
                >
                    <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-white">Detalles de la Propiedad</h3>
                        <button
                            @click="closeDetails"
                            class="text-white hover:bg-white/20 rounded-full p-1 transition-colors"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <div class="p-6">
                        <div v-if="sortedImages.length > 0" class="mb-6 -mx-6 -mt-6">
                            <div class="relative group">
                                <img
                                    :src="getImageUrl(sortedImages[currentImageIndex].image_path)"
                                    :alt="sortedImages[currentImageIndex].original_name"
                                    class="w-full h-64 object-cover"
                                />

                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                                <template v-if="sortedImages.length > 1">
                                    <button
                                        @click="prevImage"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="nextImage"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div class="absolute bottom-3 right-3 bg-black/70 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                                        {{ currentImageIndex + 1 }} / {{ sortedImages.length }}
                                    </div>
                                </template>

                                <div v-if="sortedImages[currentImageIndex].is_primary" class="absolute top-3 left-3">
                                    <span class="inline-flex items-center gap-1 bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-bold shadow-lg">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Principal
                                    </span>
                                </div>
                            </div>

                            <div v-if="sortedImages.length > 1" class="px-4 py-3 bg-gray-50">
                                <div class="flex gap-2 overflow-x-auto pb-2">
                                    <button
                                        v-for="(image, index) in sortedImages"
                                        :key="image.id"
                                        @click="goToImage(index)"
                                        :class="[
                                            'flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all border-2',
                                            currentImageIndex === index
                                                ? 'border-blue-500 ring-2 ring-blue-200 scale-110'
                                                : 'border-transparent opacity-60 hover:opacity-100'
                                        ]"
                                    >
                                        <img
                                            :src="getImageUrl(image.image_path)"
                                            :alt="`Thumbnail ${index + 1}`"
                                            class="w-full h-full object-cover"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="mb-4 w-full h-56 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center -mx-6 -mt-6">
                            <div class="text-center">
                                <MapPin :size="48" class="text-gray-400 mx-auto mb-2" />
                                <span class="text-gray-400 text-sm">Sin imágenes disponibles</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">
                                    {{ selectedProduct.name }}
                                </h4>

                                <!-- Badge de operación -->
                                <div class="inline-flex items-center gap-2 mb-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium',
                                        selectedProduct.operacion === 'venta' ? 'bg-green-100 text-green-800' :
                                        selectedProduct.operacion === 'alquiler' ? 'bg-blue-100 text-blue-800' :
                                        'bg-purple-100 text-purple-800'
                                    ]">
                                        <span class="text-lg">{{ getOperacionIcon(selectedProduct.operacion) }}</span>
                                        {{ selectedProduct.operacion.charAt(0).toUpperCase() + selectedProduct.operacion.slice(1) }}
                                    </span>
                                </div>

                                <div class="inline-flex items-center gap-2 bg-green-50 px-3 py-2 rounded-lg">
                                    <span class="text-sm text-green-600 font-medium">Precio:</span>
                                    <span class="text-2xl font-bold text-green-600">
                                        {{ formatPrice(selectedProduct.price) }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Código</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.codigo_inmueble }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Categoría</p>
                                    <p class="font-semibold text-gray-800">{{ selectedProduct.category || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <MapPin :size="20" class="text-blue-600 mt-0.5 flex-shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Ubicación</p>
                                        <p class="text-sm text-gray-700 mb-2">
                                            {{ selectedProduct.location.address || 'Sin dirección especificada' }}
                                        </p>
                                        <p class="text-xs text-gray-500 font-mono">
                                            {{ selectedProduct.location.latitude.toFixed(6) }},
                                            {{ selectedProduct.location.longitude.toFixed(6) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <a
                                    :href="`https://www.google.com/maps/search/?api=1&query=${selectedProduct.location.latitude},${selectedProduct.location.longitude}`"
                                    target="_blank"
                                    class="flex items-center justify-center gap-2 flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors"
                                >
                                    <Navigation :size="20" />
                                    Google Maps
                                </a>

                                <a
                                    :href="`/propiedad/${selectedProduct.id}`"
                                    class="flex items-center justify-center gap-2 flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition-colors"
                                >
                                    <Search :size="20" />
                                    Ver Más
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
    </div>
</template>