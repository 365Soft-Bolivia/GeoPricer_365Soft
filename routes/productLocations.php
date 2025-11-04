<?php

use App\Http\Controllers\ProductLocationController;
use Illuminate\Support\Facades\Route;

// Ruta principal para la vista del dashboard de ubicaciones (renderiza Vue)
Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('ubicaciones', [ProductLocationController::class, 'index'])
    ->name('ubicaciones');

// Rutas para las vistas (renderiza componentes Vue con Inertia)
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('ubicaciones')
    ->name('ubicaciones.')
    ->group(function () {
        // Vista del mapa con todas las ubicaciones
        Route::get('/mapa', [ProductLocationController::class, 'mapa'])
            ->name('mapa');
        
        // Vista para asignar ubicaciones a productos
        Route::get('/asignar', [ProductLocationController::class, 'asignar'])
            ->name('asignar');
        
        // Vista para editar ubicaciones existentes
        Route::get('/editar', [ProductLocationController::class, 'editar'])
            ->name('editar');
    });

// Grupo de rutas para operaciones con ubicaciones (API endpoints)
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('ubicaciones/api')
    ->name('ubicaciones.api.')
    ->group(function () {
        // Listar todos los productos con ubicación
        Route::get('/listar', [ProductLocationController::class, 'listar'])
            ->name('listar');
        
        // Obtener ubicación de un producto específico
        Route::get('/{product}/obtener', [ProductLocationController::class, 'show'])
            ->name('show');
        
        // Guardar/actualizar ubicación de un producto
        Route::post('/{product}', [ProductLocationController::class, 'store'])
            ->name('store');
        
        // Activar/desactivar ubicación
        Route::post('/{product}/toggle-status', [ProductLocationController::class, 'toggleActive'])
            ->name('toggle-status');
        
        // Eliminar ubicación
        Route::delete('/{product}', [ProductLocationController::class, 'destroy'])
            ->name('destroy');
        
        // Buscar productos cercanos
        Route::post('/cercanos', [ProductLocationController::class, 'nearby'])
            ->name('cercanos');
    });