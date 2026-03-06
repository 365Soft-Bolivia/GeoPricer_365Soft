<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DataReorderController extends Controller
{
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
        'venta' => ['venta', 'vende', 'en venta', 'se vende', 'venta directa', 'enquiler'],
        'alquiler' => ['alquiler', 'alquila', 'renta', 'arriendo', 'en alquiler', 'alquilar', 'alquiler temporal'],
        'anticretico' => ['anticretico', 'anticrético', 'anticré'],
    ];

    /**
     * Mostrar vista principal de reordenamiento
     */
    public function index()
    {
        return inertia('DataReorder');
    }

    /**
     * Analizar y reordenar productos existentes en BD
     */
    public function analyze(Request $request)
    {
        // Validar usuario autenticado y con rol admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'dry_run' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Parámetros inválidos: ' . $validator->errors()->first()
            ], 422);
        }

        // Aumentar tiempo de ejecución y memoria
        set_time_limit(600); // 10 minutos
        ini_set('memory_limit', '512M');

        try {
            $isDryRun = $request->input('dry_run', true);
            $batchSize = 1000; // Procesar de 1000 en 1000

            // Log para debugging
            \Log::info('DataReorder: Iniciando análisis', ['dry_run' => $isDryRun]);

            // Contar total de productos
            $totalProducts = Product::count();

            \Log::info('DataReorder: Total productos', ['total' => $totalProducts]);

            if ($totalProducts === 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total' => 0,
                        'correct' => 0,
                        'incorrect_category' => 0,
                        'incorrect_operation' => 0,
                        'both_incorrect' => 0,
                        'changes' => [],
                        'changes_count' => 0,
                        'applied' => 0,
                        'dry_run' => $isDryRun,
                    ]
                ]);
            }

            // Obtener categorías actuales
            $categories = ProductCategory::select('id', 'category_name')
                ->orderBy('category_name')
                ->get();

            // Crear mapa de categorías
            $categoryMap = [];
            foreach ($categories as $category) {
                $categoryLower = mb_strtolower($category->category_name, 'UTF-8');
                $categoryMap[$categoryLower] = $category->id;
            }

            \Log::info('DataReorder: Categorías cargadas', ['count' => count($categoryMap)]);

            // Analizar productos en lotes (chunks)
            $changes = [];
            $correct = 0;
            $incorrectCategory = 0;
            $incorrectOperation = 0;
            $bothIncorrect = 0;
            $processedCount = 0;
            $productsToDelete = []; // Productos incompletos para eliminar

            Product::with(['category'])->chunk($batchSize, function ($products) use (
                &$changes,
                &$correct,
                &$incorrectCategory,
                &$incorrectOperation,
                &$bothIncorrect,
                &$processedCount,
                &$productsToDelete,
                &$categoryMap,
                $totalProducts
            ) {
                foreach ($products as $product) {
                    try {
                        $nameLower = mb_strtolower($product->name, 'UTF-8');

                        // ============================================================
                        // LIMPIEZA DE DATOS INCOMPLETOS
                        // Eliminar productos que pueden afectar el ACM
                        // ============================================================

                        $hasPrice = ($product->price_usd > 0) || ($product->price_bob > 0);
                        $hasSurface = ($product->superficie_util > 0) || ($product->superficie_construida > 0);

                        // CASO 1: Tiene precio pero NO tiene superficie -> ELIMINAR
                        if ($hasPrice && !$hasSurface) {
                            $productsToDelete[] = [
                                'id' => $product->id,
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id,
                                'name' => $product->name,
                                'reason' => 'precio_sin_superficie',
                                'price_usd' => $product->price_usd,
                                'price_bob' => $product->price_bob,
                                'superficie_util' => $product->superficie_util,
                                'superficie_construida' => $product->superficie_construida,
                            ];
                            $processedCount++;
                            continue; // Pasar al siguiente producto
                        }

                        // CASO 2: Tiene superficie pero NO tiene precio -> ELIMINAR
                        if ($hasSurface && !$hasPrice) {
                            $productsToDelete[] = [
                                'id' => $product->id,
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id,
                                'name' => $product->name,
                                'reason' => 'superficie_sin_precio',
                                'price_usd' => $product->price_usd,
                                'price_bob' => $product->price_bob,
                                'superficie_util' => $product->superficie_util,
                                'superficie_construida' => $product->superficie_construida,
                            ];
                            $processedCount++;
                            continue; // Pasar al siguiente producto
                        }

                        // ============================================================
                        // DETECCIÓN DE CATEGORÍA Y OPERACIÓN
                        // Solo si pasó la limpieza de datos incompletos
                        // ============================================================

                        // Detectar qué debería ser
                        $detectedCategory = $this->detectCategory($nameLower, $product);
                        $detectedOperation = $this->detectOperation($nameLower, $product);

                        // Obtener valores actuales
                        $currentCategoryName = $product->category ? mb_strtolower($product->category->category_name, 'UTF-8') : null;
                        $currentOperation = $product->operacion;

                        // Detectar cambios necesarios
                        $categoryNeedsChange = ($detectedCategory && $detectedCategory !== $currentCategoryName);
                        $operationNeedsChange = ($detectedOperation !== $currentOperation);

                        if ($categoryNeedsChange && $operationNeedsChange) {
                            $bothIncorrect++;
                            $changes[] = [
                                'id' => $product->id, // ID para actualizar en BD
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id, // Código para mostrar al usuario
                                'name' => $product->name,
                                'type' => 'both',
                                'current_category' => $currentCategoryName ?? 'SIN CATEGORÍA',
                                'correct_category' => $detectedCategory,
                                'current_operation' => $currentOperation ?? 'SIN OPERACIÓN',
                                'correct_operation' => $detectedOperation,
                                'price_usd' => $product->price_usd,
                                'price_bob' => $product->price_bob,
                            ];
                        } elseif ($categoryNeedsChange) {
                            $incorrectCategory++;
                            $changes[] = [
                                'id' => $product->id, // ID para actualizar en BD
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id, // Código para mostrar al usuario
                                'name' => $product->name,
                                'type' => 'category',
                                'current_category' => $currentCategoryName ?? 'SIN CATEGORÍA',
                                'correct_category' => $detectedCategory,
                                'price_usd' => $product->price_usd,
                                'price_bob' => $product->price_bob,
                            ];
                        } elseif ($operationNeedsChange) {
                            $incorrectOperation++;
                            $changes[] = [
                                'id' => $product->id, // ID para actualizar en BD
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id, // Código para mostrar al usuario
                                'name' => $product->name,
                                'type' => 'operation',
                                'current_operation' => $currentOperation ?? 'SIN OPERACIÓN',
                                'correct_operation' => $detectedOperation,
                            ];
                        } else {
                            $correct++;
                        }

                        $processedCount++;
                    } catch (\Exception $e) {
                        \Log::warning('DataReorder: Error procesando producto', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continuar con el siguiente producto
                    }
                }
            });

            \Log::info('DataReorder: Análisis completado', [
                'correct' => $correct,
                'changes' => count($changes),
                'to_delete' => count($productsToDelete)
            ]);

            // Aplicar cambios si no es dry run
            $applied = 0;
            $deleted = 0;
            if (!$isDryRun && (!empty($changes) || !empty($productsToDelete))) {
                DB::beginTransaction();
                try {
                    // 1. Actualizar productos con cambios de categoría/operación
                    foreach ($changes as $change) {
                        $updateData = [];

                        if ($change['type'] === 'both' || $change['type'] === 'category') {
                            $newCategoryId = $categoryMap[$change['correct_category']] ?? null;
                            if ($newCategoryId) {
                                $updateData['category_id'] = $newCategoryId;
                            }
                        }

                        if ($change['type'] === 'both' || $change['type'] === 'operation') {
                            $updateData['operacion'] = $change['correct_operation'];
                        }

                        if (!empty($updateData)) {
                            Product::where('id', $change['id'])->update($updateData);
                            $applied++;
                        }
                    }

                    // 2. Eliminar productos incompletos que afectan el ACM
                    if (!empty($productsToDelete)) {
                        $idsToDelete = array_column($productsToDelete, 'id');
                        $deleted = Product::whereIn('id', $idsToDelete)->delete();
                        \Log::info('DataReorder: Productos incompletos eliminados', ['deleted' => $deleted]);
                    }

                    DB::commit();
                    \Log::info('DataReorder: Cambios aplicados', ['applied' => $applied, 'deleted' => $deleted]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('DataReorder: Error aplicando cambios', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalProducts,
                    'correct' => $correct,
                    'incorrect_category' => $incorrectCategory,
                    'incorrect_operation' => $incorrectOperation,
                    'both_incorrect' => $bothIncorrect,
                    'changes' => array_slice($changes, 0, 50), // Primeros 50 cambios
                    'changes_count' => count($changes),
                    'applied' => $applied,
                    'deleted_count' => count($productsToDelete),
                    'products_to_delete' => array_slice($productsToDelete, 0, 50), // Primeros 50 a eliminar
                    'deleted' => $deleted,
                    'dry_run' => $isDryRun,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DataReorder: Error general', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al analizar productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detectar operación basada en el nombre, descripción u otros campos
     * Búsqueda mejorada con word boundaries para mayor precisión
     */
    protected function detectOperation(string $nameLower, $product): string
    {
        // Función helper para buscar palabra completa
        $findKeyword = function($text, $keyword) {
            // Usar word boundaries para buscar palabras completas
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/iu';
            return preg_match($pattern, $text);
        };

        // 1. Primero buscar en operación actual
        if ($product->operacion) {
            $operationLower = mb_strtolower($product->operacion, 'UTF-8');

            foreach ($this->operationKeywords as $operation => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($findKeyword($operationLower, $keyword)) {
                        return $operation;
                    }
                }
            }
        }

        // 2. Buscar en el nombre (PRIORIDAD MÁXIMA)
        foreach ($this->operationKeywords as $operation => $keywords) {
            foreach ($keywords as $keyword) {
                if ($findKeyword($nameLower, $keyword)) {
                    return $operation;
                }
            }
        }

        // 3. Buscar en la descripción (limpiar HTML primero)
        if ($product->description) {
            $cleanDesc = strip_tags($product->description); // Eliminar HTML
            $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
            $descLower = mb_strtolower($cleanDesc, 'UTF-8');

            foreach ($this->operationKeywords as $operation => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($findKeyword($descLower, $keyword)) {
                        return $operation;
                    }
                }
            }
        }

        // Por defecto: venta
        return 'venta';
    }

    /**
     * Detectar categoría basada en palabras clave del nombre, descripción u otros campos
     * Búsqueda mejorada con word boundaries para evitar falsos positivos
     * Incluye lógica inteligente de prioridad entre categorías similares
     */
    protected function detectCategory(string $nameLower, $product): ?string
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
        // ============================================================

        // REGLA 1: DEPARTAMENTO vs EDIFICIO
        // Departamento tiene PRIORIDAD sobre edificio
        $deptoKeywords = ['departamento', 'depto', 'apartamento', 'piso', 'flat', 'dept', 'apartment'];
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
            return 'departamento';
        }

        // Si SOLO menciona edificio (sin departamento), es edificio
        if ($hasEdificioKeyword) {
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
            return 'casa';
        }

        // Si SOLO menciona condominio (sin casa), es condominio
        if ($hasCondominioKeyword) {
            return 'condominio';
        }

        // ============================================================
        // BÚSQUEDA NORMAL DE LAS DEMÁS CATEGORÍAS
        // ============================================================

        // 1. Buscar en el nombre (PRIORIDAD 1 - El más importante)
        foreach ($this->keywordMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($findKeyword($nameLower, $keyword)) {
                    return $category;
                }
            }
        }

        // 2. Buscar en la descripción (PRIORIDAD 2)
        if ($product->description) {
            $cleanDesc = strip_tags($product->description); // Eliminar HTML
            $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decodificar entidades HTML
            $descLower = mb_strtolower($cleanDesc, 'UTF-8');

            // Aplicar misma lógica de prioridad en descripción
            // Departamento优先idad
            foreach ($deptoKeywords as $keyword) {
                if ($findKeyword($descLower, $keyword)) {
                    return 'departamento';
                }
            }

            // Casa优先acidad sobre condominio
            foreach ($casaKeywords as $keyword) {
                if ($findKeyword($descLower, $keyword)) {
                    return 'casa';
                }
            }

            foreach ($this->keywordMap as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($findKeyword($descLower, $keyword)) {
                        return $category;
                    }
                }
            }
        }

        return null;
    }
}
