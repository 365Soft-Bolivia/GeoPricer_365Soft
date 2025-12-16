<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTA: Esta migración está vacía porque los índices de optimización
     * ya están incluidos en la creación de la tabla products.
     * Se mantiene por compatibilidad con el historial.
     */
    public function up(): void
    {
        // Los índices ya están creados en la tabla products
        // No se realizan cambios para evitar duplicación

        // Solo creamos el índice faltante para locations que no está en la creación original
        Schema::table('product_locations', function (Blueprint $table) {
            if (!Schema::hasIndex('product_locations', 'idx_location_active_address')) {
                $table->index(['is_active', 'address'], 'idx_location_active_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo eliminamos el índice de locations si existe
        Schema::table('product_locations', function (Blueprint $table) {
            if (Schema::hasIndex('product_locations', 'idx_location_active_address')) {
                $table->dropIndex('idx_location_active_address');
            }
        });
    }
};
