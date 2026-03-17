<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\Public\HomeController;

// Ruta raíz eliminada para evitar conflictos con la ruta pública
// La ruta / está ahora en routes/public.php con protección de autenticación pública
// Route::get('/', [HomeController::class, 'index'])->name('home');

// Redirigir /admin a dashboard o login
Route::get('/admin', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.login');
})->name('admin.home');

// Dashboard (protegido)
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('admin.dashboard');
    });

// Rutas protegidas por rol de administrador
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('categorias', fn () => Inertia::render('Categorias'))->name('admin.categorias');
    });

// Archivos de rutas modulares
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/accesos.php';
require __DIR__.'/roles.php';
require __DIR__.'/productLocations.php';
require __DIR__.'/products.php';
require __DIR__.'/data-import.php';
require __DIR__.'/data-reorder.php';

// Rutas de autenticación pública (deben ir antes de public.php)
require __DIR__.'/public-auth.php';

// Rutas públicas protegidas (propiedades, mapa, etc.)
require __DIR__.'/public.php';