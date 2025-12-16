<?php
// database/migrations/2024_01_01_000005_create_product_category_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_category', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('category_name', 191);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_category');
    }
};