<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Alquileres - GeoPricer Bolivia
    |--------------------------------------------------------------------------
    |
    | Valores de referencia para calcular justificación de precios de alquiler
    | en diferentes zonas de Bolivia. Estos valores son configurables y pueden
    | ajustarse según el mercado.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Zonas de Cobertura (Coordenadas para detectar ubicación)
    |--------------------------------------------------------------------------
    */
    'zonas' => [
        'sopocachi' => [
            'nombre' => 'Sopocachi',
            'base_alquiler' => 600, // USD base mensual
            'lat_center' => -16.5150,
            'lng_center' => -68.1250,
            'radio_km' => 1.5,
        ],
        'san_miguel' => [
            'nombre' => 'San Miguel',
            'base_alquiler' => 550,
            'lat_center' => -16.5250,
            'lng_center' => -68.1150,
            'radio_km' => 2.0,
        ],
        'calacoto' => [
            'nombre' => 'Calacoto',
            'base_alquiler' => 700,
            'lat_center' => -16.5350,
            'lng_center' => -68.0850,
            'radio_km' => 2.5,
        ],
        'miraflores' => [
            'nombre' => 'Miraflores',
            'base_alquiler' => 500,
            'lat_center' => -16.5050,
            'lng_center' => -68.1350,
            'radio_km' => 2.0,
        ],
        'centro' => [
            'nombre' => 'Centro',
            'base_alquiler' => 450,
            'lat_center' => -16.4950,
            'lng_center' => -68.1350,
            'radio_km' => 1.5,
        ],
        'obrajes' => [
            'nombre' => 'Obrajes',
            'base_alquiler' => 520,
            'lat_center' => -16.5450,
            'lng_center' => -68.0750,
            'radio_km' => 2.0,
        ],
        'san_pedro' => [
            'nombre' => 'San Pedro',
            'base_alquiler' => 480,
            'lat_center' => -16.5050,
            'lng_center' => -68.1450,
            'radio_km' => 1.8,
        ],
        'zona_sur' => [
            'nombre' => 'Zona Sur',
            'base_alquiler' => 400,
            'lat_center' => -16.5500,
            'lng_center' => -68.0500,
            'radio_km' => 5.0,
        ],
        'otra_zona' => [
            'nombre' => 'Otra Zona',
            'base_alquiler' => 400,
            'lat_center' => -17.38, // La Paz (default)
            'lng_center' => -66.16,
            'radio_km' => 50.0, // Radio grande para capturar todo
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores por Característica
    |--------------------------------------------------------------------------
    |
    | Cuánto vale cada característica adicional en el precio del alquiler
    |
    */
    'tasas' => [
        'habitacion' => 100,      // USD por cada habitación adicional
        'bano' => 50,             // USD por cada baño adicional
        'cochera' => 80,          // USD por cochera
        'superficie_extra' => 5,  // USD por cada m² adicional sobre 80m²

        // Amenities (valor mensual que aportan)
        'piscina' => 60,
        'gimnasio' => 40,
        'seguridad_24h' => 30,
        'estacionamiento' => 20,
        'areas_verdes' => 25,
        'quincho' => 35,
        'lavanderia' => 15,

        // Ajustes por estado de conservación
        'estado_nuevo' => 0,       // Sin ajuste (ya incluido en base)
        'estado_bueno' => -50,     // Descuento de $50
        'estado_regular' => -100,  // Descuento de $100
        'estado_malo' => -200,     // Descuento de $200
    ],

    /*
    |--------------------------------------------------------------------------
    | Umbrales de Comparación
    |--------------------------------------------------------------------------
    |
    | Qué porcentaje de diferencia con el promedio se considera:
    | - MUY BARATO: más de 15% abajo del promedio
    | - BARATO: entre 5% y 15% abajo del promedio
    | - JUSTIFICADO: dentro del ±5% del promedio
    | - CARO: entre 5% y 15% arriba del promedio
    | - MUY CARO: más de 15% arriba del promedio
    */
    'umbrales' => [
        'muy_barato' => -15,
        'barato' => -5,
        'justificado_min' => -5,
        'justificado_max' => 5,
        'caro' => 5,
        'muy_caro' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Superficie Base
    |--------------------------------------------------------------------------
    |
    | Superficie mínima considerada "estándar" (no aumenta el precio)
    | Por encima de esto, cada m² adicional añade valor
    */
    'superficie_base_m2' => 80,

    /*
    |--------------------------------------------------------------------------
    | Amenities Detectados por Superficie
    |--------------------------------------------------------------------------
    |
    | Si la propiedad supera estos m², se asume que tiene ciertos amenities
    | (esto se usa cuando no tenemos datos explícitos de amenities)
    */
    'amenities_por_superficie' => [
        'threshold_piscina' => 150,    // m² para asumir piscina
        'threshold_gimnasio' => 120,   // m² para asumir gimnasio
        'threshold_seguridad' => 100,  // m² para asumir seguridad 24h
    ],

    /*
    |--------------------------------------------------------------------------
    | Rangos de Precio por Tipo de Propiedad
    |--------------------------------------------------------------------------
    |
    | Para mostrar al usuario qué es "normal" pagar según características
    */
    'rangos_por_habitaciones' => [
        1 => ['min' => 400, 'max' => 700],    // 1 habitación
        2 => ['min' => 600, 'max' => 950],    // 2 habitaciones
        3 => ['min' => 800, 'max' => 1300],   // 3 habitaciones
        4 => ['min' => 1000, 'max' => 1600],  // 4+ habitaciones
    ],

    /*
    |--------------------------------------------------------------------------
    | Factores de Ajuste por Tipo de Propiedad
    |--------------------------------------------------------------------------
    */
    'factores_tipo_propiedad' => [
        'departamento' => 1.0,    // Sin ajuste
        'casa' => 1.1,            // +10% por ser casa
        'loft' => 0.9,            // -10% por ser loft
        'penthouse' => 1.3,       // +30% por ser penthouse
        'estudio' => 0.75,        // -25% por ser estudio
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Visualización
    |--------------------------------------------------------------------------
    */
    'visualizacion' => [
        'mostrar_desglose_completo' => true,
        'mostrar_comparacion_promedio' => true,
        'mostrar_indicador_justificacion' => true,
        'color_muy_barato' => '#10b981',   // Verde
        'color_barato' => '#34d399',       // Verde claro
        'color_justificado' => '#3b82f6',  // Azul
        'color_caro' => '#f59e0b',         // Naranja
        'color_muy_caro' => '#ef4444',     // Rojo
    ],
];
