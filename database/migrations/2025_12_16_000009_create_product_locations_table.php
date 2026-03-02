<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Primero crear la tabla sin FK
        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique(); // Sin FK inicialmente
            $table->decimal('latitude', 10, 8)->comment('Latitud de la ubicación');
            $table->decimal('longitude', 11, 8)->comment('Longitud de la ubicación');
            $table->string('address')->nullable()->comment('Dirección formateada (opcional)');
            $table->boolean('is_active')->default(true)->comment('Activar/desactivar ubicación');
            $table->timestamps();
            
            $table->index(['latitude', 'longitude']);
            $table->index('is_active');
        });

        // Intentar agregar la FK después
        try {
            Schema::table('product_locations', function (Blueprint $table) {
                $table->foreign('product_id')
                      ->references('id')
                      ->on('products')
                      ->onDelete('cascade');
            });
        } catch (\Exception $e) {
            \Log::warning('No se pudo crear FK para product_locations: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_locations');
    }
};