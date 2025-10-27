<?php

use App\Http\Controllers\RolesController;
use Illuminate\Support\Facades\Route;

// Ruta principal para listar roles
Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('roles', [RolesController::class, 'index'])
    ->name('roles');

// Rutas para las acciones CRUD de roles
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('roles')
    ->name('roles.')
    ->group(function () {
        Route::post('/', [RolesController::class, 'store'])->name('store');
        Route::put('/{id}', [RolesController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [RolesController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [RolesController::class, 'destroy'])->name('destroy');
    });