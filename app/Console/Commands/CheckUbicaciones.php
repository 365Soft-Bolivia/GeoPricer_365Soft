<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductLocation;

class CheckUbicaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ubicaciones:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de las ubicaciones de las propiedades';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('============================================');
        $this->info('  Verificación de Ubicaciones');
        $this->info('============================================');
        $this->newLine();

        // 1. Total de productos
        $total = Product::count();
        $this->info("1. Total de productos: {$total}");
        $this->newLine();

        // 2. Productos CON ubicación
        $conUbicacion = Product::has('location')->count();
        $this->info("2. Productos CON ubicación: {$conUbicacion}");
        $this->newLine();

        // 3. Productos SIN ubicación
        $sinUbicacion = Product::doesntHave('location')->count();
        $this->info("3. Productos SIN ubicación: {$sinUbicacion}");
        $this->newLine();

        // 4. Verificar consistencia
        $this->info('--------------------------------------------');
        $this->info('VERIFICACIÓN DE CONSISTENCIA:');
        $this->info('--------------------------------------------');

        $suma = $conUbicacion + $sinUbicacion;
        if ($suma === $total) {
            $this->info("✓ Total consistente: {$conUbicacion} + {$sinUbicacion} = {$total}");
        } else {
            $this->error("✗ Total INCONSISTENTE:");
            $this->error("  Con ubicación + Sin ubicación = {$suma}");
            $this->error("  Total real = {$total}");
            $this->error("  Diferencia = " . abs($total - $suma));
        }
        $this->newLine();

        // 5. Verificar duplicados en product_locations
        $duplicados = DB::table('product_locations')
            ->select('product_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('product_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicados > 0) {
            $this->warn("⚠ Se encontraron {$duplicados} productos con ubicaciones duplicadas");
        } else {
            $this->info("✓ No hay duplicados en product_locations");
        }
        $this->newLine();

        // 6. Verificar coordenadas NULL
        $coordenadasNull = ProductLocation::whereNull('latitude')
            ->orWhereNull('longitude')
            ->count();

        if ($coordenadasNull > 0) {
            $this->warn("⚠ Hay {$coordenadasNull} ubicaciones con coordenadas NULL");
        } else {
            $this->info("✓ Todas las ubicaciones tienen coordenadas válidas");
        }
        $this->newLine();

        // 7. Muestra ejemplos
        $this->info('--------------------------------------------');
        $this->info('EJEMPLOS DE PRODUCTOS:');
        $this->info('--------------------------------------------');

        $ejemplos = Product::leftJoin('product_locations', function($join) {
            $join->on('products.id', '=', 'product_locations.product_id');
        })
        ->select('products.id', 'products.name', 'products.codigo_inmueble',
                 DB::raw('CASE WHEN product_locations.id IS NULL THEN "SIN ubicación" ELSE "CON ubicación" END as estado'))
        ->orderBy('products.id')
        ->limit(10)
        ->get();

        $this->table(
            ['ID', 'Nombre', 'Código', 'Estado'],
            $ejemplos->map(fn($p) => [$p->id, substr($p->name, 0, 40), $p->codigo_inmueble, $p->estado])
        );
        $this->newLine();

        // 8. Análisis del problema
        $this->info('--------------------------------------------');
        $this->info('ANÁLISIS DEL PROBLEMA:');
        $this->info('--------------------------------------------');

        if ($suma !== $total) {
            $this->warn('POSIBLE CAUSA: Hay productos que tienen registros en');
            $this->warn('product_locations pero no están siendo detectados');
            $this->warn('por la relación hasOne().');
            $this->newLine();
            $this->info('RECOMENDACIÓN:');
            $this->info('1. Verificar que no haya productos huérfanos en product_locations');
            $this->info('2. Verificar que no haya productos con múltiples ubicaciones');
            $this->info('3. Limpiar caché: php artisan cache:clear');
        }

        $this->newLine();
        $this->info('============================================');
        $this->info('  Verificación completada');
        $this->info('============================================');

        return Command::SUCCESS;
    }
}
