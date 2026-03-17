<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
            'clean_html' => 'sometimes|boolean',
            'delete_without_location' => 'sometimes|boolean',
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
            $cleanHtml = $request->input('clean_html', false);
            $deleteWithoutLocation = $request->input('delete_without_location', false);
            $batchSize = 1000; // Procesar de 1000 en 1000

            // Log para debugging
            \Log::info('DataReorder: Iniciando análisis', [
                'dry_run' => $isDryRun,
                'clean_html' => $cleanHtml,
                'delete_without_location' => $deleteWithoutLocation
            ]);

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
            $productsWithoutLocation = 0; // Contador específico de productos sin ubicación
            $productsToDelete = []; // Productos incompletos para eliminar

            Product::with(['category', 'location'])->chunk($batchSize, function ($products) use (
                &$changes,
                &$correct,
                &$incorrectCategory,
                &$incorrectOperation,
                &$bothIncorrect,
                &$processedCount,
                &$productsWithoutLocation,
                &$productsToDelete,
                &$categoryMap,
                $totalProducts,
                &$deleteWithoutLocation
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
                        $hasLocation = $product->location !== null;

                        // CASO 0: NO tiene ubicación -> ELIMINAR (si está activado el flag)
                        if ($deleteWithoutLocation && !$hasLocation) {
                            $productsToDelete[] = [
                                'id' => $product->id,
                                'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id,
                                'name' => $product->name,
                                'reason' => 'sin_ubicacion',
                                'price_usd' => $product->price_usd,
                                'price_bob' => $product->price_bob,
                                'superficie_util' => $product->superficie_util,
                                'superficie_construida' => $product->superficie_construida,
                            ];
                            $productsWithoutLocation++; // Incrementar contador específico
                            $processedCount++;
                            continue; // Pasar al siguiente producto
                        }

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
                'to_delete' => count($productsToDelete),
                'without_location' => $productsWithoutLocation
            ]);

            // Aplicar cambios si no es dry run
            $applied = 0;
            $deleted = 0;
            $htmlCleaned = 0; // Contador de descripciones con HTML limpiado
            $productsWithHtml = []; // Lista de productos con HTML para mostrar al usuario

            // Si se activa clean_html, contar y capturar productos con HTML
            if ($cleanHtml) {
                Product::chunk($batchSize, function ($products) use (&$htmlCleaned, &$productsWithHtml) {
                    foreach ($products as $product) {
                        if ($product->description && $this->hasHtml($product->description)) {
                            $htmlCleaned++;
                            // Capturar los primeros 50 productos con HTML para mostrar
                            if (count($productsWithHtml) < 50) {
                                $productsWithHtml[] = [
                                    'id' => $product->id,
                                    'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id,
                                    'name' => $product->name,
                                    'description' => mb_substr($product->description, 0, 200) . '...', // Mostrar solo primeros 200 caracteres
                                    'description_length' => mb_strlen($product->description),
                                ];
                            }
                        }
                    }
                });
                \Log::info('DataReorder: Descripciones con HTML detectadas', ['html_with_html' => $htmlCleaned]);
            }

            if (!$isDryRun && (!empty($changes) || !empty($productsToDelete) || $cleanHtml || $productsWithoutLocation > 0)) {
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

                    // 3. Limpiar HTML de descripciones si está activado
                    if ($cleanHtml) {
                        Product::chunk($batchSize, function ($products) use (&$htmlCleaned) {
                            foreach ($products as $product) {
                                if ($product->description && $this->hasHtml($product->description)) {
                                    $cleanDescription = $this->cleanHtmlDescription($product->description);
                                    if ($cleanDescription !== $product->description) {
                                        $product->description = $cleanDescription;
                                        $product->save();
                                        $htmlCleaned++;
                                    }
                                }
                            }
                        });
                        \Log::info('DataReorder: Descripciones HTML limpiadas', ['html_cleaned' => $htmlCleaned]);
                    }

                    DB::commit();
                    \Log::info('DataReorder: Cambios aplicados', [
                        'applied' => $applied,
                        'deleted' => $deleted,
                        'deleted_without_location' => $productsWithoutLocation,
                        'html_cleaned' => $htmlCleaned
                    ]);
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
                    'products_without_location' => $productsWithoutLocation,
                    'delete_without_location_enabled' => $deleteWithoutLocation,
                    'html_cleaned' => $htmlCleaned,
                    'products_with_html' => $productsWithHtml, // Productos con HTML detectados
                    'clean_html_enabled' => $cleanHtml,
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
            $cleanDesc = $this->cleanHtmlDescription($product->description); // Limpieza avanzada de HTML
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
     * Verificar si un producto es realmente una cochera basado en sus datos
     * Una cochera real SOLO debe tener precio y metros cuadrados.
     * Si tiene datos adicionales como habitaciones, baños, ambientes o año de construcción,
     * entonces es otro tipo de inmueble (casa, dpto, etc.) que menciona cochera.
     *
     * @param mixed $product El producto a verificar
     * @return bool true si parece ser una cochera real, false si tiene datos de otra propiedad
     */
    protected function isRealCochera($product): bool
    {
        // Datos que una cochera SÍ debe tener
        $hasPrice = ($product->price_usd > 0) || ($product->price_bob > 0);
        $hasSurface = ($product->superficie_util > 0) || ($product->superficie_construida > 0);

        // Si no tiene precio o superficie, no es una cochera válida
        if (!$hasPrice || !$hasSurface) {
            return false;
        }

        // Datos que indican NO es una cochera (es otra propiedad)
        // Una cochera real no tiene estos campos con valores positivos
        $hasAmbientes = !empty($product->ambientes) && $product->ambientes > 0;
        $hasHabitaciones = !empty($product->habitaciones) && $product->habitaciones > 0;
        $hasBanos = !empty($product->banos) && $product->banos > 0;
        $hasAnoConstruccion = !empty($product->ano_construccion) && $product->ano_construccion > 0;

        // Si tiene cualquiera de estos datos adicionales, NO es una cochera
        // (es una casa, departamento, etc. que menciona cochera/parking en su descripción)
        if ($hasAmbientes || $hasHabitaciones || $hasBanos || $hasAnoConstruccion) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si un producto es realmente una habitación basado en sus datos
     * Una habitación real típicamente tiene:
     * - Exactamente 1 habitación/ambiente/dormitorio
     * - Menciona "baño compartido" en la descripción
     * - O menciona "sin cocina" o "sin cocina privada"
     *
     * Diferencia con Estudio/Monoambiente:
     * - Habitación: 1 Ambiente + Baño Compartido + Sin Cocina Privada
     * - Estudio/Monoambiente: 1 Ambiente + Baño Privado + Cocina Privada
     *
     * @param mixed $product El producto a verificar
     * @param string $description La descripción del producto
     * @return bool true si parece ser una habitación real, false si es otra propiedad
     */
    protected function isRealHabitacion($product, ?string $description = null): bool
    {
        // Verificar si menciona indicadores de habitación en la descripción
        if ($description) {
            $descLower = mb_strtolower($description, 'UTF-8');

            // Indicadores fuertes de habitación
            $habitationKeywords = [
                'baño compartido', 'baño a compartir',
                'bano compartido', 'bano a compartir',
                'baño compartida', 'baños compartidos', 'banos compartidos',
                'bathroom shared', 'shared bathroom', 'share bathroom',
                'sin cocina', 'sin cocina privada', 'sin cocina propia',
                'no cocina', 'sin cocina equipada',
                'kitchen shared', 'shared kitchen'
            ];

            foreach ($habitationKeywords as $keyword) {
                if (str_contains($descLower, $keyword)) {
                    return true;
                }
            }
        }

        // Verificar datos del producto
        $hasExactlyOneHabitacion = isset($product->habitaciones) && $product->habitaciones === 1;
        $hasExactlyOneAmbiente = isset($product->ambientes) && $product->ambientes === 1;

        // Si tiene más de 1 habitación o más de 1 ambiente, NO es habitación individual
        $hasMultipleHabitaciones = isset($product->habitaciones) && $product->habitaciones > 1;
        $hasMultipleAmbientes = isset($product->ambientes) && $product->ambientes > 1;

        if ($hasMultipleHabitaciones || $hasMultipleAmbientes) {
            return false;
        }

        // Para ser habitación, debe tener exactamente 1 habitación o ambiente
        // y NO tener características de monoambiente (cocina privada)
        if ($hasExactlyOneHabitacion || $hasExactlyOneAmbiente) {
            // Si menciona "cocina privada" o "kitchenette", es más probable que sea monoambiente
            if ($description) {
                $descLower = mb_strtolower($description, 'UTF-8');
                $monoambienteKeywords = ['cocina privada', 'kitchenette', 'kitchen priv', 'cocina propia', 'cocina completa'];
                foreach ($monoambienteKeywords as $keyword) {
                    if (str_contains($descLower, $keyword)) {
                        return false; // Es más probable que sea monoambiente
                    }
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Verificar si un producto es realmente un estudio/monoambiente basado en sus datos
     * Un monoambiente real típicamente tiene:
     * - Exactamente 1 ambiente
     * - Baño privado
     * - Cocina privada (o kitchenette)
     *
     * Diferencia con Habitación:
     * - Habitación: 1 Ambiente + Baño Compartido + Sin Cocina Privada
     * - Estudio/Monoambiente: 1 Ambiente + Baño Privado + Cocina Privada
     *
     * @param mixed $product El producto a verificar
     * @param string $description La descripción del producto
     * @return bool true si parece ser un monoambiente real, false si es otra propiedad
     */
    protected function isRealMonoambiente($product, ?string $description = null): bool
    {
        // Verificar indicadores de monoambiente en la descripción
        if ($description) {
            $descLower = mb_strtolower($description, 'UTF-8');

            // Indicadores fuertes de monoambiente
            $monoambienteKeywords = [
                'cocina privada', 'kitchenette', 'kitchen priv', 'cocina propia',
                'cocina completa', 'mini cocina', 'cocina integrada',
                'kitchen private', 'private kitchen'
            ];

            foreach ($monoambienteKeywords as $keyword) {
                if (str_contains($descLower, $keyword)) {
                    // Verificar que tenga 1 ambiente
                    $hasExactlyOneAmbiente = isset($product->ambientes) && $product->ambientes === 1;
                    $hasExactlyOneHabitacion = isset($product->habitaciones) && $product->habitaciones <= 1;
                    return $hasExactlyOneAmbiente || $hasExactlyOneHabitacion;
                }
            }
        }

        // Verificar datos del producto
        $hasExactlyOneAmbiente = isset($product->ambientes) && $product->ambientes === 1;
        $hasExactlyOneHabitacion = isset($product->habitaciones) && $product->habitaciones === 1;

        // Si tiene más de 1 ambiente o más de 1 habitación, NO es monoambiente
        $hasMultipleAmbientes = isset($product->ambientes) && $product->ambientes > 1;
        $hasMultipleHabitaciones = isset($product->habitaciones) && $product->habitaciones > 1;

        if ($hasMultipleAmbientes || $hasMultipleHabitaciones) {
            return false;
        }

        // Para ser monoambiente, debe tener exactamente 1 ambiente
        if ($hasExactlyOneAmbiente || $hasExactlyOneHabitacion) {
            // NO debe mencionar baño compartido
            if ($description) {
                $descLower = mb_strtolower($description, 'UTF-8');
                $habitationKeywords = [
                    'baño compartido', 'baño a compartir',
                    'bano compartido', 'bano a compartir',
                    'baño compartida', 'baños compartidos', 'banos compartidos'
                ];
                foreach ($habitationKeywords as $keyword) {
                    if (str_contains($descLower, $keyword)) {
                        return false; // Es más probable que sea habitación
                    }
                }
            }
            return true;
        }

        return false;
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

        // REGLA 0: HABITACIÓN vs MONOAMBIENTE (MÁXIMA PRIORIDAD)
        // Se verifican antes que todo lo demás
        // Habitación: 1 Ambiente + Baño Compartido + Sin Cocina Privada
        // Monoambiente: 1 Ambiente + Baño Privado + Cocina Privada
        $habitationKeywords = ['habitación', 'habitaciones', 'room', 'cuarto', 'cuartos', 'bedroom'];
        $monoambienteKeywords = ['estudio', 'monoambiente', 'monoambientes', 'studio', 'studio apartment', 'single room'];
        // NOTA: 'dormitorio' NO está aquí porque es genérico para deptos/casas también

        $hasHabitationKeyword = false;
        foreach ($habitationKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasHabitationKeyword = true;
                break;
            }
        }

        $hasMonoambienteKeyword = false;
        foreach ($monoambienteKeywords as $keyword) {
            if ($findKeyword($nameLower, $keyword)) {
                $hasMonoambienteKeyword = true;
                break;
            }
        }

        // Obtener descripción limpia para validaciones adicionales
        $cleanDescription = null;
        if ($product->description) {
            $cleanDescription = $this->cleanHtmlDescription($product->description); // Limpieza avanzada de HTML
        }

        // Si detecta monoambiente por palabra clave, verificar que realmente sea monoambiente
        if ($hasMonoambienteKeyword) {
            if ($this->isRealMonoambiente($product, $cleanDescription)) {
                return 'estudio/monoambiente';
            }
            // Si no es monoambiente, pero tiene 1 ambiente, podría ser habitación
            if ($this->isRealHabitacion($product, $cleanDescription)) {
                return 'habitación';
            }
            // Sino, continuar buscando otras categorías
        }

        // Si detecta habitación por palabra clave, verificar que realmente sea habitación
        if ($hasHabitationKeyword) {
            if ($this->isRealHabitacion($product, $cleanDescription)) {
                return 'habitación';
            }
            // Si no es habitación, pero tiene 1 ambiente, podría ser monoambiente
            if ($this->isRealMonoambiente($product, $cleanDescription)) {
                return 'estudio/monoambiente';
            }
            // IMPORTANTE: Si originalmente era departamento y tiene 1 ambiente + cocina privada,
            // seguir siendo departamento (no cambiarlo a habitación)
            // Ejemplo: "Departamento de 1 Dormitorio en Venta" con cocina privada
            $hasExactlyOneHabitacion = isset($product->habitaciones) && $product->habitaciones === 1;
            $hasExactlyOneAmbiente = isset($product->ambientes) && $product->ambientes === 1;
            $currentCategoryName = $product->category ? mb_strtolower($product->category->category_name, 'UTF-8') : null;

            // Si el inmueble tiene 1 habitación/ambiente y menciona cocina privada,
            // Y estaba previamente clasificado como departamento, mantenerlo como departamento
            if (($hasExactlyOneHabitacion || $hasExactlyOneAmbiente) && $currentCategoryName === 'departamento') {
                return 'departamento'; // Mantener como departamento monoambiente
            }
            // Sino, continuar buscando otras categorías
        }

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

        // Si SOLO menciona condominio (sin casa, Y sin monoambiente, departamento o habitación), es condominio
        // IMPORTANTE: Monoambiente tiene prioridad sobre condominio
        if ($hasCondominioKeyword && !$hasMonoambienteKeyword && !$hasHabitationKeyword && !$hasDeptoKeyword) {
            return 'condominio';
        }

        // ============================================================
        // BÚSQUEDA NORMAL DE LAS DEMÁS CATEGORÍAS
        // ============================================================

        // 1. Buscar en el nombre (PRIORIDAD 1 - El más importante)
        foreach ($this->keywordMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if ($findKeyword($nameLower, $keyword)) {
                    // VALIDACIÓN ESPECIAL PARA HABITACIÓN
                    // Habitación: 1 Ambiente + Baño Compartido + Sin Cocina Privada
                    if ($category === 'habitación' && !$this->isRealHabitacion($product, $cleanDescription)) {
                        // Si detecta habitación pero no cumple los requisitos,
                        // verificar si podría ser monoambiente
                        if ($this->isRealMonoambiente($product, $cleanDescription)) {
                            return 'estudio/monoambiente';
                        }
                        continue;
                    }

                    // VALIDACIÓN ESPECIAL PARA ESTUDIO/MONOAMBIENTE
                    // Monoambiente: 1 Ambiente + Baño Privado + Cocina Privada
                    if ($category === 'estudio/monoambiente' && !$this->isRealMonoambiente($product, $cleanDescription)) {
                        // Si detecta monoambiente pero no cumple los requisitos,
                        // verificar si podría ser habitación
                        if ($this->isRealHabitacion($product, $cleanDescription)) {
                            return 'habitación';
                        }
                        continue;
                    }

                    // VALIDACIÓN ESPECIAL PARA COCHERA
                    // Solo clasificar como cochera si realmente tiene SOLO precio y metros
                    // Si tiene datos adicionales (habitaciones, baños, etc.), es otra propiedad
                    if ($category === 'cochera' && !$this->isRealCochera($product)) {
                        // No es cochera, continuar buscando otras categorías
                        continue;
                    }
                    return $category;
                }
            }
        }

        // 2. Buscar en la descripción (PRIORIDAD 2)
        if ($product->description) {
            $cleanDesc = $this->cleanHtmlDescription($product->description); // Limpieza avanzada de HTML
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
                        // VALIDACIÓN ESPECIAL PARA HABITACIÓN
                        // Habitación: 1 Ambiente + Baño Compartido + Sin Cocina Privada
                        if ($category === 'habitación' && !$this->isRealHabitacion($product, $cleanDesc)) {
                            // Si detecta habitación pero no cumple los requisitos,
                            // verificar si podría ser monoambiente
                            if ($this->isRealMonoambiente($product, $cleanDesc)) {
                                return 'estudio/monoambiente';
                            }
                            continue;
                        }

                        // VALIDACIÓN ESPECIAL PARA ESTUDIO/MONOAMBIENTE
                        // Monoambiente: 1 Ambiente + Baño Privado + Cocina Privada
                        if ($category === 'estudio/monoambiente' && !$this->isRealMonoambiente($product, $cleanDesc)) {
                            // Si detecta monoambiente pero no cumple los requisitos,
                            // verificar si podría ser habitación
                            if ($this->isRealHabitacion($product, $cleanDesc)) {
                                return 'habitación';
                            }
                            continue;
                        }

                        // VALIDACIÓN ESPECIAL PARA COCHERA
                        // Solo clasificar como cochera si realmente tiene SOLO precio y metros
                        // Si tiene datos adicionales (habitaciones, baños, etc.), es otra propiedad
                        if ($category === 'cochera' && !$this->isRealCochera($product)) {
                            // No es cochera, continuar buscando otras categorías
                            continue;
                        }
                        return $category;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Verificar si un texto contiene HTML
     *
     * @param string|null $text El texto a verificar
     * @return bool true si contiene HTML, false en caso contrario
     */
    protected function hasHtml(?string $text): bool
    {
        if (empty($text) || !is_string($text)) {
            return false;
        }

        // Verificar si contiene tags HTML básicos
        return preg_match('/<[^>]+>/', $text) === 1;
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

    /**
     * Verificar si coordenadas están fuera de Bolivia
     * Bolivia está aproximadamente entre:
     * - Latitud: -22° a -9°
     * - Longitud: -69° a -57°
     *
     * @param float|null $lat Latitud a verificar
     * @param float|null $lng Longitud a verificar
     * @return bool true si está fuera de Bolivia, false si está dentro o sin datos
     */
    protected function isOutsideBolivia(?float $lat, ?float $lng): bool
    {
        // Si no hay coordenadas, no se puede determinar
        if ($lat === null || $lng === null) {
            return false;
        }

        // Límites aproximados de Bolivia (con un margen de seguridad)
        $latMin = -22.5;
        $latMax = -9.0;
        $lngMin = -69.5;
        $lngMax = -57.0;

        return ($lat < $latMin || $lat > $latMax || $lng < $lngMin || $lng > $lngMax);
    }

    /**
     * Exportar lista de productos para descargar como CSV
     *
     * @param array $products Lista de productos a exportar
     * @return string CSV formateado
     */
    protected function exportToCsv(array $products): string
    {
        $csv = fopen('php://temp', 'w');
        fputcsv($csv, ['ID', 'Código', 'Nombre', 'Categoría', 'Operación', 'Precio USD', 'Precio BOB', 'Latitud', 'Longitud', 'Razón']);

        foreach ($products as $product) {
            $categoryName = $product['category'] ?? 'Sin categoría';
            $operation = $product['operation'] ?? 'Sin operación';

            fputcsv($csv, [
                $product['id'],
                $product['codigo'],
                mb_substr($product['name'], 0, 100),
                is_string($categoryName) ? $categoryName : '',
                is_string($operation) ? $operation : '',
                $product['price_usd'] ?? '',
                $product['price_bob'] ?? '',
                $product['lat'] ?? '',
                $product['lng'] ?? '',
                $product['reason'] ?? '',
            ]);
        }

        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    /**
     * Analizar productos fuera de Bolivia
     * Detecta productos con ubicación fuera del país y permite exportar antes de eliminar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeOutsideBolivia(Request $request)
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
            $batchSize = 1000;

            \Log::info('DataReorder: Iniciando análisis de productos fuera de Bolivia', ['dry_run' => $isDryRun]);

            // Contar productos con ubicación
            $totalWithLocation = Product::whereHas('location', function ($query) {
                $query->where('is_active', true);
            })->count();

            \Log::info('DataReorder: Productos con ubicación', ['total' => $totalWithLocation]);

            if ($totalWithLocation === 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_with_location' => 0,
                        'outside_bolivia' => 0,
                        'products_outside' => [],
                        'dry_run' => $isDryRun,
                    ]
                ]);
            }

            // Analizar productos en lotes (chunks) para optimizar memoria
            $productsOutside = [];
            $processedCount = 0;

            Product::with(['category', 'location'])
                ->whereHas('location', function ($query) {
                    $query->where('is_active', true);
                })
                ->chunk($batchSize, function ($products) use (
                    &$productsOutside,
                    &$processedCount,
                    $isDryRun
                ) {
                    foreach ($products as $product) {
                try {
                    $isOutside = $this->isOutsideBolivia(
                        $product->location->latitude ?? null,
                        $product->location->longitude ?? null
                    );

                    if ($isOutside) {
                        $productsOutside[] = [
                            'id' => $product->id,
                            'codigo' => $product->codigo_inmueble ?? $product->sku ?? $product->id,
                            'name' => $product->name,
                            'category' => $product->category ? $product->category->category_name : null,
                            'operation' => $product->operacion,
                            'price_usd' => $product->price_usd,
                            'price_bob' => $product->price_bob,
                            'lat' => $product->location->latitude ?? null,
                            'lng' => $product->location->longitude ?? null,
                            'reason' => 'ubicación_fuera_bolivia',
                        ];
                        }

                        $processedCount++;
                    } catch (\Exception $e) {
                        \Log::warning('DataReorder: Error verificando ubicación de producto', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            \Log::info('DataReorder: Análisis completado', [
                'outside_bolivia' => count($productsOutside),
                'total_analyzed' => $processedCount
            ]);

            // Generar CSV para exportar
            $csvData = !empty($productsOutside) ? $this->exportToCsv($productsOutside) : null;

            // Eliminar productos si no es dry run
            $deleted = 0;
            if (!$isDryRun && !empty($productsOutside)) {
                DB::beginTransaction();
                try {
                    $idsToDelete = array_column($productsOutside, 'id');
                    $deleted = Product::whereIn('id', $idsToDelete)->delete();
                    \Log::info('DataReorder: Productos fuera de Bolivia eliminados', ['deleted' => $deleted]);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('DataReorder: Error eliminando productos fuera de Bolivia', [
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_with_location' => $processedCount,
                    'outside_bolivia' => count($productsOutside),
                    'products_outside' => array_slice($productsOutside, 0, 50), // Primeros 50 para mostrar
                    'products_outside_count' => count($productsOutside),
                    'deleted' => $deleted,
                    'csv_export' => $csvData,
                    'dry_run' => $isDryRun,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DataReorder: Error en análisis de productos fuera de Bolivia', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al analizar productos fuera de Bolivia: ' . $e->getMessage()
            ], 500);
        }
    }
}
