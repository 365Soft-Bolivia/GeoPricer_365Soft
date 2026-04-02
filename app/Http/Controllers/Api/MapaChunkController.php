<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PropertyMapChunkService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MapaChunkController extends Controller
{
    private PropertyMapChunkService $chunkService;

    public function __construct(PropertyMapChunkService $chunkService)
    {
        $this->chunkService = $chunkService;
    }

    /**
     * Obtiene un chunk de propiedades para el mapa
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChunk(Request $request): JsonResponse
    {
        try {
            // Validar request
            $validated = $request->validate([
                'cursor' => ['nullable', 'string'],
                'filtros' => ['nullable', 'array'],
                'filtros.categoria' => ['nullable', 'integer'],
                'filtros.operacion' => ['nullable', 'string', 'in:venta,alquiler,anticretico'],
                'filtros.precio_min' => ['nullable', 'numeric', 'min:0'],
                'filtros.precio_max' => ['nullable', 'numeric', 'min:0'],
                'filtros.habitaciones' => ['nullable', 'integer', 'min:0'],
                'filtros.banos' => ['nullable', 'integer', 'min:0'],
                'filtros.ubicaciones' => ['nullable', 'array'],
                'filtros.ubicaciones.*' => ['string'],
            ]);

            $cursor = $validated['cursor'] ?? null;
            $filtros = $validated['filtros'] ?? [];

            // Obtener chunk
            $result = $this->chunkService->getChunk($filtros, $cursor);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'next_cursor' => $result['next_cursor'],
                'has_more' => $result['has_more'],
                'total_count' => $result['total_count'],
                'chunk_size' => $result['chunk_size']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en MapaChunkController:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar propiedades: ' . $e->getMessage()
            ], 500);
        }
    }
}
