<?php

use App\Http\Controllers\Api\MapaChunkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API para consumo del frontend
|
*/

Route::prefix('v1')->group(function () {
    // Endpoint para carga progresiva de propiedades en el mapa
    Route::post('/propiedades/mapa-chunk', [MapaChunkController::class, 'getChunk']);
});
