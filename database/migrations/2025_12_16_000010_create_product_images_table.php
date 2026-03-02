<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Primero crear la tabla sin FK
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Sin FK inicialmente
            $table->string('image_path');
            $table->string('original_name')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('product_id');
            $table->index(['product_id', 'is_primary']);
        });

        // Intentar agregar la FK después (manejar error silenciosamente)
        try {
            Schema::table('product_images', function (Blueprint $table) {
                $table->foreign('product_id')
                      ->references('id')
                      ->on('products')
                      ->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Log del error pero continuar
            \Log::warning('No se pudo crear FK para product_images: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};