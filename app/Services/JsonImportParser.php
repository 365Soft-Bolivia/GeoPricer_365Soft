<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductCategory;

class JsonImportParser
{
    /**
     * Tipos de JSON soportados
     */
    const FORMAT_CENTURY21 = 'century21';
    const FORMAT_REMAX = 'remax';
    const FORMAT_INFOCASAS = 'infocasas';
    const FORMAT_STANDARD = 'standard';

    /**
     * Detectar el formato del JSON
     */
    public function detectFormat(array $data): string
    {
        // REMAX: tiene props.listingsData.data
        if (isset($data['props']['listingsData']['data']) && is_array($data['props']['listingsData']['data'])) {
            return self::FORMAT_REMAX;
        }

        // InfoCasas: tiene hits.hits con _source
        if (isset($data['hits']['hits']) && is_array($data['hits']['hits'])) {
            return self::FORMAT_INFOCASAS;
        }

        // Century 21: tiene results
        if (isset($data['results']) && is_array($data['results'])) {
            return self::FORMAT_CENTURY21;
        }

        // Standard: array directo o subtype_properties
        return self::FORMAT_STANDARD;
    }

    /**
     * Extraer items del JSON según el formato detectado
     */
    public function extractItems(array $data, string $format): array
    {
        switch ($format) {
            case self::FORMAT_REMAX:
                return $this->extractRemaxItems($data);

            case self::FORMAT_INFOCASAS:
                return $this->extractInfocasasItems($data);

            case self::FORMAT_CENTURY21:
                return $this->extractCentury21Items($data);

            case self::FORMAT_STANDARD:
                return $this->extractStandardItems($data);

            default:
                return [];
        }
    }

    /**
     * Extraer items de JSON estándar (original)
     */
    protected function extractStandardItems(array $data): array
    {
        $items = [];

        // Si tiene subtype_properties (estructura original)
        if (isset($data['subtype_properties'])) {
            foreach ($data['subtype_properties'] as $type) {
                if (isset($type['subtype_properties'])) {
                    foreach ($type['subtype_properties'] as $subtype) {
                        $items[] = $subtype;
                    }
                }
            }
        }
        // Si es un array directo
        elseif (isset($data[0])) {
            $items = $data;
        }
        // Si es un solo objeto
        else {
            $items[] = $data;
        }

        return $items;
    }

    /**
     * Extraer items de JSON de Century 21
     */
    protected function extractCentury21Items(array $data): array
    {
        return $data['results'] ?? [];
    }

    /**
     * Extraer items de JSON de InfoCasas
     * Estructura: hits.hits[_source.listing]
     */
    protected function extractInfocasasItems(array $data): array
    {
        $items = [];
        $hits = $data['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            if (isset($hit['_source']['listing'])) {
                $items[] = $hit['_source']['listing'];
            }
        }

        return $items;
    }

    /**
     * Extraer items de JSON de REMAX
     */
    protected function extractRemaxItems(array $data): array
    {
        return $data['props']['listingsData']['data'] ?? [];
    }

    /**
     * Normalizar un item al formato estándar
     * Convierte items de C21, REMAX, InfoCasas, etc. a un formato común
     */
    public function normalizeItem(array $item, string $format): array
    {
        switch ($format) {
            case self::FORMAT_CENTURY21:
                return $this->normalizeCentury21Item($item);

            case self::FORMAT_REMAX:
                return $this->normalizeRemaxItem($item);

            case self::FORMAT_INFOCASAS:
                return $this->normalizeInfocasasItem($item);

            case self::FORMAT_STANDARD:
            default:
                return $item;
        }
    }

    /**
     * Normalizar item de Century 21
     */
    protected function normalizeCentury21Item(array $item): array
    {
        $normalized = [];

        // Nombre
        $normalized['name'] = $item['encabezado'] ?? $item['name'] ?? $item['title'] ?? $item['nombre'] ?? 'Sin nombre';

        // ID externo
        $normalized['id'] = $item['id'] ?? $item['code'] ?? $item['codigo'] ?? uniqid();

        // Slug
        $normalized['slug'] = $item['slug'] ?? null;

        // Categoría (tipoPropiedad)
        if (isset($item['tipoPropiedad'])) {
            $normalized['category'] = $item['tipoPropiedad'];
        }

        // Operación (tipoOperacion)
        if (isset($item['tipoOperacion'])) {
            $normalized['operation'] = $item['tipoOperacion'];
        }

        // Precios anidados (precios.vista y precios.contrato)
        if (isset($item['precios']) && is_array($item['precios'])) {
            // Precios.vista
            if (isset($item['precios']['vista']) && is_array($item['precios']['vista'])) {
                $vistaPrecio = $item['precios']['vista']['precio'] ?? null;
                $vistaMoneda = $item['precios']['vista']['moneda'] ?? 'USD';

                if ($vistaPrecio !== null && is_numeric($vistaPrecio)) {
                    $monedaUpper = strtoupper($vistaMoneda);
                    if ($monedaUpper === 'USD') {
                        $normalized['price_usd'] = floatval($vistaPrecio);
                    } elseif ($monedaUpper === 'BOB') {
                        $normalized['price_bob'] = floatval($vistaPrecio);
                    }
                }
            }

            // Precios.contrato
            if (isset($item['precios']['contrato']) && is_array($item['precios']['contrato'])) {
                $contratoPrecio = $item['precios']['contrato']['precio'] ?? null;
                $contratoMoneda = $item['precios']['contrato']['moneda'] ?? 'BOB';

                if ($contratoPrecio !== null && is_numeric($contratoPrecio)) {
                    $monedaUpper = strtoupper($contratoMoneda);
                    if ($monedaUpper === 'USD' && !isset($normalized['price_usd'])) {
                        $normalized['price_usd'] = floatval($contratoPrecio);
                    } elseif ($monedaUpper === 'BOB' && !isset($normalized['price_bob'])) {
                        $normalized['price_bob'] = floatval($contratoPrecio);
                    }
                }
            }
        }

        // Superficie (m2T y m2C)
        if (isset($item['m2T']) && is_numeric($item['m2T'])) {
            $normalized['superficie_util'] = floatval($item['m2T']);
        }
        if (isset($item['m2C']) && is_numeric($item['m2C'])) {
            $normalized['superficie_construida'] = floatval($item['m2C']);
        }

        // Habitaciones, baños, cocheras
        if (isset($item['recamaras']) && is_numeric($item['recamaras'])) {
            $normalized['habitaciones'] = intval($item['recamaras']);
        }
        if (isset($item['banos']) && is_numeric($item['banos'])) {
            $normalized['banos'] = intval($item['banos']);
        }
        if (isset($item['estacionamientos']) && is_numeric($item['estacionamientos'])) {
            $normalized['cocheras'] = intval($item['estacionamientos']);
        }

        // Coordenadas
        if (isset($item['lat'])) {
            $normalized['lat'] = $item['lat'];
        }
        if (isset($item['lon']) || isset($item['longitude'])) {
            $normalized['lng'] = $item['lon'] ?? $item['longitude'];
        }

        // Dirección
        if (isset($item['calle'])) {
            $normalized['address'] = $item['calle'];
        }

        // Descripción (combinar campos de texto)
        $descriptionParts = [];
        if (isset($item['descripcion']) && !empty($item['descripcion'])) {
            $descriptionParts[] = $this->cleanHtmlDescription($item['descripcion']);
        }
        if (isset($item['municipio']) && !empty($item['municipio'])) {
            $descriptionParts[] = 'Zona: ' . $this->cleanHtmlText($item['municipio']);
        }

        if (!empty($descriptionParts)) {
            $normalized['description'] = implode("\n", $descriptionParts);
        }

        // Mantener todos los campos originales
        return array_merge($item, $normalized);
    }

    /**
     * Normalizar item de REMAX
     */
    protected function normalizeRemaxItem(array $item): array
    {
        $normalized = [];

        // Nombre (extraer del slug)
        $slug = $item['slug'] ?? '';
        if (!empty($slug)) {
            // El slug tiene formato: "venta-terreno-comercial-santa-cruz-de-la-sierra-este-1250013580-6"
            // Lo convertimos a: "Terreno Comercial en Santa Cruz de la Sierra Este"
            $nameParts = explode('-', $slug);
            $nameParts = array_slice($nameParts, 1); // Quitar "venta"
            $nameParts = array_slice($nameParts, 0, -2); // Quitar los últimos 2 elementos (ID)

            if (!empty($nameParts)) {
                $normalized['name'] = ucwords(implode(' ', $nameParts));
            }
        }

        // Fallback: si no hay slug, usar una descripción genérica
        if (!isset($normalized['name'])) {
            $normalized['name'] = $item['listing_information']['subtype_property']['name'] ?? 'Propiedad';
        }

        // ID externo
        $normalized['id'] = $item['id'] ?? uniqid();

        // Slug
        $normalized['slug'] = $slug;

        // Categoría (subtype_property.name)
        if (isset($item['listing_information']['subtype_property']['name'])) {
            $normalized['category'] = $item['listing_information']['subtype_property']['name'];
        }

        // Operación (transaction_type.name)
        if (isset($item['transaction_type']['name'])) {
            $normalized['operation'] = $item['transaction_type']['name'];
        }

        // Precio (price)
        if (isset($item['price']) && is_array($item['price'])) {
            $amount = $item['price']['amount'] ?? null;
            $currency = $item['price']['currency'] ?? 'USD';

            if ($amount !== null && is_numeric($amount)) {
                $currencyUpper = strtoupper($currency);
                if ($currencyUpper === 'USD' || $currencyUpper === 'US$') {
                    $normalized['price_usd'] = floatval($amount);
                } elseif ($currencyUpper === 'BOB' || $currencyUpper === 'BS') {
                    $normalized['price_bob'] = floatval($amount);
                }
            }
        }

        // Superficie (listing_information)
        if (isset($item['listing_information']['construction_area_m']) && is_numeric($item['listing_information']['construction_area_m'])) {
            $normalized['superficie_construida'] = floatval($item['listing_information']['construction_area_m']);
        }
        if (isset($item['listing_information']['land_m2']) && is_numeric($item['listing_information']['land_m2'])) {
            $normalized['superficie_util'] = floatval($item['listing_information']['land_m2']);
        }

        // Habitaciones, baños
        if (isset($item['listing_information']['number_bedrooms']) && is_numeric($item['listing_information']['number_bedrooms'])) {
            $normalized['habitaciones'] = intval($item['listing_information']['number_bedrooms']);
        }
        if (isset($item['listing_information']['number_bathrooms']) && is_numeric($item['listing_information']['number_bathrooms'])) {
            $normalized['banos'] = intval($item['listing_information']['number_bathrooms']);
        }

        // Coordenadas (location)
        if (isset($item['location']['latitude'])) {
            $normalized['lat'] = $item['location']['latitude'];
        }
        if (isset($item['location']['longitude'])) {
            $normalized['lng'] = $item['location']['longitude'];
        }

        // Dirección (location)
        $addressParts = [];
        if (isset($item['location']['zone']['name']) && !empty($item['location']['zone']['name'])) {
            $addressParts[] = $item['location']['zone']['name'];
        }
        if (isset($item['location']['city']['name']) && !empty($item['location']['city']['name'])) {
            $addressParts[] = $item['location']['city']['name'];
        }

        if (!empty($addressParts)) {
            $normalized['address'] = implode(', ', $addressParts);
        }

        // Descripción
        if (!empty($normalized['address'])) {
            $normalized['description'] = 'Ubicada en ' . $normalized['address'];
        }

        // Mantener todos los campos originales
        return array_merge($item, $normalized);
    }

    /**
     * Normalizar item de InfoCasas
     * Extrae datos de technicalSheet (array clave-valor) y locations
     */
    protected function normalizeInfocasasItem(array $item): array
    {
        $normalized = [];

        // Nombre
        $normalized['name'] = $item['title'] ?? 'Sin nombre';

        // ID externo
        $normalized['id'] = $item['id'] ?? $item['code'] ?? uniqid();

        // Slug (no disponible en InfoCasas)
        $normalized['slug'] = null;

        // Descripción
        if (isset($item['description']) && !empty($item['description'])) {
            // Limpiar HTML de la descripción
            $normalized['description'] = $this->cleanHtmlDescription($item['description']);
        }

        // Procesar technicalSheet (array de clave-valor)
        $techSheet = [];
        if (isset($item['technicalSheet']) && is_array($item['technicalSheet'])) {
            foreach ($item['technicalSheet'] as $field) {
                if (isset($field['field']) && isset($field['value']) && !empty($field['value'])) {
                    $techSheet[$field['field']] = $field['value'];
                }
            }
        }

        // Categoría (property_type_name)
        if (isset($techSheet['property_type_name'])) {
            $normalized['category'] = $techSheet['property_type_name'];
        }

        // Baños
        if (isset($techSheet['bathrooms']) && is_numeric($techSheet['bathrooms'])) {
            $normalized['banos'] = intval($techSheet['bathrooms']);
        }

        // Dormitorios (habitaciones)
        if (isset($techSheet['bedrooms']) && is_numeric($techSheet['bedrooms'])) {
            $normalized['habitaciones'] = intval($techSheet['bedrooms']);
        }

        // Garaje (cocheras)
        if (isset($techSheet['garage']) && is_numeric($techSheet['garage'])) {
            $normalized['cocheras'] = intval($techSheet['garage']);
        }

        // Superficie construida (m2Built)
        if (isset($techSheet['m2Built'])) {
            $value = preg_replace('/[^0-9.]/', '', $techSheet['m2Built']);
            if (is_numeric($value)) {
                $normalized['superficie_construida'] = floatval($value);
            }
        }

        // Superficie terreno (m2Terrain)
        if (isset($techSheet['m2Terrain'])) {
            $value = preg_replace('/[^0-9.]/', '', $techSheet['m2Terrain']);
            if (is_numeric($value)) {
                $normalized['superficie_util'] = floatval($value);
            }
        }

        // Precio (price.amount y price.currency.name)
        if (isset($item['price']) && is_array($item['price'])) {
            $amount = $item['price']['amount'] ?? null;
            $currencyName = $item['price']['currency']['name'] ?? 'USD';

            if ($amount !== null && is_numeric($amount)) {
                $currencyUpper = strtoupper(str_replace(['U$S', 'US$'], 'USD', $currencyName));
                if ($currencyUpper === 'USD') {
                    $normalized['price_usd'] = floatval($amount);
                } elseif ($currencyUpper === 'BOB' || $currencyUpper === 'BS') {
                    $normalized['price_bob'] = floatval($amount);
                }
            }
        }

        // Coordenadas (locations.location_point)
        // Formato: "POINT (longitude latitude)"
        if (isset($item['locations']['location_point']) && !empty($item['locations']['location_point'])) {
            $point = $item['locations']['location_point'];
            // Extraer coordenadas de "POINT (lng lat)"
            if (preg_match('/POINT\s*\(\s*(-?\d+\.?\d*)\s+(-?\d+\.?\d*)\s*\)/i', $point, $matches)) {
                $normalized['lng'] = floatval($matches[1]); // Longitude primero
                $normalized['lat'] = floatval($matches[2]); // Latitude después
            }
        }

        // Dirección (locations)
        $addressParts = [];
        if (isset($item['locations']['neighbourhood']) && is_array($item['locations']['neighbourhood']) && !empty($item['locations']['neighbourhood'])) {
            $neighbourhood = $item['locations']['neighbourhood'][0]['name'] ?? '';
            if (!empty($neighbourhood)) {
                $addressParts[] = $neighbourhood;
            }
        }
        if (isset($item['locations']['city']) && is_array($item['locations']['city']) && !empty($item['locations']['city'])) {
            $city = $item['locations']['city'][0]['name'] ?? '';
            if (!empty($city)) {
                $addressParts[] = $city;
            }
        }
        if (isset($item['locations']['state']) && is_array($item['locations']['state']) && !empty($item['locations']['state'])) {
            $state = $item['locations']['state'][0]['name'] ?? '';
            if (!empty($state)) {
                $addressParts[] = $state;
            }
        }

        if (!empty($addressParts)) {
            $normalized['address'] = implode(', ', $addressParts);
        }

        // Mantener todos los campos originales
        return array_merge($item, $normalized);
    }

    /**
     * Limpiar descripción HTML y convertirla a texto plano legible
     * Convierte <p>, <br> a saltos de línea, elimina tags innecesarios
     * y limpía espacios y saltos de línea excesivos
     *
     * @param string|null $html La descripción HTML
     * @return string Descripción limpia en texto plano
     */
    protected function cleanHtmlDescription(?string $html): string
    {
        if (empty($html) || !is_string($html)) {
            return '';
        }

        // Decodificar entidades HTML primero (para &nbsp;, &amp;, etc.)
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Reemplazar etiquetas de párrafo y salto de línea por saltos de línea
        // Primero reemplazamos <br> y sus variantes
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/br>/i', "\n", $text);

        // Reemplazar <p> por salto de línea
        $text = preg_replace('/<p\b[^>]*>/i', "\n", $text);

        // Reemplazar </p> por salto de línea
        $text = preg_replace('/<\/p>/i', "\n", $text);

        // Reemplazar <div> y </div> por salto de línea
        $text = preg_replace('/<div\b[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/div>/i', "\n", $text);

        // Reemplazar <li> y </li> con formato de lista
        $text = preg_replace('/<li\b[^>]*>/i', "\n• ", $text);
        $text = preg_replace('/<\/li>/i', "\n", $text);

        // Reemplazar <ul> y <ol> por saltos de línea
        $text = preg_replace('/<[uo]l\b[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/[uo]l>/i', "\n", $text);

        // Reemplazar <h1>-<h6> por saltos de línea con énfasis
        $text = preg_replace('/<h([1-6])\b[^>]*>/i', "\n### ", $text);
        $text = preg_replace('/<\/h[1-6]>/i', "\n", $text);

        // Reemplazar <strong>, <b> por asteriscos
        $text = preg_replace('/<(strong|b)\b[^>]*>/i', "**", $text);
        $text = preg_replace('/<\/(strong|b)>/i', "**", $text);

        // Reemplazar <em>, <i> por guiones bajos
        $text = preg_replace('/<(em|i)\b[^>]*>/i', "_", $text);
        $text = preg_replace('/<\/(em|i)>/i', "_", $text);

        // Eliminar todos los demás tags HTML restantes
        $text = strip_tags($text);

        // Decodificar entidades HTML nuevamente (por si hay algunas después de strip_tags)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limpiar saltos de línea múltiples: convertir 3+ saltos a 2 saltos
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Limpiar espacios en blanco al inicio de cada línea
        $text = preg_replace('/^[ \t]+/m', '', $text);

        // Trim general para eliminar espacios al inicio y final
        $text = trim($text);

        // Reemplazar espacios múltiples con un solo espacio
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim($text);
    }

    /**
     * Limpiar valor de texto eliminando HTML básico
     * Versión simplificada para campos que no son descripción
     *
     * @param string|null $text El texto a limpiar
     * @return string Texto limpio
     */
    protected function cleanHtmlText(?string $text): string
    {
        if (empty($text) || !is_string($text)) {
            return '';
        }

        // Reemplazar <br> por espacio
        $cleaned = preg_replace('/<br\s*\/?>/i', ' ', $text);
        $cleaned = preg_replace('/<\/br>/i', ' ', $cleaned);

        // Eliminar todos los tags HTML
        $cleaned = strip_tags($cleaned);

        // Decodificar entidades HTML
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limpiar espacios múltiples
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned);
    }
}
