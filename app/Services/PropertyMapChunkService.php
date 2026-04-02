<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PropertyMapChunkService
{
    /**
     * Obtiene un chunk de propiedades para el mapa usando cursor pagination
     *
     * @param array $filtros Filtros aplicados (categoria, operacion, etc.)
     * @param string|null $cursor Cursor codificado en base64 para paginación
     * @param int $chunkSize Tamaño del chunk (default: 500)
     * @return array
     */
    public function getChunk(array $filtros, ?string $cursor = null, int $chunkSize = 500): array
    {
        $startTime = microtime(true);

        // Construir query base OPTIMIZADA
        $query = $this->buildBaseQuery($filtros);

        // Aplicar cursor pagination
        if ($cursor) {
            $decodedCursor = $this->decodeCursor($cursor);
            $query->where('products.id', '>', $decodedCursor['last_id']);
        }

        // Obtener total de propiedades (solo en el primer chunk sin cursor)
        $totalCount = null;
        if (!$cursor) {
            $countStart = microtime(true);
            $totalCount = $this->getTotalCount($filtros);
            $countTime = round((microtime(true) - $countStart) * 1000, 2);
            Log::info('Total de propiedades para mapa:', [
                'filtros' => $filtros,
                'total' => $totalCount,
                'chunk_size' => $chunkSize,
                'count_time_ms' => $countTime
            ]);
        }

        // Obtener propiedades del chunk
        $queryStart = microtime(true);
        $products = $query
            ->orderBy('products.id')
            ->take($chunkSize)
            ->get();
        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);

        $formatStart = microtime(true);

        // Formatear datos para el mapa (minimizado para optimizar)
        $formattedProducts = $this->formatForMap($products);

        $formatTime = round((microtime(true) - $formatStart) * 1000, 2);
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        // Generar próximo cursor
        $nextCursor = null;
        $hasMore = false;

        if ($products->count() === $chunkSize) {
            $lastProduct = $products->last();
            $nextCursor = $this->encodeCursor(['last_id' => $lastProduct->id]);
            $hasMore = true;
        }

        Log::info('Chunk generado:', [
            'chunk_size' => $products->count(),
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor ? 'generated' : 'none',
            'query_time_ms' => $queryTime,
            'format_time_ms' => $formatTime,
            'total_time_ms' => $totalTime
        ]);

        return [
            'data' => $formattedProducts,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
            'total_count' => $totalCount,
            'chunk_size' => $products->count()
        ];
    }

    /**
     * Construye la query base con filtros aplicados (OPTIMIZADA con JOIN)
     */
    private function buildBaseQuery(array $filtros): Builder
    {
        // Usar JOIN directo en lugar de whereHas para mejor performance
        $query = Product::with(['location', 'category'])
            ->join('product_locations as pl', 'pl.product_id', '=', 'products.id')
            ->where('products.is_public', true)
            ->where('pl.is_active', true)
            ->select('products.*'); // Evitar columnas duplicadas del join

        // Aplicar filtros
        if (!empty($filtros['categoria'])) {
            $query->where('products.category_id', $filtros['categoria']);
        }

        if (!empty($filtros['operacion'])) {
            $query->where('products.operacion', $filtros['operacion']);
        }

        if (!empty($filtros['precio_min'])) {
            $query->where('products.price_usd', '>=', $filtros['precio_min']);
        }

        if (!empty($filtros['precio_max'])) {
            $query->where('products.price_usd', '<=', $filtros['precio_max']);
        }

        if (!empty($filtros['habitaciones'])) {
            $query->where('products.habitaciones', '>=', $filtros['habitaciones']);
        }

        if (!empty($filtros['banos'])) {
            $query->where('products.banos', '>=', $filtros['banos']);
        }

        if (!empty($filtros['ubicaciones']) && is_array($filtros['ubicaciones'])) {
            $query->whereIn('pl.address', $filtros['ubicaciones']);
        }

        return $query;
    }

    /**
     * Obtiene el total de propiedades con los filtros aplicados
     */
    private function getTotalCount(array $filtros): int
    {
        return $this->buildBaseQuery($filtros)->count();
    }

    /**
     * Formatea las propiedades para el mapa (datos minimizados)
     */
    private function formatForMap($products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'codigo_inmueble' => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
                'price_usd' => $product->price_usd ? (float) $product->price_usd : null,
                'price_bob' => $product->price_bob ? (float) $product->price_bob : null,
                'operacion' => $product->operacion,
                'default_image' => $product->default_image,
                'category' => $product->category?->category_name ?? null,
                'category_id' => $product->category_id,

                // Campos informativos minimizados
                'habitaciones' => $product->habitaciones,
                'banos' => $product->banos,
                'ambientes' => $product->ambientes,
                'cocheras' => $product->cocheras,
                'superficie_util' => $product->superficie_util,
                'superficie_construida' => $product->superficie_construida,

                'location' => [
                    'id' => $product->location->id,
                    'latitude' => $product->location->latitude,
                    'longitude' => $product->location->longitude,
                    'address' => $product->location->address,
                    'is_active' => $product->location->is_active,
                ],
            ];
        })->toArray();
    }

    /**
     * Codifica el cursor en base64
     */
    private function encodeCursor(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    /**
     * Decodifica el cursor desde base64
     */
    private function decodeCursor(string $cursor): array
    {
        return json_decode(base64_decode($cursor), true);
    }
}
