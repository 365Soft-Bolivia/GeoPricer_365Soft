<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PropertyFilterService
{
    private const CACHE_DURATION = 300; // 5 minutos
    private const MAX_PER_PAGE = 100;

    /**
     * Aplica filtros optimizados a la consulta de propiedades
     */
    public function applyFilters(Request $request): array
    {
        $cacheKey = $this->generateCacheKey($request);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($request) {
            $perPage = min($request->get('per_page', 12), self::MAX_PER_PAGE);
            $page = $request->get('page', 1);

            $query = Product::query()
                ->public()
                ->withRelations()
                ->orderBy('created_at', 'desc');

            // Aplicar todos los filtros usando los scopes optimizados
            $this->applyCategoryFilters($query, $request);
            $this->applyOperationFilters($query, $request);
            $this->applyPriceFilters($query, $request);
            $this->applyCharacteristicFilters($query, $request);
            $this->applySurfaceFilters($query, $request);
            $this->applySearchFilters($query, $request);

            $propiedades = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'propiedades' => $this->formatProperties($propiedades),
                'pagination' => $this->formatPagination($propiedades),
                'filter_options' => $this->getFilterOptions(),
                'filters_applied' => $this->getAppliedFilters($request),
            ];
        });
    }

    /**
     * Aplica filtros de categorías
     */
    private function applyCategoryFilters($query, Request $request): void
    {
        $query->byCategories($request->get('categoria'));
    }

    /**
     * Aplica filtros de operación
     */
    private function applyOperationFilters($query, Request $request): void
    {
        $query->byOperations($request->get('operacion'));
    }

    /**
     * Aplica filtros de precio
     */
    private function applyPriceFilters($query, Request $request): void
    {
        $minPrice = $request->get('precio_min');
        $maxPrice = $request->get('precio_max');

        if ($minPrice || $maxPrice) {
            $query->byPriceRange($minPrice, $maxPrice);
        }

        // Filtro de rango predefinido
        $query->byPriceRangePreset($request->get('rango_precio'));
    }

    /**
     * Aplica filtros de características
     */
    private function applyCharacteristicFilters($query, Request $request): void
    {
        $query->byCharacteristics(
            $request->get('ambientes'),
            $request->get('habitaciones'),
            $request->get('banos'),
            $request->get('cocheras')
        );
    }

    /**
     * Aplica filtros de superficie
     */
    private function applySurfaceFilters($query, Request $request): void
    {
        // Filtro de superficie construida
        $minConstruida = $request->get('superficie_construida_min');
        $maxConstruida = $request->get('superficie_construida_max');

        if ($minConstruida || $maxConstruida) {
            $query->bySurfaceRange($minConstruida, $maxConstruida);
        }

        // Filtro de superficie de terreno
        $minTerreno = $request->get('superficie_terreno_min');
        $maxTerreno = $request->get('superficie_terreno_max');

        if ($minTerreno || $maxTerreno) {
            $query->byLandSurfaceRange($minTerreno, $maxTerreno);
        }

        // Mantener compatibilidad con los parámetros antiguos
        $minSurface = $request->get('superficie_min');
        $maxSurface = $request->get('superficie_max');

        if ($minSurface || $maxSurface) {
            $query->bySurfaceRange($minSurface, $maxSurface);
        }
    }

    /**
     * Aplica filtros de búsqueda
     */
    private function applySearchFilters($query, Request $request): void
    {
        $query->byCode($request->get('codigo'));
        $query->byLocation($request->get('ubicacion'));
    }

    /**
     * Formatea las propiedades para el frontend
     */
    private function formatProperties($propiedades): array
    {
        return $propiedades->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'codigo_inmueble' => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
                'price' => (float) $product->price,
                'descripcion' => $product->description,
                'direccion' => $product->location->address ?? null,
                'superficie_util' => $product->superficie_util,
                'superficie_construida' => $product->superficie_construida,
                'ambientes' => $product->ambientes,
                'habitaciones' => $product->habitaciones,
                'banos' => $product->banos,
                'cocheras' => $product->cocheras,
                'ano_construccion' => $product->ano_construccion,
                'antiguedad' => $product->antiguedad,
                'operacion' => $product->operacion,
                'category' => $product->category?->category_name ?? null,
                'is_public' => $product->is_public,
                'comision' => $product->comision,
                'images' => $product->images->map(fn($image) => [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'original_name' => $image->original_name,
                    'is_primary' => $image->is_primary,
                    'order' => $image->order,
                ]),
                'location' => $product->location ? [
                    'id' => $product->location->id,
                    'latitude' => (float) $product->location->latitude,
                    'longitude' => (float) $product->location->longitude,
                    'address' => $product->location->address,
                    'is_active' => $product->location->is_active,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Formatea los datos de paginación
     */
    private function formatPagination($propiedades): array
    {
        return [
            'current_page' => $propiedades->currentPage(),
            'last_page' => $propiedades->lastPage(),
            'per_page' => $propiedades->perPage(),
            'total' => $propiedades->total(),
            'from' => $propiedades->firstItem(),
            'to' => $propiedades->lastItem(),
        ];
    }

    /**
     * Obtiene las opciones para los filtros (con cache)
     */
    private function getFilterOptions(): array
    {
        return Cache::remember('property_filter_options', 3600, function () {
            return [
                'categorias' => \App\Models\ProductCategory::pluck('category_name', 'id')->toArray(),
                'operaciones' => ['venta' => 'Venta', 'alquiler' => 'Alquiler', 'anticretico' => 'Anticrético'],
                'rango_precios' => [
                    '0-50000' => '$0 - $50,000',
                    '50000-100000' => '$50,000 - $100,000',
                    '100000-200000' => '$100,000 - $200,000',
                    '200000-500000' => '$200,000 - $500,000',
                    '500000+' => '$500,000+',
                ],
                'ubicaciones' => \App\Models\ProductLocation::whereNotNull('address')
                    ->distinct()
                    ->pluck('address')
                    ->take(10)
                    ->toArray(),
                'opciones_ordenamiento' => [
                    'created_at_desc' => 'Más recientes primero',
                    'created_at_asc' => 'Más antiguos primero',
                    'price_asc' => 'Precio: Menor a Mayor',
                    'price_desc' => 'Precio: Mayor a Menor',
                    'name_asc' => 'Nombre A-Z',
                    'name_desc' => 'Nombre Z-A',
                ],
            ];
        });
    }

    /**
     * Obtiene los filtros aplicados para el frontend
     */
    private function getAppliedFilters(Request $request): array
    {
        return $request->only([
            'categoria',
            'operacion',
            'codigo',
            'rango_precio',
            'ubicacion',
            'precio_min',
            'precio_max',
            'ambientes',
            'habitaciones',
            'banos',
            'cocheras',
            'superficie_min',
            'superficie_max',
            'superficie_construida_min',
            'superficie_construida_max',
            'superficie_terreno_min',
            'superficie_terreno_max',
            'per_page',
        ]);
    }

    /**
     * Genera una clave de cache única basada en los filtros
     */
    private function generateCacheKey(Request $request): string
    {
        $filters = $request->only([
            'categoria', 'operacion', 'codigo', 'rango_precio', 'ubicacion',
            'precio_min', 'precio_max', 'ambientes', 'habitaciones',
            'banos', 'cocheras', 'superficie_min', 'superficie_max',
            'superficie_construida_min', 'superficie_construida_max',
            'superficie_terreno_min', 'superficie_terreno_max',
            'per_page', 'page'
        ]);

        return 'property_filters_' . md5(serialize($filters));
    }

    /**
     * Limpia el cache de filtros cuando se actualiza una propiedad
     */
    public function clearCache(): void
    {
        Cache::forget('property_filter_options');

        // Limpiar cache de búsquedas específicas
        $cacheKeys = Cache::get('property_filter_cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('property_filter_cache_keys');
    }
}