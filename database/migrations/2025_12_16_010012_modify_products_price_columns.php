<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminar índices que usan el campo 'price' viejo
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_public_price');
            $table->dropIndex('idx_public_category_operation_price');
        });

        Schema::table('products', function (Blueprint $table) {
            // Agregar nuevos campos (ambos pueden ser null)
            $table->decimal('price_usd', 12, 2)->nullable()->after('codigo_inmueble');
            $table->decimal('price_bob', 12, 2)->nullable()->after('price_usd');
        });

        // Migrar datos existentes
        // Si currency es USD, mover price a price_usd
        DB::statement("
            UPDATE products
            SET price_usd = CAST(price AS DECIMAL(12,2))
            WHERE currency = 'USD' OR currency IS NULL
        ");

        // Si currency es BOB, mover price a price_bob
        DB::statement("
            UPDATE products
            SET price_bob = CAST(price AS DECIMAL(12,2))
            WHERE currency = 'BOB'
        ");

        // Ahora eliminar los campos viejos
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price', 'currency']);
        });

        // Agregar índices para optimizar búsquedas
        Schema::table('products', function (Blueprint $table) {
            $table->index('price_usd');
            $table->index('price_bob');
            // Recrear índices compuestos con price_usd (principal)
            $table->index(['is_public', 'price_usd'], 'idx_public_price_usd');
            $table->index(['is_public', 'category_id', 'operacion', 'price_usd'], 'idx_public_category_operation_price_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero eliminar los índices nuevos
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('price_usd');
            $table->dropIndex('price_bob');
            $table->dropIndex('idx_public_price_usd');
            $table->dropIndex('idx_public_category_operation_price_usd');
        });

        Schema::table('products', function (Blueprint $table) {
            // Recrear los campos viejos
            $table->string('price', 191)->nullable()->after('codigo_inmueble');
            $table->enum('currency', ['USD', 'BOB'])->nullable()->default('USD')->after('price');
        });

        // Intentar recuperar datos (priorizar USD)
        DB::statement("
            UPDATE products
            SET price = CAST(COALESCE(price_usd, price_bob) AS CHAR),
                currency = CASE WHEN price_usd IS NOT NULL THEN 'USD' ELSE 'BOB' END
            WHERE price_usd IS NOT NULL OR price_bob IS NOT NULL
        ");

        // Eliminar nuevos campos
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_usd', 'price_bob']);
        });

        // Recrear índices originales
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_public', 'price'], 'idx_public_price');
            $table->index(['is_public', 'category_id', 'operacion', 'price'], 'idx_public_category_operation_price');
        });
    }
};
