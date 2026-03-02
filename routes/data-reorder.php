<?php

use App\Http\Controllers\DataReorderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        // Página principal de reordenamiento
        Route::get('/data-reorder', [DataReorderController::class, 'index'])->name('admin.data-reorder');

        // Analizar y reordenar productos
        Route::post('/data-reorder/analyze', [DataReorderController::class, 'analyze'])->name('admin.data-reorder.analyze');
    });
