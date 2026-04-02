<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOperacionAlquiler extends Command
{
    protected $signature = 'products:fix-operacion-alquiler
                            {--dry-run : Solo mostrar registros afectados sin ejecutar cambios}
                            {--force : Ejecutar sin confirmación}';

    protected $description = 'Corrige productos que quedaron con operacion=venta por error de keywords durante la importación (enquiler/se enquiler)';

    public function handle()
    {
        $this->info('=== Corrección de Operación: venta → alquiler ===');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('MODO SIMULACIÓN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Buscar registros afectados
        $affected = DB::select(
            "SELECT id, name, operacion FROM products
             WHERE operacion = 'venta'
             AND (name LIKE '%enquiler%' OR name LIKE '%se enquiler%')"
        );

        $count = count($affected);

        if ($count === 0) {
            $this->info('✓ No se encontraron registros afectados.');
            return 0;
        }

        $this->warn("Se encontraron {$count} productos con operacion='venta' que deberían ser 'alquiler':");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'Operacion actual'],
            array_slice(array_map(fn($r) => [
                $r->id,
                strlen($r->name) > 60 ? substr($r->name, 0, 57) . '...' : $r->name,
                $r->operacion,
            ], $affected), 0, 20)
        );

        if ($count > 20) {
            $this->info("... y " . ($count - 20) . " registros más.");
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn('Modo simulación: No se realizaron cambios.');
            $this->info('Para aplicar los cambios ejecuta: php artisan products:fix-operacion-alquiler');
            return 0;
        }

        if (!$force && !$this->confirm("¿Deseas actualizar estos {$count} registros de 'venta' a 'alquiler'?", true)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->newLine();
        $this->info('Aplicando cambios...');

        DB::beginTransaction();
        try {
            $updated = DB::statement(
                "UPDATE products
                 SET operacion = 'alquiler', updated_at = NOW()
                 WHERE operacion = 'venta'
                 AND (name LIKE '%enquiler%' OR name LIKE '%se enquiler%')"
            );

            DB::commit();

            $this->newLine();
            $this->info("✓ ¡Corrección completada! {$count} productos actualizados de 'venta' a 'alquiler'.");
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('✗ Error al aplicar los cambios: ' . $e->getMessage());
            $this->info('Los cambios fueron revertidos.');
            return 1;
        }
    }
}
