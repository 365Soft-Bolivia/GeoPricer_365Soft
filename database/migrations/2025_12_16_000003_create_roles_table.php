<?php
// database/migrations/2024_01_01_000003_create_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('name', 191);
            $table->string('display_name', 191)->nullable();
            $table->string('description', 191)->nullable();
            $table->timestamps();

            $table->unique(['name', 'company_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};