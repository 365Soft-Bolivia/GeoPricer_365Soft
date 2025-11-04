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
        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->comment('Latitud de la ubicación');
            $table->decimal('longitude', 11, 8)->comment('Longitud de la ubicación');
            $table->string('address')->nullable()->comment('Dirección formateada (opcional)');
            $table->boolean('is_active')->default(true)->comment('Activar/desactivar ubicación');
            $table->timestamps();
            
            // Índices para búsquedas
            $table->index(['latitude', 'longitude']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_locations');
    }
};