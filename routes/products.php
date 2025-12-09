<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImageController;

// Rutas protegidas para productos (admin)
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    
    // CRUD de proyectos/productos
    Route::get('/proyectos', [ProductController::class, 'index'])->name('admin.proyectos.index');
    Route::get('/proyectos/crear', [ProductController::class, 'create'])->name('admin.proyectos.create');
    Route::post('/proyectos', [ProductController::class, 'store'])->name('admin.proyectos.store');
    Route::get('/proyectos/{id}', [ProductController::class, 'show'])->name('admin.proyectos.show');
    Route::get('/proyectos/{id}/editar', [ProductController::class, 'edit'])->name('admin.proyectos.edit');
    Route::put('/proyectos/{id}', [ProductController::class, 'update'])->name('admin.proyectos.update');
    Route::patch('/proyectos/{id}/toggle', [ProductController::class, 'toggleStatus'])->name('admin.proyectos.toggle');
    Route::delete('/proyectos/{id}', [ProductController::class, 'destroy'])->name('admin.proyectos.destroy');

    // Rutas de imágenes de productos
    Route::prefix('productos/{productId}/imagenes')->name('productos.images.')->group(function () {
        Route::post('/', [ProductImageController::class, 'store'])->name('store');
        Route::post('/{imageId}/principal', [ProductImageController::class, 'setPrimary'])->name('setPrimary');
        Route::delete('/{imageId}', [ProductImageController::class, 'destroy'])->name('delete');
        Route::post('/reordenar', [ProductImageController::class, 'reorder'])->name('reorder');
    });
});