<?php

use App\Http\Controllers\Public\PublicLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación Pública
|--------------------------------------------------------------------------
|
| Estas rutas manejan la autenticación para el sistema público de GeoPricer.
| Están separadas del sistema de administración para mayor seguridad y control.
|
*/

// Rutas para usuarios no autenticados (invitados)
Route::middleware('guest')->group(function () {
    // Mostrar formulario de login público
    Route::get('/login', [PublicLoginController::class, 'showLoginForm'])
        ->name('public.login')
        ->middleware('login.rate.limit:5');

    // Procesar login público
    Route::post('/login', [PublicLoginController::class, 'login'])
        ->name('public.login.post')
        ->middleware('login.rate.limit:5');
});

// Rutas para usuarios autenticados en el sistema público
Route::middleware('public.auth')->group(function () {
    // Cerrar sesión
    Route::post('/logout', [PublicLoginController::class, 'logout'])
        ->name('public.logout');
});