<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationRadarController extends Controller
{
    /**
     * Mostrar la vista del radar de propiedades
     */
    public function index()
    {
        // Obtener todas las propiedades con ubicación activa
        $productsConUbicacion = Product::with(['category', 'location'])
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble,
                    'price' => $product->price,
                    'superficie_util' => $product->superficie_util,
                    'superficie_construida' => $product->superficie_construida,
                    'category' => $product->category?->category_name,
                    'operacion' => $product->operacion,
                    'ambientes' => $product->ambientes,
                    'habitaciones' => $product->habitaciones,
                    'banos' => $product->banos,
                    'location' => [
                        'latitude' => $product->location->latitude,
                        'longitude' => $product->location->longitude,
                        'is_active' => $product->location->is_active,
                    ],
                ];
            });

        return Inertia::render('Ubicaciones/Radar', [
            'productsConUbicacion' => $productsConUbicacion->values(),
        ]);
    }

    /**
     * API: Buscar propiedades dentro de un radio
     */
    public function searchInRadius(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:100|max:5000',
        ]);

        $centerLat = $validated['latitude'];
        $centerLng = $validated['longitude'];
        $radius = $validated['radius'];

        // Obtener propiedades dentro del radio usando fórmula Haversine
        $products = Product::with(['category', 'location'])
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->filter(function ($product) use ($centerLat, $centerLng, $radius) {
                if (!$product->location) return false;

                $distance = $this->calculateDistance(
                    $centerLat,
                    $centerLng,
                    $product->location->latitude,
                    $product->location->longitude
                );

                return $distance <= $radius;
            })
            ->map(function ($product) use ($centerLat, $centerLng) {
                // Calcular precios por m²
                $pricePerUtilSqm = null;
                $pricePerBuiltSqm = null;

                if ($product->superficie_util && $product->superficie_util > 0) {
                    $pricePerUtilSqm = $product->price / $product->superficie_util;
                }

                if ($product->superficie_construida && $product->superficie_construida > 0) {
                    $pricePerBuiltSqm = $product->price / $product->superficie_construida;
                }

                $distance = $this->calculateDistance(
                    $centerLat,
                    $centerLng,
                    $product->location->latitude,
                    $product->location->longitude
                );

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble,
                    'price' => $product->price,
                    'superficie_util' => $product->superficie_util,
                    'superficie_construida' => $product->superficie_construida,
                    'price_per_util_sqm' => $pricePerUtilSqm,
                    'price_per_built_sqm' => $pricePerBuiltSqm,
                    'category' => $product->category?->category_name,
                    'operacion' => $product->operacion,
                    'ambientes' => $product->ambientes,
                    'habitaciones' => $product->habitaciones,
                    'banos' => $product->banos,
                    'distance' => round($distance, 2),
                    'location' => [
                        'latitude' => $product->location->latitude,
                        'longitude' => $product->location->longitude,
                    ],
                ];
            })
            ->values();

        // Calcular promedios de la zona
        $validUtilProducts = $products->filter(fn($p) => $p['price_per_util_sqm'] !== null);
        $validBuiltProducts = $products->filter(fn($p) => $p['price_per_built_sqm'] !== null);

        $averagePricePerUtilSqm = $validUtilProducts->isNotEmpty()
            ? $validUtilProducts->avg('price_per_util_sqm')
            : null;

        $averagePricePerBuiltSqm = $validBuiltProducts->isNotEmpty()
            ? $validBuiltProducts->avg('price_per_built_sqm')
            : null;

        return response()->json([
            'success' => true,
            'products' => $products,
            'statistics' => [
                'total_properties' => $products->count(),
                'average_price_per_util_sqm' => $averagePricePerUtilSqm,
                'average_price_per_built_sqm' => $averagePricePerBuiltSqm,
                'properties_with_util_surface' => $validUtilProducts->count(),
                'properties_with_built_surface' => $validBuiltProducts->count(),
            ],
        ]);
    }

    /**
     * Calcular distancia entre dos puntos usando fórmula Haversine
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radio de la Tierra en metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distancia en metros
    }
}