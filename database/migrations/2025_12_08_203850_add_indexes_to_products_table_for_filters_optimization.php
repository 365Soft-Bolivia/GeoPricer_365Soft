<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Índices compuestos para búsquedas frecuentes
            $table->index(['is_public', 'created_at'], 'idx_public_created');
            $table->index(['is_public', 'category_id'], 'idx_public_category');
            $table->index(['is_public', 'operacion'], 'idx_public_operation');
            $table->index(['is_public', 'price'], 'idx_public_price');

            // Índices para filtros de características
            $table->index(['is_public', 'ambientes'], 'idx_public_ambientes');
            $table->index(['is_public', 'habitaciones'], 'idx_public_habitaciones');
            $table->index(['is_public', 'banos'], 'idx_public_banos');
            $table->index(['is_public', 'cocheras'], 'idx_public_cocheras');

            // Índices para superficies
            $table->index(['is_public', 'superficie_construida'], 'idx_public_superficie_construida');
            $table->index(['is_public', 'superficie_util'], 'idx_public_superficie_terreno');

            // Índice para búsqueda por código
            $table->index(['is_public', 'codigo_inmueble'], 'idx_public_codigo');
            $table->index(['is_public', 'sku'], 'idx_public_sku');

            // Índice compuesto para filtros combinados (muy común)
            $table->index(['is_public', 'category_id', 'operacion', 'price'], 'idx_public_category_operation_price');
        });

        // Índices para tabla de ubicaciones
        Schema::table('product_locations', function (Blueprint $table) {
            $table->index(['is_active', 'address'], 'idx_location_active_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_public_created');
            $table->dropIndex('idx_public_category');
            $table->dropIndex('idx_public_operation');
            $table->dropIndex('idx_public_price');
            $table->dropIndex('idx_public_ambientes');
            $table->dropIndex('idx_public_habitaciones');
            $table->dropIndex('idx_public_banos');
            $table->dropIndex('idx_public_cocheras');
            $table->dropIndex('idx_public_superficie_construida');
            $table->dropIndex('idx_public_superficie_terreno');
            $table->dropIndex('idx_public_codigo');
            $table->dropIndex('idx_public_sku');
            $table->dropIndex('idx_public_category_operation_price');
        });

        Schema::table('product_locations', function (Blueprint $table) {
            $table->dropIndex('idx_location_active_address');
        });
    }
};
