<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\JsonImportParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DataImportController extends Controller
{
    protected JsonImportParser $parser;

    // Tasa de cambio aproximada USD a BOB (Bolivia)
    const USD_TO_BOB_RATE = 6.95;

    public function __construct(JsonImportParser $parser)
    {
        $this->parser = $parser;
    }
    /**
     * Mapa de palabras clave para detectar categorías
     * 15 CATEGORÍAS FINALES OFICIALES
     *
     * IMPORTANTE: El orden define la prioridad.
     * Las categorías ESPECIALES van primero para evitar falsos positivos.
     * Ejemplo: "penthouse" contiene "house", pero debe detectarse como penthouse, no como casa.
     */
    protected $keywordMap = [
        // ============================================================
        // CATEGORÍAS ESPECIALES (PRIORIDAD ALTA)
        // Se verifican PRIMERO para evitar confusiones
        // ============================================================
        'penthouse' => ['penthouse', 'ph ', 'pent house', 'penthaus'],
        'dúplex' => ['dúplex', 'duplex', 'duplex house', 'duplex home'],
        'estudio/monoambiente' => ['estudio', 'monoambiente', 'monoambientes', 'studio', 'studio apartment', 'single room'],
        'condominio' => ['condominio', 'condominio horizontal', 'condominio privado', 'condominium', 'condo'],
        'edificio' => ['edificio', 'edificio de apartamentos', 'torre', 'torres', 'building', 'tower'],

        // ============================================================
        // CATEGORÍAS PRINCIPALES (PRIORIDAD MEDIA)
        // Se verifican DESPUÉS de las especiales
        // ============================================================
        'casa' => [
            'casa', 'casas', 'chalet', 'chalets', 'villa', 'villas', 'residencia', 'residencias',
            'casa de lujo', 'casa en condominio', 'casa con comercial', 'home', 'house'
        ],
        'departamento' => [
            'apartamento', 'apartamentos', 'depto', 'departamento', 'departamentos', 'piso', 'pisos',
            'flat', 'flats', 'apartment', 'dept'
        ],
        'terreno' => [
            'terreno', 'terrenos', 'lote', 'lotes', 'solar', 'solares', 'parcela', 'parcelas',
            'lote o terreno', 'terreno comercial', 'land', 'lot'
        ],
        'local comercial' => [
            'local', 'locales', 'tienda', 'tiendas', 'comercio', 'comercios', 'local comercial',
            'comercial/negocio', 'negocio especial', 'negocio', 'store', 'shop', 'commercial'
        ],
        'oficina' => [
            'oficina', 'oficinas', 'consultorio', 'consultorios', 'suit', 'ofic', 'office'
        ],
        'galpón' => [
            'galpón', 'galpones', 'depósito', 'depositos', 'deposito', 'nave', 'naves', 'almacén', 'almacenes',
            'local industrial o galpón', 'local industrial', 'nave industrial', 'warehouse', 'warehouse industrial'
        ],
        'quinta' => [
            'finca', 'fincas', 'hacienda', 'haciendas', 'quinta', 'quinta o campo', 'quinta propiedad agricola',
            'ranch', 'estancia', 'estancias', 'agrícola', 'ganadero', 'propiedad agrícola/ganadera',
            'propiedad agricola/ganadera', 'campo', 'farm', 'ranch'
        ],
        'cochera' => [
            'parqueo', 'garaje', 'garaje o cochera', 'garaje/baulera', 'cochera', 'baulera',
            'estacionamiento', 'parking', 'garage', 'parking space'
        ],
        'habitación' => [
            'habitación', 'habitaciones', 'room', 'cuarto', 'cuartos', 'dormitorio', 'bedroom'
        ],
        'otros' => [
            'otra', 'otro', 'otros', 'no clasificado', 'sin clasificar', 'varios', 'other', 'misc'
        ],
    ];

    /**
     * Mapa de palabras clave para detectar operación
     * 3 TIPOS DE OPERACIÓN EN LA BD
     */
    protected $operationKeywords = [
        'venta' => ['venta', 'vende', 'en venta', 'se vende', 'venta directa', 'enquiler', 'se enquiler', 'a la venta', 'en venta', 'a vender', 'se vende'],
        'alquiler' => ['alquiler', 'alquila', 'renta', 'arriendo', 'en alquiler', 'alquilar', 'alquiler temporal', 'en renta', 'en arriendo', 'para alquilar', 'para renta', 'rentarse', 'arrendar', 'en arrendamiento', 'alquilarse'],
        'anticretico' => ['anticretico', 'anticrético', 'anticré', 'en anticretico', 'en anticrético', 'anticretico_', '_anticretico', 'anticretico_', 'con anticretico', 'anticresis', 'anticretico_', '_anticrético'],
    ];

    /**
     * Mapeo de campos del JSON a campos de la BD
     */
    protected $fieldMapping = [
        // Nombres comunes en JSON -> Campo en BD
        'price' => ['price_usd', 'price_bob'],
        'precio' => ['price_usd', 'price_bob'],
        'price_usd' => ['price_usd'],
        'price_bob' => ['price_bob'],
        'usd' => ['price_usd'],
        'bob' => ['price_bob'],
        'surface' => ['superficie_util', 'superficie_construida'],
        'superficie' => ['superficie_util', 'superficie_construida'],
        'area' => ['superficie_util', 'superficie_construida'],
        'área' => ['superficie_util', 'superficie_construida'],
        'metros' => ['superficie_util', 'superficie_construida'],
        'm2' => ['superficie_util', 'superficie_construida'],
        'bedrooms' => ['habitaciones'],
        'habitaciones' => ['habitaciones'],
        'recamaras' => ['habitaciones'], // Century 21
        'rooms' => ['ambientes'],
        'ambientes' => ['ambientes'],
        'bathrooms' => ['banos'],
        'baños' => ['banos'],
        'banos' => ['banos'],
        'baths' => ['banos'],
        'parking' => ['cocheras'],
        'cocheras' => ['cocheras'],
        'estacionamientos' => ['cocheras'], // Century 21
        'garage' => ['cocheras'],
        'year' => ['ano_construccion'],
        'ano' => ['ano_construccion'],
        'año' => ['ano_construccion'],
        'year_built' => ['ano_construccion'],
        'description' => ['description'],
        'descripción' => ['description'],
        'desc' => ['description'],
        'direccion' => ['description'],
        'address' => ['description'],
        'calle' => ['description'],
        'ciudad' => ['description'],
        'city' => ['description'],
        'zona' => ['description'],
        'zone' => ['description'],
        'ubicacion' => ['description'],
        'location' => ['description'],
        'operation' => ['operacion'],
        'operacion' => ['operacion'],
        'tipo_operacion' => ['operacion'],
    ];

    /**
     * Mostrar vista principal de importación
     */
    public function index()
    {
        return inertia('DataImport');
    }

    /**
     * Procesar archivo JSON subido
     */
    public function process(Request $request)
    {
        // Log de entrada
        \Log::debug('DataImport process iniciado', [
            'method' => $request->method(),
            'has_file' => $request->hasFile('json_file'),
            'csrf_token_sent' => !empty($request->header('X-CSRF-TOKEN')),
        ]);

        $validator = Validator::make($request->all(), [
            'json_file' => 'required|file|mimes:json',
            'dry_run' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validación fallida en DataImport', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('json_file');
            $isDryRun = $request->input('dry_run', false);

            \Log::debug('Archivo recibido', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'is_dry_run' => $isDryRun,
            ]);

            // Leer contenido del JSON
            $jsonContent = file_get_contents($file->getPathname());
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido: ' . json_last_error_msg());
            }

            if (empty($data)) {
                throw new \Exception('El archivo JSON está vacío');
            }

            // Procesar los datos
            $result = $this->processData($data, $isDryRun);

            \Log::info('DataImport completado exitosamente', [
                'processed' => $result['processed'],
                'total' => $result['total'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo procesado correctamente',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en DataImport', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo',
                'error' => $e->getMessage(),
                'debug' => env('APP_DEBUG') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Procesar datos del JSON y clasificarlos
     */
    protected function processData(array $data, bool $isDryRun): array
    {
        // Obtener categorías de la BD
        $categories = ProductCategory::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        // Crear mapa de categorías
        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryLower = mb_strtolower($category->category_name, 'UTF-8');
            $categoryMap[$categoryLower] = $category->id;
        }

        // Detectar formato del JSON
        $format = $this->parser->detectFormat($data);

        // Extraer items según el formato detectado
        $items = $this->parser->extractItems($data, $format);

        // Procesar cada item
        $processed = [];
        $errors = [];
        $skipped = [];

        foreach ($items as $item) {
            try {
                // Normalizar el item al formato estándar
                $normalizedItem = $this->parser->normalizeItem($item, $format);

                // Procesar el item normalizado
                $result = $this->processItem($normalizedItem, $categoryMap, $isDryRun);
                if ($result['status'] === 'success') {
                    $processed[] = $result;
                } elseif ($result['status'] === 'skipped') {
                    $skipped[] = $result;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'item' => $item,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Calcular estadísticas de categorías y operaciones
        $categoriesStats = [];
        $operationsStats = [];
        $pricesCorrected = 0;
        $pricesCalculated = 0;

        foreach ($processed as $item) {
            // Contar categorías
            $catName = $item['category_name'] ?? 'Desconocida';
            if (!isset($categoriesStats[$catName])) {
                $categoriesStats[$catName] = 0;
            }
            $categoriesStats[$catName]++;

            // Contar operaciones
            $opName = $item['operation'] ?? 'Desconocida';
            if (!isset($operationsStats[$opName])) {
                $operationsStats[$opName] = 0;
            }
            $operationsStats[$opName]++;

            // Contar precios corregidos
            if (isset($item['price_corrected']) && $item['price_corrected']) {
                $pricesCorrected++;
            }

            // Contar precios calculados
            if (isset($item['price_calculated']) && $item['price_calculated']) {
                $pricesCalculated++;
            }
        }

        return [
            'total' => count($items),
            'processed' => count($processed),
            'skipped' => count($skipped),
            'errors' => count($errors),
            'dry_run' => $isDryRun,
            'items' => array_slice($processed, 0, 20),
            'errors_list' => array_slice($errors, 0, 10),
            'skipped_list' => array_slice($skipped, 0, 10),
            'categories_stats' => $categoriesStats,
            'operations_stats' => $operationsStats,
            'prices_corrected' => $pricesCorrected,
            'prices_calculated' => $pricesCalculated,
        ];
    }

    /**
     * Procesar un item individual (puede venir en CUALQUIER formato)
     */
    protected function processItem(array $item, array $categoryMap, bool $isDryRun): array
    {
        // Extraer datos básicos - Ahora el item ya viene normalizado
        $name = $item['name'] ?? 'Sin nombre';
        $nameLower = mb_strtolower($name, 'UTF-8');
        $externalId = $item['id'] ?? uniqid();
        $slug = $item['slug'] ?? null;

        // Detectar categoría (inteligente: si no detecta, usa "otros")
        $detectedCategory = $this->detectCategory($nameLower, $item);

        // Si no se detectó categoría, usar "otros" por defecto
        if (!$detectedCategory) {
            $detectedCategory = 'otros';
        }

        // Buscar ID de categoría en la BD
        $categoryId = $categoryMap[$detectedCategory] ?? null;
        if (!$categoryId) {
            return [
                'status' => 'skipped',
                'id' => $externalId,
                'name' => $name,
                'reason' => "Categoría detectada '{$detectedCategory}' no existe en la base de datos"
            ];
        }

        // Detectar operación (inteligente: si no detecta, intenta de nuevo)
        $operation = $this->detectOperation($nameLower, $item);

        // LOG DE DEPURACIÓN: Guardar qué detectó
        if (!$operation) {
            \Log::info("DataImport: No se detectó operación para '{$name}'", [
                'name' => $name,
                'item' => $item
            ]);
        } else {
            \Log::info("DataImport: Operación detectada para '{$name}'", [
                'operation' => $operation,
                'name' => $name
            ]);
        }

        // Si no detectó operación (null), intentar una búsqueda más agresiva
        if (!$operation) {
            // Buscar operation en descripción u otros campos de texto
            $operation = $this->detectOperationInText($item);

            if ($operation) {
                \Log::info("DataImport: Operación detectada en búsqueda avanzada para '{$name}'", [
                    'operation' => $operation,
                    'name' => $name
                ]);
            }
        }

        // Verificar si ya existe
        $existing = Product::where('codigo_inmueble', 'EXT-' . $externalId)->first();
        if ($existing) {
            return [
                'status' => 'skipped',
                'id' => $externalId,
                'name' => $name,
                'reason' => 'Ya existe un producto con este código externo'
            ];
        }

        // Si después de todo, la categoría es "otros" Y no hay operación,
        // entonces omitir el producto (probablemente no es una propiedad)
        if ($detectedCategory === 'otros' && !$operation) {
            return [
                'status' => 'skipped',
                'id' => $externalId,
                'name' => $name,
                'reason' => 'No se pudo detectar ni categoría ni operación. Probablemente no es una propiedad inmobiliaria.'
            ];
        }

        // Extraer TODOS los datos del item
        $productData = $this->extractProductData($item, $name, $categoryId, $operation, $externalId, $slug);

        // Crear producto (si no es dry run)
        $productId = null;
        $locationCreated = false;
        if (!$isDryRun) {
            DB::beginTransaction();
            try {
                $product = Product::create($productData);
                $productId = $product->id;

                // Crear ubicación si hay datos de ubicación
                $locationData = $this->extractLocationData($item, $productId);
                if ($locationData !== null) {
                    \App\Models\ProductLocation::create($locationData);
                    $locationCreated = true;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return [
            'status' => 'success',
            'id' => $externalId,
            'name' => $name,
            'detected_category' => $detectedCategory,
            'detected_operation' => $operation,
            'category_id' => $categoryId,
            'product_id' => $productId,
            'location_created' => $locationCreated,
            'data_saved' => $productData,
            'category_name' => ucfirst($detectedCategory), // Para mostrar en el modal
            'operation' => ucfirst($operation), // Para mostrar en el modal
            'price_corrected' => $productData['price_corrected'] ?? false, // Si se corrigió el precio
            'price_calculated' => $productData['price_calculated'] ?? false, // Si se calculó el precio
            'price_correction_note' => $productData['price_correction_note'] ?? null, // Nota de la corrección
            'price_usd' => $productData['price_usd'] ?? null,
            'price_bob' => $productData['price_bob'] ?? null,
        ];
    }

    /**
     * Extraer TODOS los datos posibles del item
     */
    protected function extractProductData(array $item, string $name, int $categoryId, ?string $operation, string $externalId, ?string $slug): array
    {
        $data = [
            'name' => $name,
            'category_id' => $categoryId,
            'codigo_inmueble' => 'EXT-' . $externalId,
            'slug' => $slug,
            'operacion' => $operation,
            'is_public' => true, // Por defecto público
            'status' => 'published',
        ];

        $extraInfo = []; // Campos extra para la descripción

        // === PROCESAR PRECIOS (ya normalizados por el servicio) ===
        if (isset($item['price_usd']) && is_numeric($item['price_usd'])) {
            $data['price_usd'] = floatval($item['price_usd']);
        }
        if (isset($item['price_bob']) && is_numeric($item['price_bob'])) {
            $data['price_bob'] = floatval($item['price_bob']);
        }

        // === PROCESAR SUPERFICIE (ya normalizada por el servicio) ===
        if (isset($item['superficie_util']) && is_numeric($item['superficie_util'])) {
            $data['superficie_util'] = floatval($item['superficie_util']);
        }
        if (isset($item['superficie_construida']) && is_numeric($item['superficie_construida'])) {
            $data['superficie_construida'] = floatval($item['superficie_construida']);
        }

        // === PROCESAR HABITACIONES, BAÑOS, COCHERAS ===
        if (isset($item['habitaciones']) && is_numeric($item['habitaciones'])) {
            $data['habitaciones'] = intval($item['habitaciones']);
        }
        if (isset($item['banos']) && is_numeric($item['banos'])) {
            $data['banos'] = intval($item['banos']);
        }
        if (isset($item['cocheras']) && is_numeric($item['cocheras'])) {
            $data['cocheras'] = intval($item['cocheras']);
        }

        // === PROCESAR EL RESTO DE CAMPOS ===
        foreach ($item as $fieldName => $value) {
            // Saltar valores nulos o vacíos
            if ($value === null || $value === '') {
                continue;
            }

            // Saltar valores que son arrays u objetos (no se pueden convertir a string fácilmente)
            if (is_array($value) || is_object($value)) {
                continue;
            }

            // Saltar campos ya procesados o internos
            $skipFields = [
                'id', 'name', 'slug', 'category', 'operation',
                'price_usd', 'price_bob', 'superficie_util', 'superficie_construida',
                'habitaciones', 'banos', 'cocheras',
                'created_at', 'updated_at', 'type_property_id', 'area_id',
                'lat', 'lng', 'latitude', 'longitude', 'latitud', 'longitud', 'address'
            ];

            if (in_array($fieldName, $skipFields)) {
                continue;
            }

            // Asegurarse de que el fieldName es un string
            if (!is_string($fieldName)) {
                continue;
            }

            $fieldNameLower = mb_strtolower($fieldName, 'UTF-8');

            // Buscar si este campo corresponde a algún campo de la BD
            $mapped = false;

            // Buscar en el mapeo
            foreach ($this->fieldMapping as $jsonField => $dbFields) {
                if (str_contains($fieldNameLower, $jsonField)) {
                    foreach ($dbFields as $dbField) {
                        // Asignar al primer campo que no tenga valor
                        if (!isset($data[$dbField]) || $data[$dbField] === null) {
                            $data[$dbField] = $this->sanitizeValue($dbField, $value);
                            $mapped = true;
                            break;
                        }
                    }
                    if ($mapped) break;
                }
            }

            // Si no se mapeó, agregar a info extra (excluyendo campos no relevantes)
            $excludedFields = [
                'fotos', 'estatus', 'moneda', 'telefono', 'whatsapp', 'email',
                'asesor', 'afiliado', 'url', 'meta_tags', 'etiquetas',
                'mantenimiento', 'precioformat', 'descripcion', 'description',
                'ubicacion', 'location', 'zone', 'city', 'ciudad', 'zona',
                'recamaras', 'estacionamientos', 'ambientes', 'amb',
                'precios', 'm2t', 'm2c', 'encabezado', 'tipopropiedad', 'tipooperacion',
                'listing', 'agent', 'office', 'user', 'default_imagen', 'status_listing',
                'transaction_type', 'subtype_property', 'area', 'quality_score', 'mlsid',
                'date_of_listing', 'price_type', 'img', 'finances', 'grouped_ids',
                'ismapfeatured', 'showaddress', 'country_id', 'owner', 'has_whatsapp',
                'inmofull', 'inmolink', 'inmopropslink', 'type', 'particular', 'subsidiaries',
                'logo', 'inmo', 'masked_phone', 'whatsapp_phone', 'image_url', 'image_name',
                'office_hours', 'office_id', 'agent_id', 'user_id'
            ];

            if (!$mapped && !in_array($fieldNameLower, $excludedFields)) {
                $extraInfo[] = ucfirst($fieldName) . ': ' . $this->formatValue($value);
            }
        }

        // Agregar campos extras a la descripción
        if (!empty($extraInfo)) {
            $existingDescription = $data['description'] ?? '';
            $extraText = implode("\n", $extraInfo);
            $data['description'] = trim($existingDescription . "\n\n" . $extraText);
        }

        // Si no hay descripción, crear una básica
        if (empty($data['description'])) {
            $operationText = $operation ?? 'operación no especificada';
            $data['description'] = "Propiedad en {$operationText}: {$name}";
        }

        // Validar y corregir coherencia de precios
        $data = $this->validateAndCorrectPrices($data);

        return $data;
    }

    /**
     * Validar y corregir precios incoherentes
     * Si los precios USD y BOB no tienen coherencia, recalcular uno basado en el otro
     */
    protected function validateAndCorrectPrices(array $data): array
    {
        $hasUsd = isset($data['price_usd']) && $data['price_usd'] > 0;
        $hasBob = isset($data['price_bob']) && $data['price_bob'] > 0;

        // Si tiene ambos precios
        if ($hasUsd && $hasBob) {
            $usd = $data['price_usd'];
            $bob = $data['price_bob'];

            // Calcular tasa implícita
            $implicitRate = $bob / $usd;

            // Verificar si es coherente (permitiendo ±20% de variación)
            $expectedBob = $usd * self::USD_TO_BOB_RATE;
            $lowerBound = $expectedBob * 0.8;  // 80% del esperado
            $upperBound = $expectedBob * 1.2;  // 120% del esperado

            // Si el BOB está fuera del rango razonable, corregir
            if ($bob < $lowerBound || $bob > $upperBound) {
                $data['price_bob'] = round($expectedBob, 2);
                $data['price_corrected'] = true;
                $data['price_correction_note'] = "Precio BOB corregido de {$bob} a {$data['price_bob']} (tasa: " . self::USD_TO_BOB_RATE . ")";
            }
        }
        // Si solo tiene USD, calcular BOB
        elseif ($hasUsd && !$hasBob) {
            $data['price_bob'] = round($data['price_usd'] * self::USD_TO_BOB_RATE, 2);
            $data['price_calculated'] = true;
            $data['price_correction_note'] = "Precio BOB calculado desde USD (tasa: " . self::USD_TO_BOB_RATE . ")";
        }
        // Si solo tiene BOB, calcular USD
        elseif (!$hasUsd && $hasBob) {
            $data['price_usd'] = round($data['price_bob'] / self::USD_TO_BOB_RATE, 2);
            $data['price_calculated'] = true;
            $data['price_correction_note'] = "Precio USD calculado desde BOB (tasa: " . self::USD_TO_BOB_RATE . ")";
        }

        return $data;
    }

    /**
     * Sanitizar valor según el tipo de campo
     */
    protected function sanitizeValue(string $field, $value)
    {
        // Campos numéricos
        if (in_array($field, ['price_usd', 'price_bob', 'superficie_util', 'superficie_construida', 'comision'])) {
            return is_numeric($value) ? floatval($value) : null;
        }

        // Campos enteros
        if (in_array($field, ['ambientes', 'habitaciones', 'banos', 'cocheras', 'ano_construccion'])) {
            return is_numeric($value) ? intval($value) : null;
        }

        // Campos de texto
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Formatear valor para mostrar en descripción
     * Maneja arrays multidimensionales, objetos y valores escalares
     */
    protected function formatValue($value): string
    {
        // Si es null, retornar string vacío
        if ($value === null) {
            return '';
        }

        // Si es booleano, convertir a texto
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        // Si es un array
        if (is_array($value)) {
            // Verificar si es un array multidimensional o de objetos
            foreach ($value as $item) {
                if (is_array($item) || is_object($item)) {
                    // Array complejo, retornar count de elementos
                    return '(' . count($value) . ' elementos)';
                }
            }

            // Array simple de valores escalares
            $filtered = array_filter($value, function($v) {
                return $v !== null && $v !== '';
            });
            return implode(', ', $filtered);
        }

        // Si es un objeto
        if (is_object($value)) {
            // Intentar convertir a JSON legible
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                // Limitar longitud
                if (strlen($json) > 100) {
                    return substr($json, 0, 100) . '...';
                }
                return $json;
            }
            return '(Objeto)';
        }

        // Para cualquier otro tipo, convertir a string
        return (string) $value;
    }

    /**
     * Extraer datos de ubicación del item para crear registro en product_locations
     * Ahora el item ya viene normalizado con lat, lng, address
     */
    protected function extractLocationData(array $item, int $productId): ?array
    {
        // Buscar latitud (ya normalizado por el servicio)
        $latitude = null;
        $latFields = ['lat', 'latitude'];
        foreach ($latFields as $field) {
            if (isset($item[$field]) && is_numeric($item[$field])) {
                $latitude = floatval($item[$field]);
                break;
            }
        }

        // Buscar longitud (ya normalizado por el servicio)
        $longitude = null;
        $lngFields = ['lng', 'lon', 'longitude'];
        foreach ($lngFields as $field) {
            if (isset($item[$field]) && is_numeric($item[$field])) {
                $longitude = floatval($item[$field]);
                break;
            }
        }

        // Buscar dirección (ya normalizado por el servicio)
        $address = $item['address'] ?? null;

        // Si hay latitud y longitud, crear ubicación
        if ($latitude !== null && $longitude !== null) {
            return [
                'product_id' => $productId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'is_active' => true, // Siempre activo por defecto
            ];
        }

        // Si solo hay dirección (sin coordenadas), no crear ubicación
        // porque el mapa necesita latitud y longitud
        return null;
    }

    /**
     * Detectar operación basada en el nombre, descripción u otros campos de texto
     * Búsqueda mejorada con word boundaries para mayor precisión
     */
    protected function detectOperation(string $nameLower, array $item): ?string
    {
        // Función helper para buscar palabra completa
        $findKeyword = function($text, $keyword) {
            // Para frases multi-palabra, usar búsqueda normal (sin word boundaries)
            if (str_contains($keyword, ' ')) {
                return str_contains($text, $keyword);
            }
            // Para palabras simples, usar word boundaries
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/iu';
            return preg_match($pattern, $text);
        };

        // ============================================================
        // PRIORIDAD DE BÚSQUEDA: Anticretico > Alquiler > Venta
        // IMPORTANTE: El NOMBRE tiene prioridad absoluta sobre TODO
        // ============================================================

        // 1. PRIMERO buscar en el nombre (PRIORIDAD MÁXIMA)
        // El nombre suele ser más descriptivo y preciso que los campos estructurados
        // Buscar anticretico primero
        foreach ($this->operationKeywords['anticretico'] as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                \Log::info("DataImport: Anticretico detectado en nombre", [
                    'name' => $nameLower,
                    'keyword' => $keyword
                ]);
                return 'anticretico';
            }
        }
        // Luego alquiler
        foreach ($this->operationKeywords['alquiler'] as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                \Log::info("DataImport: Alquiler detectado en nombre", [
                    'name' => $nameLower,
                    'keyword' => $keyword
                ]);
                return 'alquiler';
            }
        }
        // Finalmente venta
        foreach ($this->operationKeywords['venta'] as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                \Log::info("DataImport: Venta detectado en nombre", [
                    'name' => $nameLower,
                    'keyword' => $keyword
                ]);
                return 'venta';
            }
        }

        // ============================================================
        // MAPEO EXPLÍCITO PARA CENTURY 21 Y OTRAS APIs
        // Solo se usa si NO se encontró en el nombre
        // ============================================================
        $c21OperationMapping = [
            'venta' => 'venta',
            'vende' => 'venta',
            'en venta' => 'venta',
            'renta' => 'alquiler',
            'alquiler' => 'alquiler',
            'arriendo' => 'alquiler',
            'en alquiler' => 'alquiler',
            'anticretico' => 'anticretico',
            'anticrético' => 'anticretico',
            'anticré' => 'anticretico',
        ];

        // 2. Si no encontró en el nombre, buscar en campos específicos del item
        // Primero intentar mapeo directo para APIs conocidas (C21, REMAX, InfoCasas)
        if (isset($item['tipoOperacion']) || isset($item['tipo_operacion'])) {
            $tipoOp = $item['tipoOperacion'] ?? $item['tipo_operacion'] ?? '';
            $tipoOpLower = mb_strtolower(trim($tipoOp), 'UTF-8');

            if (isset($c21OperationMapping[$tipoOpLower])) {
                \Log::info("DataImport: Operación detectada via mapeo C21", [
                    'tipoOp' => $tipoOpLower,
                    'mapped' => $c21OperationMapping[$tipoOpLower]
                ]);
                return $c21OperationMapping[$tipoOpLower];
            }
        }

        // 3. Si no hay mapeo directo, buscar con palabras clave en campos específicos
        $operationField = $item['operation'] ?? $item['operacion'] ?? $item['tipo_operacion'] ?? $item['tipoOperacion'] ?? $item['type'] ?? null;

        if ($operationField) {
            $operationLower = mb_strtolower($operationField, 'UTF-8');

            // Buscar anticretico primero (prioridad alta)
            foreach ($this->operationKeywords['anticretico'] as $keyword) {
                if ($findKeyword($operationLower, $keyword)) {
                    \Log::info("DataImport: Anticretico detectado en campo específico", [
                        'field' => $operationLower,
                        'keyword' => $keyword
                    ]);
                    return 'anticretico';
                }
            }
            // Luego alquiler
            foreach ($this->operationKeywords['alquiler'] as $keyword) {
                if ($findKeyword($operationLower, $keyword)) {
                    \Log::info("DataImport: Alquiler detectado en campo específico", [
                        'field' => $operationLower,
                        'keyword' => $keyword
                    ]);
                    return 'alquiler';
                }
            }
            // Finalmente venta
            foreach ($this->operationKeywords['venta'] as $keyword) {
                if ($findKeyword($operationLower, $keyword)) {
                    \Log::info("DataImport: Venta detectado en campo específico", [
                        'field' => $operationLower,
                        'keyword' => $keyword
                    ]);
                    return 'venta';
                }
            }
        }

        // 3. Buscar en la descripción y otros campos de texto (limpiar HTML)
        $textFields = ['description', 'desc', 'descripción', 'descripcion', 'details', 'detalle', 'detalles', 'text', 'texto'];

        foreach ($textFields as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $cleanDesc = strip_tags($item[$field]); // Eliminar HTML
                $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
                $fieldLower = mb_strtolower($cleanDesc, 'UTF-8');

                // Buscar con el mismo orden de prioridad
                // Anticretico primero
                foreach ($this->operationKeywords['anticretico'] as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'anticretico';
                    }
                }
                // Luego alquiler
                foreach ($this->operationKeywords['alquiler'] as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'alquiler';
                    }
                }
                // Finalmente venta
                foreach ($this->operationKeywords['venta'] as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'venta';
                    }
                }
            }
        }

        // 4. Buscar en TODOS los campos string del item (último recurso)
        foreach ($item as $fieldName => $value) {
            // Ignorar campos que ya verificamos
            if (in_array($fieldName, ['id', 'slug', 'created_at', 'updated_at', 'operation', 'operacion', 'tipo_operacion', 'type', 'name', 'title', 'nombre'])) {
                continue;
            }

            if (is_string($value)) {
                $cleanValue = strip_tags($value); // Eliminar HTML
                $cleanValue = html_entity_decode($cleanValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
                $valueLower = mb_strtolower($cleanValue, 'UTF-8');

                // Buscar con el mismo orden de prioridad
                // Anticretico primero
                foreach ($this->operationKeywords['anticretico'] as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'anticretico';
                    }
                }
                // Luego alquiler
                foreach ($this->operationKeywords['alquiler'] as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'alquiler';
                    }
                }
                // Finalmente venta
                foreach ($this->operationKeywords['venta'] as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'venta';
                    }
                }
            }
        }

        // Por defecto: null (para que intente la búsqueda avanzada)
        return null;
    }

    /**
     * Detectar operación de forma más agresiva en todos los campos de texto
     * Se usa cuando la detección normal falla
     */
    protected function detectOperationInText(array $item): ?string
    {
        $findKeyword = function($text, $keyword) {
            // Para frases multi-palabra, usar búsqueda normal
            if (str_contains($keyword, ' ')) {
                return str_contains($text, $keyword);
            }
            // Para palabras simples, usar word boundaries
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/iu';
            return preg_match($pattern, $text);
        };

        // Palabras clave por operación (más exhaustivas)
        $ventaKeywords = [
            'venta', 'vende', 'en venta', 'se vende', 'venta directa', 'vender',
            'en venta', 'a la venta', 'a vender', 'oferta', 'enoferta', 'oprtunidad',
            'traspaso', 'traspasar'
        ];

        $alquilerKeywords = [
            'alquiler', 'alquila', 'renta', 'arriendo', 'en alquiler', 'alquilar',
            'en renta', 'rentar', 'arrendar', 'arriendo', 'rentarse', 'alquiler temporal',
            'temporal', 'mensual', 'por dia', 'por días', 'por dia', 'día', 'diario',
            'por mes', 'por meses', 'estadía', 'estadia'
        ];

        $anticreticoKeywords = [
            'anticretico', 'anticrético', 'anticré', 'anticretico sin interes',
            'anticretico con interes', 'en anticretico', 'en anticrético'
        ];

        // Concatenar TODOS los campos de texto del item
        $fullText = '';

        // 1. Agregar nombre
        if (isset($item['name'])) {
            $fullText .= ' ' . $item['name'];
        }

        // 2. Agregar descripción
        $descFields = ['description', 'desc', 'descripción', 'descripcion', 'details', 'detalle', 'detalles', 'text', 'texto'];
        foreach ($descFields as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $cleanDesc = strip_tags($item[$field]);
                $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fullText .= ' ' . $cleanDesc;
            }
        }

        // 3. Agregar todos los demás campos string
        foreach ($item as $key => $value) {
            if (is_string($value) && !in_array($key, $descFields) && !in_array($key, ['name', 'slug', 'id'])) {
                $cleanValue = strip_tags($value);
                $cleanValue = html_entity_decode($cleanValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fullText .= ' ' . $cleanValue;
            }
        }

        $fullTextLower = mb_strtolower($fullText, 'UTF-8');

        // PRIORIDAD: Anticretico > Alquiler > Venta
        // Buscar primero anticrético (prioridad alta - palabras más distintivas)
        foreach ($anticreticoKeywords as $keyword) {
            if ($findKeyword($fullTextLower, $keyword)) {
                return 'anticretico';
            }
        }

        // Luego alquiler
        foreach ($alquilerKeywords as $keyword) {
            if ($findKeyword($fullTextLower, $keyword)) {
                return 'alquiler';
            }
        }

        // Finalmente venta
        foreach ($ventaKeywords as $keyword) {
            if ($findKeyword($fullTextLower, $keyword)) {
                return 'venta';
            }
        }

        // Si aún no se detectó, retornar null (para que se omita el producto)
        return null;
    }

    /**
     * Detectar categoría basada en palabras clave del nombre, descripción u otros campos
     * Búsqueda mejorada con word boundaries para evitar falsos positivos
     * Incluye lógica inteligente de prioridad entre categorías similares
     */
    protected function detectCategory(string $nameLower, array $item = []): ?string
    {
        // Función helper para buscar palabra completa
        $findKeyword = function($text, $keyword) {
            // Para frases multi-palabra, usar búsqueda normal
            if (str_contains($keyword, ' ')) {
                return str_contains($text, $keyword);
            }
            // Para palabras simples, usar word boundaries
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/iu';
            return preg_match($pattern, $text);
        };

        // ============================================================
        // REGLAS ESPECIALES DE PRIORIDAD INTELIGENTE
        // EL NOMBRE TIENE PRIORIDAD ABSOLUTA SOBRE MAPEOS
        // ============================================================

        // REGLA 0: MONOAMBIENTE vs DEPARTAMENTO
        // Monoambiente tiene PRIORIDAD ABSOLUTA sobre departamento
        $monoambienteKeywords = ['monoambiente', 'monoambientes', 'studio', 'studio apartment', 'single room', 'mono ambiente', 'mono-ambiente'];
        $deptoKeywords = ['departamento', 'depto', 'apartamento', 'piso', 'flat', 'dept', 'apartment'];

        $hasMonoambienteKeyword = false;
        foreach ($monoambienteKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasMonoambienteKeyword = true;
                \Log::info("DataImport: Monoambiente detectado en nombre", [
                    'name' => $nameLower,
                    'keyword' => $keyword
                ]);
                break;
            }
        }

        // Si menciona monoambiente, es monoambiente (PRIORIDAD MÁXIMA)
        if ($hasMonoambienteKeyword) {
            return 'estudio/monoambiente';
        }

        // REGLA 1: DEPARTAMENTO vs EDIFICIO
        // Departamento tiene PRIORIDAD sobre edificio
        $edificioKeywords = ['edificio', 'torre', 'building', 'tower'];

        $hasDeptoKeyword = false;
        foreach ($deptoKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasDeptoKeyword = true;
                break;
            }
        }

        $hasEdificioKeyword = false;
        foreach ($edificioKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasEdificioKeyword = true;
                break;
            }
        }

        // Si menciona departamento, es departamento (aunque también mencione edificio)
        if ($hasDeptoKeyword) {
            \Log::info("DataImport: Departamento detectado en nombre", [
                'name' => $nameLower
            ]);
            return 'departamento';
        }

        // Si SOLO menciona edificio (sin departamento), es edificio
        if ($hasEdificioKeyword) {
            \Log::info("DataImport: Edificio detectado en nombre", [
                'name' => $nameLower
            ]);
            return 'edificio';
        }

        // REGLA 2: CASA vs CONDOMINIO
        // Casa tiene PRIORIDAD sobre condominio
        $casaKeywords = ['casa', 'casas', 'chalet', 'chalets', 'villa', 'villas', 'residencia', 'residencias', 'home', 'house'];
        $condominioKeywords = ['condominio', 'condominio horizontal', 'condominio privado', 'condominium', 'condo'];

        $hasCasaKeyword = false;
        foreach ($casaKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasCasaKeyword = true;
                break;
            }
        }

        $hasCondominioKeyword = false;
        foreach ($condominioKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasCondominioKeyword = true;
                break;
            }
        }

        // Si menciona casa, es casa (aunque también mencione condominio)
        if ($hasCasaKeyword) {
            \Log::info("DataImport: Casa detectada en nombre", [
                'name' => $nameLower
            ]);
            return 'casa';
        }

        // Si SOLO menciona condominio (sin casa), es condominio
        if ($hasCondominioKeyword) {
            \Log::info("DataImport: Condominio detectado en nombre", [
                'name' => $nameLower
            ]);
            return 'condominio';
        }

        // ============================================================
        // BÚSQUEDA NORMAL DEL RESTO DE CATEGORÍAS
        // ============================================================

        // 1. Buscar en el nombre (PRIORIDAD 1 - El más importante)
        foreach ($this->keywordMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($findKeyword($nameLower, $keyword)) {
                    \Log::info("DataImport: Categoría detectada en nombre", [
                        'category' => $category,
                        'keyword' => $keyword,
                        'name' => $nameLower
                    ]);
                    return $category;
                }
            }
        }

        // ============================================================
        // MAPEO EXPLÍCITO PARA CENTURY 21 Y OTRAS APIs
        // Solo se usa si NO se encontró en el nombre
        // ============================================================
        $c21CategoryMapping = [
            'terreno' => 'terreno',
            'casa' => 'casa',
            'departamento' => 'departamento',
            'penthouse' => 'penthouse',
            'duplex' => 'dúplex',
            'dúplex' => 'dúplex',
            'local comercial' => 'local comercial',
            'oficina' => 'oficina',
            'galpon' => 'galpón',
            'galpón' => 'galpón',
            'quinta' => 'quinta',
            'hacienda' => 'quinta',
            'finca' => 'quinta',
            'estudio' => 'estudio/monoambiente',
            'monoambiente' => 'estudio/monoambiente',
            'cochera' => 'cochera',
            'garage' => 'cochera',
            'parqueo' => 'cochera',
            'habitacion' => 'habitación',
            'habitación' => 'habitación',
            'edificio' => 'edificio',
            'condominio' => 'condominio',
            'propiedad agricola' => 'otros',
            'otro' => 'otros',
        ];

        // 2. Si tiene tipoPropiedad (Century 21), usar mapeo directo
        if (isset($item['tipoPropiedad']) || isset($item['tipo_propiedad'])) {
            $tipoProp = $item['tipoPropiedad'] ?? $item['tipo_propiedad'] ?? '';
            $tipoPropLower = mb_strtolower(trim($tipoProp), 'UTF-8');

            if (isset($c21CategoryMapping[$tipoPropLower])) {
                \Log::info("DataImport: Categoría detectada via mapeo C21", [
                    'tipoProp' => $tipoPropLower,
                    'mapped' => $c21CategoryMapping[$tipoPropLower]
                ]);
                return $c21CategoryMapping[$tipoPropLower];
            }
        }

        // ============================================================
        // MAPEO EXPLÍCITO PARA INFOCASAS
        // ============================================================
        $infocasasCategoryMapping = [
            'otro' => 'otros',
            'otros' => 'otros',
            'casa' => 'casa',
            'departamento' => 'departamento',
            'apartamento' => 'departamento',
            'penthouse' => 'penthouse',
            'terreno' => 'terreno',
            'local comercial' => 'local comercial',
            'oficina' => 'oficina',
            'galpón' => 'galpón',
            'galpon' => 'galpón',
            'quinta' => 'quinta',
            'hacienda' => 'quinta',
            'finca' => 'quinta',
            'estudio' => 'estudio/monoambiente',
            'monoambiente' => 'estudio/monoambiente',
            'cochera' => 'cochera',
            'garage' => 'cochera',
            'parqueo' => 'cochera',
            'habitación' => 'habitación',
            'edificio' => 'edificio',
            'condominio' => 'condominio',
        ];

        // 3. Si tiene category (InfoCasas ya normalizado), usar mapeo directo
        if (isset($item['category'])) {
            $categoryLower = mb_strtolower(trim($item['category']), 'UTF-8');

            if (isset($infocasasCategoryMapping[$categoryLower])) {
                \Log::info("DataImport: Categoría detectada via mapeo InfoCasas", [
                    'category' => $categoryLower,
                    'mapped' => $infocasasCategoryMapping[$categoryLower]
                ]);
                return $infocasasCategoryMapping[$categoryLower];
            }
        }

        // ============================================================
        // BÚSQUEDA EN CAMPOS DE TEXTO (descripciones, etc.)
        // ============================================================

        // 2. Buscar en campos específicos del item (category, tipo, etc.)
        $categoryField = $item['category'] ?? $item['categoria'] ?? $item['type'] ?? $item['tipo'] ?? $item['property_type'] ?? $item['tipo_propiedad'] ?? $item['tipoPropiedad'] ?? null;

        if ($categoryField) {
            $categoryLower = mb_strtolower($categoryField, 'UTF-8');

            foreach ($this->keywordMap as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($findKeyword($categoryLower, $keyword)) {
                        return $category;
                    }
                }
            }
        }

        // 2. Buscar en el nombre (PRIORIDAD 1 - El más importante)
        foreach ($this->keywordMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($findKeyword($nameLower, $keyword)) {
                    return $category;
                }
            }
        }

        // 3. Buscar en la descripción y otros campos de texto (limpiar HTML)
        $textFields = ['description', 'desc', 'descripción', 'descripcion', 'details', 'detalle', 'detalles', 'text', 'texto'];

        foreach ($textFields as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $cleanDesc = strip_tags($item[$field]); // Eliminar HTML
                $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
                $fieldLower = mb_strtolower($cleanDesc, 'UTF-8');

                // Aplicar misma lógica de prioridad en descripción

                // REGLA 0: Monoambiente tiene PRIORIDAD ABSOLUTA
                foreach ($monoambienteKeywords as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'estudio/monoambiente';
                    }
                }

                // REGLA 1: Departamento prioridad sobre edificio
                foreach ($deptoKeywords as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'departamento';
                    }
                }

                // REGLA 2: Casa prioridad sobre condominio
                foreach ($casaKeywords as $keyword) {
                    if ($findKeyword($fieldLower, $keyword)) {
                        return 'casa';
                    }
                }

                foreach ($this->keywordMap as $category => $keywords) {
                    foreach ($keywords as $keyword) {
                        if ($findKeyword($fieldLower, $keyword)) {
                            return $category;
                        }
                    }
                }
            }
        }

        // 4. Buscar en TODOS los campos string del item (último recurso)
        foreach ($item as $fieldName => $value) {
            // Ignorar campos que ya verificamos
            if (in_array($fieldName, ['id', 'slug', 'created_at', 'updated_at', 'category', 'categoria', 'type', 'tipo', 'property_type', 'tipo_propiedad', 'name', 'title', 'nombre', 'operation', 'operacion'])) {
                continue;
            }

            if (is_string($value) && strlen($value) > 3) { // Solo campos con más de 3 caracteres
                $cleanValue = strip_tags($value); // Eliminar HTML
                $cleanValue = html_entity_decode($cleanValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
                $valueLower = mb_strtolower($cleanValue, 'UTF-8');

                // Aplicar prioridades incluso en campos de último recurso

                // REGLA 0: Monoambiente tiene PRIORIDAD ABSOLUTA
                foreach ($monoambienteKeywords as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'estudio/monoambiente';
                    }
                }

                // REGLA 1: Departamento prioridad sobre edificio
                foreach ($deptoKeywords as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'departamento';
                    }
                }

                // REGLA 2: Casa prioridad sobre condominio
                foreach ($casaKeywords as $keyword) {
                    if ($findKeyword($valueLower, $keyword)) {
                        return 'casa';
                    }
                }

                foreach ($this->keywordMap as $category => $keywords) {
                    foreach ($keywords as $keyword) {
                        if ($findKeyword($valueLower, $keyword)) {
                            return $category;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Obtener categorías disponibles
     */
    public function categories()
    {
        $categories = ProductCategory::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
