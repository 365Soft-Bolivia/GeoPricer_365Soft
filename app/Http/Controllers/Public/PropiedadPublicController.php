<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLocation;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropiedadPublicController extends Controller
{
    /**
     * Muestra el listado público de propiedades
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de paginación y filtros
        $perPage = $request->get('per_page', 100); // 100 propiedades por página como solicitaste
        $page = $request->get('page', 1);

        // Query base para propiedades públicas
        $query = Product::query()
            ->where('is_public', true)
            ->with([
                'images',
                'location',
                'category',
                'addedBy'
            ])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros si existen
        if ($request->filled('categoria')) {
            $query->where('category_id', $request->get('categoria'));
        }

        if ($request->filled('operacion')) {
            $query->where('operacion', $request->get('operacion'));
        }

        if ($request->filled('precio_min')) {
            $query->where('price', '>=', $request->get('precio_min'));
        }

        if ($request->filled('precio_max')) {
            $query->where('price', '<=', $request->get('precio_max'));
        }

        if ($request->filled('ambientes')) {
            $query->where('ambientes', $request->get('ambientes'));
        }

        if ($request->filled('habitaciones')) {
            $query->where('habitaciones', $request->get('habitaciones'));
        }

        if ($request->filled('banos')) {
            $query->where('banos', $request->get('banos'));
        }

        if ($request->filled('cocheras')) {
            $query->where('cocheras', $request->get('cocheras'));
        }

        if ($request->filled('superficie_min')) {
            $query->where('superficie_construida', '>=', $request->get('superficie_min'));
        }

        if ($request->filled('superficie_max')) {
            $query->where('superficie_construida', '<=', $request->get('superficie_max'));
        }

        // Obtener propiedades paginadas
        $propiedades = $query->paginate($perPage, ['*'], 'page', $page);

        // Formatear los datos para el frontend
        $propiedadesFormateadas = $propiedades->getCollection()->map(function ($product) {
            // Mapear los campos del modelo Product a lo que espera el frontend
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
                'category' => $product->category?->name ?? null,
                'is_public' => $product->is_public,
                'comision' => $product->comision,
                // Imágenes formateadas
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => $image->image_path,
                        'original_name' => $image->original_name,
                        'is_primary' => $image->is_primary,
                        'order' => $image->order,
                    ];
                }),
                // Ubicación si existe
                'location' => $product->location ? [
                    'id' => $product->location->id,
                    'latitude' => (float) $product->location->latitude,
                    'longitude' => (float) $product->location->longitude,
                    'address' => $product->location->address,
                    'is_active' => $product->location->is_active,
                ] : null,
            ];
        });

        // Reconstruir la paginación con los datos formateados
        $paginationData = [
            'current_page' => $propiedades->currentPage(),
            'last_page' => $propiedades->lastPage(),
            'per_page' => $propiedades->perPage(),
            'total' => $propiedades->total(),
            'from' => $propiedades->firstItem(),
            'to' => $propiedades->lastItem(),
        ];

        // Obtener opciones para filtros
        $filterOptions = [
            'categorias' => ProductCategory::pluck('category_name', 'id')->toArray(),
            'operaciones' => ['venta' => 'Venta', 'alquiler' => 'Alquiler', 'anticretico' => 'Anticrético'],
            'rango_precios' => [
                '0-50000' => '$0 - $50,000',
                '50000-100000' => '$50,000 - $100,000',
                '100000-200000' => '$100,000 - $200,000',
                '200000-500000' => '$200,000 - $500,000',
                '500000+' => '$500,000+',
            ],
            'ubicaciones' => ProductLocation::whereNotNull('address')
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

        return Inertia::render('Public/Propiedades/Index', [
            'propiedades' => $propiedadesFormateadas,
            'pagination' => $paginationData,
            'filter_options' => $filterOptions,
            'filtros' => $request->only([
                'categoria',
                'operacion',
                'precio_min',
                'precio_max',
                'ambientes',
                'habitaciones',
                'banos',
                'cocheras',
                'superficie_min',
                'superficie_max',
                'per_page',
            ]),
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
                    'category' => $product->category?->name ?? null,
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