<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PropiedadPublicController;
use App\Http\Controllers\Public\ContactoController;

// Contacto público (formulario lead)
Route::get('/contacto', fn() => Inertia::render('Public/Contacto'))->name('public.contacto');
Route::post('/contacto', [ContactoController::class, 'store'])->name('public.contacto.store');


/*
|--------------------------------------------------------------------------
| Rutas Públicas (sin autenticación)
|--------------------------------------------------------------------------
*/

// Listado de propiedades público
Route::get('/propiedades', [PropiedadPublicController::class, 'index'])->name('public.propiedades');

// Detalle de una propiedad
Route::get('/propiedad/{id}', [PropiedadPublicController::class, 'show'])->name('public.propiedad.show');

// Otras páginas públicas
Route::get('/sobre-nosotros', fn() => Inertia::render('Public/SobreNosotros'))->name('public.sobre');
Route::post('/contacto', [ContactoController::class, 'store'])->name('public.contacto.store');
