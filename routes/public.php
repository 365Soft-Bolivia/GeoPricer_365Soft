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
| Rutas Públicas (simplificado)
|--------------------------------------------------------------------------
|
| Sistema público simplificado sin catálogo de propiedades.
| Solo mantiene el mapa como funcionalidad principal.
*/

// Rutas protegidas - requieren autenticación
Route::middleware(['public.auth'])->group(function () {
    // Home pública
    Route::get('/', [HomeController::class, 'index'])->name('public.home');

    // Mapa interactivo de propiedades públicas
    Route::get('/mapa-propiedades', [PropiedadPublicController::class, 'mapa'])->name('public.mapa.propiedades');

    // Otras páginas públicas
    Route::get('/sobre-nosotros', fn() => Inertia::render('Public/SobreNosotros'))->name('public.sobre');
});

