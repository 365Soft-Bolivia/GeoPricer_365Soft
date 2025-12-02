<?php

use App\Http\Controllers\AccesosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {// Ruta principal
        Route::get('accesos', [AccesosController::class, 'index'])
            ->name('admin.accesos');

        // Rutas API
        Route::prefix('accesos')
            ->name('admin.accesos.')
            ->group(function () {
                Route::get('/listar', [AccesosController::class, 'listar'])->name('listar');
                Route::post('/{id}/toggle-status', [AccesosController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/', [AccesosController::class, 'store'])->name('store');
                Route::put('/{id}', [AccesosController::class, 'update'])->name('update');
                Route::delete('/{id}', [AccesosController::class, 'destroy'])->name('destroy');
            });
        Route::get('/listar', [AccesosController::class, 'listar'])->name('listar');
        
        Route::post('/{id}/toggle-status', [AccesosController::class, 'toggleStatus'])
            ->name('toggle-status');
        
        Route::post('/', [AccesosController::class, 'store'])->name('store');
        Route::put('/{id}', [AccesosController::class, 'update'])->name('update');
        Route::delete('/{id}', [AccesosController::class, 'destroy'])->name('destroy');
    });

Route::get('/accesos', [AccesosController::class, 'index'])->name('accesos');
Route::post('/accesos', [AccesosController::class, 'store'])->name('accesos.store');
Route::put('/accesos/{id}', [AccesosController::class, 'update'])->name('accesos.update');
Route::delete('/accesos/{id}', [AccesosController::class, 'destroy'])->name('accesos.destroy');
Route::patch('/accesos/{id}/toggle-status', [AccesosController::class, 'toggleStatus'])->name('accesos.toggle-status');
Route::post('/accesos/{id}/toggle-status', [AccesosController::class, 'toggleStatus'])->name('accesos.toggle-status');

