<?php
// database/migrations/2024_01_01_000006_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('name', 191);
            $table->string('codigo_inmueble', 191)->unique();
            $table->string('price', 191);
            $table->decimal('superficie_util', 8, 2)->nullable();
            $table->decimal('superficie_construida', 8, 2)->nullable();
            $table->integer('ambientes')->nullable();
            $table->integer('habitaciones')->nullable();
            $table->integer('banos')->nullable();
            $table->integer('cocheras')->nullable();
            $table->year('ano_construccion')->nullable();
            $table->string('operacion', 191)->nullable();
            $table->decimal('comision', 10, 2)->nullable();
            $table->string('taxes', 191)->nullable();
            $table->boolean('allow_purchase')->default(0);
            $table->boolean('is_public')->default(0)->index();
            $table->boolean('downloadable')->default(0);
            $table->string('downloadable_file', 191)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedInteger('added_by')->nullable();
            $table->unsignedInteger('last_updated_by')->nullable();
            $table->string('hsn_sac_code', 191)->nullable();
            $table->string('default_image', 191)->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('sku', 191)->nullable();

            // Índices compuestos para optimización
            $table->index(['is_public', 'created_at'], 'idx_public_created');
            $table->index(['is_public', 'category_id'], 'idx_public_category');
            $table->index(['is_public', 'operacion'], 'idx_public_operation');
            $table->index(['is_public', 'price'], 'idx_public_price');
            $table->index(['is_public', 'ambientes'], 'idx_public_ambientes');
            $table->index(['is_public', 'habitaciones'], 'idx_public_habitaciones');
            $table->index(['is_public', 'banos'], 'idx_public_banos');
            $table->index(['is_public', 'cocheras'], 'idx_public_cocheras');
            $table->index(['is_public', 'superficie_construida'], 'idx_public_superficie_construida');
            $table->index(['is_public', 'superficie_util'], 'idx_public_superficie_terreno');
            $table->index(['is_public', 'codigo_inmueble'], 'idx_public_codigo');
            $table->index(['is_public', 'sku'], 'idx_public_sku');
            $table->index(['is_public', 'category_id', 'operacion', 'price'], 'idx_public_category_operation_price');

            $table->foreign('category_id')->references('id')->on('product_category')->onDelete('set null');
            $table->foreign('sub_category_id')->references('id')->on('product_category')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};