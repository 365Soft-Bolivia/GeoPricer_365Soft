<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// CRUD completo
Route::resource('products', ProductController::class)
    ->middleware(['auth', 'verified', 'role:admin']);
Route::middleware(['auth'])->group(function () {
    Route::get('/proyectos', [ProductController::class, 'index'])->name('proyectos');
    Route::get('/proyectos/{id}', [ProductController::class, 'show'])->name('products.show'); //ruta
    Route::post('/proyectos', [ProductController::class, 'store'])->name('proyectos.store');
    Route::put('/proyectos/{id}', [ProductController::class, 'update'])->name('proyectos.update');
    Route::patch('/proyectos/{id}/toggle', [ProductController::class, 'toggleStatus'])->name('proyectos.toggle');
    Route::delete('/proyectos/{id}', [ProductController::class, 'destroy'])->name('proyectos.destroy');
});
