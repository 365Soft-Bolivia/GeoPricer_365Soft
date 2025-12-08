<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLocation;
use App\Models\ProductCategory;
use App\Services\PropertyFilterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropiedadPublicController extends Controller
{
    private PropertyFilterService $filterService;

    public function __construct(PropertyFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Muestra el listado público de propiedades con filtros optimizados
     */
    public function index(Request $request): Response
    {
        $data = $this->filterService->applyFilters($request);

        return Inertia::render('Public/Propiedades/Index', [
            'propiedades' => $data['propiedades'],
            'pagination' => $data['pagination'],
            'filter_options' => $data['filter_options'],
            'filtros' => $data['filters_applied'],
        ]);
    }

    /**
     * Vista del mapa interactivo con todas las propiedades públicas con ubicación
     */
    public function mapa(): Response
    {
        // Obtener solo propiedades públicas que tengan ubicación activa
        $productsConUbicacion = Product::with(['location', 'category', 'images'])
            ->where('is_public', true)
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
                    'price' => $product->price,
                    'operacion' => $product->operacion,
                    'default_image' => $product->default_image,
                    'category' => $product->category?->category_name ?? null,
                    'category_id' => $product->category_id,
                    // Campos informativos adicionales
                    'habitaciones' => $product->habitaciones,
                    'banos' => $product->banos,
                    'ambientes' => $product->ambientes,
                    'cocheras' => $product->cocheras,
                    'superficie_util' => $product->superficie_util,
                    'superficie_construida' => $product->superficie_construida,
                    'ano_construccion' => $product->ano_construccion,
                    'antiguedad' => $product->ano_construccion ? (date('Y') - $product->ano_construccion) : null,
                    'comision' => $product->comision,
                    'location' => [
                        'id' => $product->location->id,
                        'latitude' => $product->location->latitude,
                        'longitude' => $product->location->longitude,
                        'address' => $product->location->address,
                        'is_active' => $product->location->is_active,
                    ],
                    // Incluir todas las imágenes del producto
                    'images' => $product->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => $image->image_path,
                            'original_name' => $image->original_name,
                            'is_primary' => $image->is_primary,
                            'order' => $image->order,
                        ];
                    })->toArray(),
                ];
            });

        // Obtener categorías disponibles que tengan propiedades públicas con ubicación
        $categoriasDisponibles = Product::where('is_public', true)
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            })
            ->whereNotNull('category_id')
            ->with('category')
            ->get()
            ->pluck('category.category_name', 'category.id')
            ->unique()
            ->toArray();

        return Inertia::render('Public/Propiedades/Mapa', [
            'productsConUbicacion' => $productsConUbicacion,
            'categoriasDisponibles' => $categoriasDisponibles,
            'defaultCenter' => [
                'lat' => -16.5000, // La Paz, Bolivia (centro del país)
                'lng' => -68.1500,
            ],
            'totalPropiedades' => $productsConUbicacion->count(),
        ]);
    }

    /**
     * Muestra el detalle de una propiedad específica
     */
    public function show(int $id): Response
    {
        // Obtener la propiedad con sus relaciones
        $propiedad = Product::with([
            'images' => function ($query) {
                $query->orderBy('order')->orderBy('is_primary', 'desc');
            },
            'location',
            'category',
            'addedBy',
            'files'
        ])
        ->where('is_public', true)
        ->findOrFail($id);

        // Formatear los datos para el frontend
        $propiedadFormateada = [
            'id' => $propiedad->id,
            'name' => $propiedad->name,
            'codigo_inmueble' => $propiedad->codigo_inmueble ?? $propiedad->sku ?? 'N/A',
            'price' => (float) $propiedad->price,
            'descripcion' => $propiedad->description,
            'direccion' => $propiedad->location?->address ?? null,
            'superficie_util' => $propiedad->superficie_util,
            'superficie_construida' => $propiedad->superficie_construida,
            'ambientes' => $propiedad->ambientes,
            'habitaciones' => $propiedad->habitaciones,
            'banos' => $propiedad->banos,
            'cocheras' => $propiedad->cocheras,
            'ano_construccion' => $propiedad->ano_construccion,
            'antiguedad' => $propiedad->antiguedad,
            'operacion' => $propiedad->operacion,
            'category' => $propiedad->category?->name ?? null,
            'is_public' => $propiedad->is_public,
            'comision' => $propiedad->comision,
            'created_at' => $propiedad->created_at->format('d/m/Y'),
            'images' => $propiedad->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'original_name' => $image->original_name,
                    'is_primary' => $image->is_primary,
                    'order' => $image->order,
                ];
            }),
            'location' => $propiedad->location ? [
                'id' => $propiedad->location->id,
                'latitude' => (float) $propiedad->location->latitude,
                'longitude' => (float) $propiedad->location->longitude,
                'address' => $propiedad->location->address,
                'is_active' => $propiedad->location->is_active,
            ] : null,
            'agente' => $propiedad->addedBy ? [
                'id' => $propiedad->addedBy->id,
                'name' => $propiedad->addedBy->name,
                'email' => $propiedad->addedBy->email,
                'phone' => $propiedad->addedBy->phone ?? null,
            ] : null,
        ];

        // Obtener propiedades relacionadas (misma categoría, misma zona)
        $relacionadas = Product::where('is_public', true)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($propiedad) {
                if ($propiedad->category_id) {
                    $query->where('category_id', $propiedad->category_id);
                }
            })
            ->with(['images', 'location', 'category'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
                    'price' => (float) $product->price,
                    'category' => $product->category?->category_name ?? null,
                    'direccion' => $product->location?->address ?? null,
                    'images' => $product->images->take(2)->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => $image->image_path,
                            'is_primary' => $image->is_primary,
                        ];
                    }),
                ];
            });

        return Inertia::render('Public/Propiedades/Show', [
            'propiedad' => $propiedadFormateada,
            'relacionadas' => $relacionadas,
        ]);
    }
}