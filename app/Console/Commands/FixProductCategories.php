<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class FixProductCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:fix-categories
                            {--dry-run : Solo mostrar cambios sin ejecutarlos}
                            {--batch-size=1000 : Número de registros a procesar por lote}
                            {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige automáticamente las categorías de los productos basándose en palabras clave del nombre';

    /**
     * Mapa de palabras clave para detectar categorías
     */
    protected $keywordMap = [
        'apartamento' => ['apartamento', 'apartamentos', 'depto', 'departamento', 'departamentos', 'piso', 'pisos', 'flat', 'flats'],
        'casa' => ['casa', 'casas', 'chalet', 'chalets', 'villa', 'villas', 'residencia', 'residencias'],
        'terreno' => ['terreno', 'terrenos', 'lote', 'lotes', 'solar', 'solares', 'parcela', 'parcelas'],
        'local comercial' => ['local', 'locales', 'tienda', 'tiendas', 'comercio', 'comercios', 'local comercial'],
        'oficina' => ['oficina', 'oficinas', 'consultorio', 'consultorios'],
        'galpón' => ['galpón', 'galpones', 'depósito', 'depositos', 'deposito', 'nave', 'naves', 'almacén', 'almacenes'],
        'finca' => ['finca', 'fincas', 'hacienda', 'haciendas', 'ranch', 'estancia', 'estancias'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Corrección Automática de Categorías de Productos ===');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('MODO SIMULACIÓN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Obtener categorías disponibles
        $categories = ProductCategory::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        if ($categories->isEmpty()) {
            $this->error('No se encontraron categorías en la base de datos.');
            return 1;
        }

        $this->info('Categorías encontradas en la base de datos:');
        $this->table(['ID', 'Nombre'], $categories->map(fn($c) => [$c->id, $c->category_name]));
        $this->newLine();

        // Crear mapa de ID de categorías
        $categoryMap = [];
        $categoryLowerMap = [];

        foreach ($categories as $category) {
            $categoryLower = mb_strtolower($category->category_name, 'UTF-8');
            $categoryMap[$categoryLower] = $category->id;
            $categoryLowerMap[$category->id] = $categoryLower;
        }

        $this->info("Analizando productos en busca de inconsistencias...");
        $this->newLine();

        // Contador total de productos
        $totalProducts = Product::count();
        $this->info("Total de productos en la base de datos: {$totalProducts}");
        $this->newLine();

        if ($totalProducts === 0) {
            $this->warn('No hay productos para procesar.');
            return 0;
        }

        // Variables para seguimiento
        $changes = [];
        $processedCount = 0;
        $batchCount = 0;

        // Procesar en lotes
        Product::with('category')->chunk($batchSize, function ($products) use (&$changes, &$processedCount, &$batchCount, $categoryMap, $categoryLowerMap, $isDryRun) {
            $batchCount++;
            $this->info("Procesando lote {$batchCount} (productos " . ($processedCount + 1) . " - " . ($processedCount + $products->count()) . ")...");

            $bar = $this->output->createProgressBar($products->count());
            $bar->start();

            foreach ($products as $product) {
                $productName = mb_strtolower($product->name, 'UTF-8');
                $currentCategoryId = $product->category_id;

                // Si no tiene categoría asignada, intentar asignar una
                if (is_null($currentCategoryId)) {
                    foreach ($this->keywordMap as $category => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (str_contains($productName, $keyword)) {
                                $targetCategoryId = $categoryMap[$category] ?? null;

                                if ($targetCategoryId) {
                                    $changes[] = [
                                        'id' => $product->id,
                                        'name' => $product->name,
                                        'from_category' => 'SIN CATEGORÍA',
                                        'to_category' => $category,
                                        'from_id' => null,
                                        'to_id' => $targetCategoryId,
                                    ];
                                }
                                break 2;
                            }
                        }
                    }
                } else {
                    // Verificar si la categoría actual coincide con las palabras clave
                    $currentCategoryName = $categoryLowerMap[$currentCategoryId] ?? '';

                    // Buscar la categoría correcta basada en palabras clave
                    $detectedCategory = null;

                    foreach ($this->keywordMap as $category => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (str_contains($productName, $keyword)) {
                                $detectedCategory = $category;
                                break 2;
                            }
                        }
                    }

                    // Si se detectó una categoría diferente a la actual
                    if ($detectedCategory && $detectedCategory !== $currentCategoryName) {
                        $targetCategoryId = $categoryMap[$detectedCategory] ?? null;

                        if ($targetCategoryId && $targetCategoryId !== $currentCategoryId) {
                            $changes[] = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'from_category' => $currentCategoryName,
                                'to_category' => $detectedCategory,
                                'from_id' => $currentCategoryId,
                                'to_id' => $targetCategoryId,
                            ];
                        }
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $processedCount += $products->count();
        });

        $this->newLine();
        $this->info('=== Análisis Completado ===');
        $this->newLine();

        if (empty($changes)) {
            $this->info('✓ No se encontraron inconsistencias en las categorías.');
            return 0;
        }

        $this->warn("Se encontraron " . count($changes) . " productos con categorías incorrectas:");
        $this->newLine();

        // Agrupar cambios por tipo de corrección
        $groupedChanges = [];
        foreach ($changes as $change) {
            $key = $change['from_category'] . ' → ' . $change['to_category'];
            if (!isset($groupedChanges[$key])) {
                $groupedChanges[$key] = [];
            }
            $groupedChanges[$key][] = $change;
        }

        // Mostrar resumen agrupado
        $this->info('Resumen de correcciones por tipo:');
        foreach ($groupedChanges as $key => $items) {
            $this->line("  • {$key}: " . count($items) . " productos");
        }
        $this->newLine();

        // Mostrar primeros 10 ejemplos de cambios
        $this->info('Primeros 10 ejemplos de cambios:');
        $this->table(
            ['ID', 'Nombre', 'Categoría Actual', 'Categoría Correcta'],
            array_slice(array_map(fn($c) => [
                $c['id'],
                strlen($c['name']) > 50 ? substr($c['name'], 0, 47) . '...' : $c['name'],
                $c['from_category'],
                $c['to_category']
            ], $changes), 0, 10)
        );

        if (count($changes) > 10) {
            $this->info("... y " . (count($changes) - 10) . " cambios más.");
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn('Modo simulación: No se realizaron cambios.');
            $this->info('Para ejecutar los cambios, ejecuta: php artisan products:fix-categories');
            return 0;
        }

        // Confirmar cambios
        if (!$force && !$this->confirm('¿Deseas aplicar estos cambios?', true)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        // Aplicar cambios
        $this->newLine();
        $this->info('Aplicando cambios...');

        $bar = $this->output->createProgressBar(count($changes));
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($changes as $change) {
                Product::where('id', $change['id'])
                    ->update(['category_id' => $change['to_id']]);
                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();

            $this->newLine();
            $this->info('✓ ¡Corrección completada exitosamente!');
            $this->info("Total de productos actualizados: " . count($changes));

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine();

            $this->error('✗ Error al aplicar los cambios: ' . $e->getMessage());
            $this->info('Los cambios fueron revertidos.');
            return 1;
        }
    }
}
