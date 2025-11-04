<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// CRUD completo
Route::resource('products', ProductController::class)
    ->middleware(['auth', 'verified', 'role:admin']);

