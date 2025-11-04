<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ProductCategory as Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ProductLocationController extends Controller
{
    /**
     * Vista principal para gestionar ubicaciones de productos
     * Renderiza Locations.vue con Inertia
     */
    public function index(): Response
    {
        // Obtener productos sin ubicación para el selector
        $productsSinUbicacion = Product::doesntHave('location')
            ->select('id', 'name', 'codigo_inmueble', 'operacion', 'price')
            ->orderBy('name')
            ->get();

        // Obtener productos con ubicación para mostrar en el mapa
        $productsConUbicacion = Product::has('location')
            ->with(['location', 'category'])
            ->select('id', 'name', 'codigo_inmueble', 'operacion', 'price', 'default_image', 'category_id')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble,
                    'operacion' => $product->operacion,
                    'price' => $product->price,
                    'default_image' => $product->default_image,
                    'category' => $product->category?->category_name,
                    'location' => [
                        'id' => $product->location->id,
                        'latitude' => $product->location->latitude,
                        'longitude' => $product->location->longitude,
                        'address' => $product->location->address,
                        'is_active' => $product->location->is_active,
                        'coordinates' => $product->location->coordinates,
                        'geojson' => $product->location->toGeoJson(),
                    ],
                ];
            });

        return Inertia::render('Locations', [
            'productsSinUbicacion' => $productsSinUbicacion,
            'productsConUbicacion' => $productsConUbicacion,
            'defaultCenter' => [
                'lat' => -16.5000, // La Paz, Bolivia
                'lng' => -68.1500,
            ],
        ]);
    }

    /**
     * Listar todos los productos con ubicación (para refrescar datos)
     */
    public function listar(Request $request)
    {
        $query = ProductLocation::query()
            ->with(['product' => function ($query) {
                $query->select('id', 'name', 'codigo_inmueble', 'price', 'operacion', 'default_image', 'category_id')
                    ->with('category:id,category_name');
            }]);

        // Filtrar por activos si se solicita
        if ($request->boolean('only_active')) {
            $query->active();
        }

        $locations = $query->get()->map(function ($location) {
            return [
                'id' => $location->id,
                'product_id' => $location->product_id,
                'product' => [
                    'id' => $location->product->id,
                    'name' => $location->product->name,
                    'codigo_inmueble' => $location->product->codigo_inmueble,
                    'price' => $location->product->price,
                    'operacion' => $location->product->operacion,
                    'default_image' => $location->product->default_image,
                    'category' => $location->product->category?->category_name,
                ],
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'address' => $location->address,
                'is_active' => $location->is_active,
                'coordinates' => $location->coordinates,
                'geojson' => $location->toGeoJson(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $locations,
            'total' => $locations->count(),
        ], 200);
    }

    /**
     * Obtener ubicación de un producto específico
     */
    public function show(Product $product)
    {
        if (!$product->hasLocation()) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no tiene ubicación asignada',
            ], 404);
        }

        $location = $product->location;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $location->id,
                'product_id' => $location->product_id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'address' => $location->address,
                'is_active' => $location->is_active,
                'coordinates' => $location->coordinates,
                'geojson' => $location->toGeoJson(),
            ],
        ], 200);
    }

    /**
     * Guardar o actualizar ubicación de un producto
     */
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ], [
            'latitude.required' => 'La latitud es obligatoria',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.required' => 'La longitud es obligatoria',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Actualizar o crear la ubicación
            $location = $product->location()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'address' => $request->address,
                    'is_active' => $request->is_active ?? true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Ubicación guardada correctamente',
                'data' => [
                    'id' => $location->id,
                    'product_id' => $location->product_id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'address' => $location->address,
                    'is_active' => $location->is_active,
                    'coordinates' => $location->coordinates,
                    'geojson' => $location->toGeoJson(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la ubicación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar ubicación
     */
    public function toggleActive(Product $product)
    {
        if (!$product->hasLocation()) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no tiene ubicación asignada',
            ], 404);
        }

        try {
            $location = $product->location;
            $location->is_active = !$location->is_active;
            $location->save();

            return response()->json([
                'success' => true,
                'message' => $location->is_active 
                    ? 'Ubicación activada correctamente' 
                    : 'Ubicación desactivada correctamente',
                'data' => [
                    'id' => $location->id,
                    'is_active' => $location->is_active,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar ubicación de un producto
     */
    public function destroy(Product $product)
    {
        if (!$product->hasLocation()) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no tiene ubicación asignada',
            ], 404);
        }

        try {
            $product->location()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ubicación eliminada correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la ubicación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar productos cercanos a una ubicación
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100', // km
        ], [
            'latitude.required' => 'La latitud es obligatoria',
            'longitude.required' => 'La longitud es obligatoria',
            'radius.min' => 'El radio mínimo es 0.1 km',
            'radius.max' => 'El radio máximo es 100 km',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = $request->radius ?? 5; // 5km por defecto

        try {
            // Obtener todas las ubicaciones activas
            $locations = ProductLocation::active()
                ->with(['product' => function ($query) {
                    $query->select('id', 'name', 'codigo_inmueble', 'price', 'operacion', 'default_image', 'category_id')
                        ->with('category:id,category_name');
                }])
                ->get()
                ->filter(function ($location) use ($lat, $lng, $radius) {
                    $distance = ProductLocation::calculateDistance(
                        $lat,
                        $lng,
                        $location->latitude,
                        $location->longitude
                    );
                    return $distance <= $radius;
                })
                ->map(function ($location) use ($lat, $lng) {
                    $distance = ProductLocation::calculateDistance(
                        $lat,
                        $lng,
                        $location->latitude,
                        $location->longitude
                    );

                    return [
                        'id' => $location->id,
                        'product' => [
                            'id' => $location->product->id,
                            'name' => $location->product->name,
                            'codigo_inmueble' => $location->product->codigo_inmueble,
                            'price' => $location->product->price,
                            'operacion' => $location->product->operacion,
                            'default_image' => $location->product->default_image,
                            'category' => $location->product->category?->category_name,
                        ],
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'address' => $location->address,
                        'is_active' => $location->is_active,
                        'distance' => round($distance, 2),
                        'coordinates' => $location->coordinates,
                        'geojson' => $location->toGeoJson(),
                    ];
                })
                ->sortBy('distance')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'total' => $locations->count(),
                'search' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'radius' => $radius,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar productos cercanos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

/**
 * Vista del mapa con todas las ubicaciones
 */
public function mapa(): Response
{
    $productsConUbicacion = Product::has('location')
        ->with(['location', 'category'])
        ->select('id', 'name', 'codigo_inmueble', 'price', 'default_image', 'category_id')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'codigo_inmueble' => $product->codigo_inmueble,
                'price' => $product->price,
                'default_image' => $product->default_image,
                'category' => $product->category?->category_name,
                'location' => [
                    'id' => $product->location->id,
                    'latitude' => $product->location->latitude,
                    'longitude' => $product->location->longitude,
                    'address' => $product->location->address,
                    'is_active' => $product->location->is_active,
                ],
            ];
        });

    return Inertia::render('Locations/LocationsMap', [
        'productsConUbicacion' => $productsConUbicacion,
    ]);
}

/**
 * Vista para asignar ubicaciones a productos
 */
public function asignar(): Response
{
    // Obtener productos sin ubicación
    $productsSinUbicacion = Product::doesntHave('location')
        ->with('category:id,category_name')
        ->select('id', 'name', 'codigo_inmueble', 'price', 'default_image', 'category_id')
        ->orderBy('name')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'codigo_inmueble' => $product->codigo_inmueble,
                'price' => $product->price,
                'default_image' => $product->default_image,
                'category' => $product->category?->category_name,
            ];
        });
    // Obtener todas las categorías para el filtro
    $categorias = Category::select('id', 'category_name')
        ->orderBy('category_name')
        ->get();

    return Inertia::render('Locations/LocationsAssign', [
        'productsSinUbicacion' => $productsSinUbicacion,
        'categorias' => $categorias,
        'defaultCenter' => [
            'lat' => -16.5000,
            'lng' => -68.1500,
        ],
    ]);
}

/**
 * Vista para editar ubicaciones existentes
 */
public function editar(): Response
{
    $productsConUbicacion = Product::has('location')
        ->with(['location', 'category'])
        ->select('id', 'name', 'codigo_inmueble', 'price', 'default_image', 'category_id')
        ->orderBy('name')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'codigo_inmueble' => $product->codigo_inmueble,
                'price' => $product->price,
                'default_image' => $product->default_image,
                'category' => $product->category?->category_name,
                'location' => [
                    'id' => $product->location->id,
                    'latitude' => $product->location->latitude,
                    'longitude' => $product->location->longitude,
                    'address' => $product->location->address,
                    'is_active' => $product->location->is_active,
                ],
            ];
        });

    return Inertia::render('Locations/LocationsEdit', [
        'productsConUbicacion' => $productsConUbicacion,
    ]);
}
}