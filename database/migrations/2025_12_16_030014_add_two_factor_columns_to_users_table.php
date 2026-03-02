<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTA: Esta migración está vacía porque los campos 2FA
     * ya están incluidos en la creación de la tabla users.
     * Se mantiene por compatibilidad con el historial.
     */
    public function up(): void
    {
        // Los campos two_factor ya existen en la tabla users
        // No se realizan cambios para evitar duplicación
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay cambios que revertir
    }
};
