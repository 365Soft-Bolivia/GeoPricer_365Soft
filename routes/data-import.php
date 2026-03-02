<?php

use App\Http\Controllers\DataImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        // Página principal de importación
        Route::get('/data-import', [DataImportController::class, 'index'])->name('admin.data-import');

        // Procesar archivo JSON
        Route::post('/data-import/process', [DataImportController::class, 'process'])->name('admin.data-import.process');

        // Obtener categorías disponibles
        Route::get('/data-import/categories', [DataImportController::class, 'categories'])->name('admin.data-import.categories');
    });
