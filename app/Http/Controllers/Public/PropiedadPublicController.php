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
    private const MAX_MAP_MARKERS = 500;

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
    public function mapa(Request $request): Response
    {
        // Construir query base — sin imágenes (se cargan bajo demanda al hacer click)
        $query = Product::with(['location', 'category'])
            ->where('is_public', true)
            ->whereHas('location', function ($query) {
                $query->where('is_active', true);
            });

        // Aplicar filtros si se proporcionaron
        if ($request->has('categoria') && $request->get('categoria')) {
            $categoria = $request->get('categoria');
            $query->where('category_id', $categoria);
            \Log::info('Filtro categoría aplicado:', ['categoria' => $categoria]);
        }

        if ($request->has('operacion') && $request->get('operacion')) {
            $operacion = $request->get('operacion');
            $query->where('operacion', $operacion);
            \Log::info('Filtro operación aplicado:', ['operacion' => $operacion]);
        }

        if ($request->has('precio_min') && $request->get('precio_min')) {
            $precioMin = $request->get('precio_min');
            $query->where('price_usd', '>=', $precioMin);
            \Log::info('Filtro precio_min aplicado:', ['precio_min' => $precioMin]);
        }

        if ($request->has('precio_max') && $request->get('precio_max')) {
            $precioMax = $request->get('precio_max');
            $query->where('price_usd', '<=', $precioMax);
            \Log::info('Filtro precio_max aplicado:', ['precio_max' => $precioMax]);
        }

        if ($request->has('habitaciones') && $request->get('habitaciones')) {
            $habitaciones = $request->get('habitaciones');
            $query->where('habitaciones', '>=', $habitaciones);
            \Log::info('Filtro habitaciones aplicado:', ['habitaciones' => $habitaciones]);
        }

        if ($request->has('banos') && $request->get('banos')) {
            $banos = $request->get('banos');
            $query->where('banos', '>=', $banos);
            \Log::info('Filtro baños aplicado:', ['banos' => $banos]);
        }

        if ($request->has('ubicaciones') && is_array($request->get('ubicaciones')) && count($request->get('ubicaciones')) > 0) {
            $ubicaciones = $request->get('ubicaciones');
            $query->whereHas('location', function ($q) use ($ubicaciones) {
                $q->whereIn('address', $ubicaciones);
            });
            \Log::info('Filtro ubicaciones aplicado:', ['ubicaciones' => $ubicaciones]);
        }

        // Contar resultados (NO obtener las propiedades, se cargarán progresivamente en el frontend)
        $totalCount = $query->count();
        \Log::info('Total de propiedades filtradas:', ['total' => $totalCount]);

        // Solo datos mínimos para marcadores — imágenes y detalle se cargan al hacer click
        $productsConUbicacion = $query->limit(self::MAX_MAP_MARKERS)->get()
            ->map(function ($product) {
                return [
                    'id'                   => $product->id,
                    'name'                 => $product->name,
                    'codigo_inmueble'      => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
                    'price_usd'            => $product->price_usd ? (float) $product->price_usd : null,
                    'price_bob'            => $product->price_bob ? (float) $product->price_bob : null,
                    'operacion'            => $product->operacion,
                    'category_id'          => $product->category_id,
                    'habitaciones'         => $product->habitaciones,
                    'banos'                => $product->banos,
                    'superficie_construida'=> $product->superficie_construida,
                    'location' => [
                        'latitude'  => (float) $product->location->latitude,
                        'longitude' => (float) $product->location->longitude,
                        'is_active' => $product->location->is_active,
                    ],
                ];
            });
            

        // Obtener categorías disponibles via JOIN directo (sin cargar todos los productos)
        $categoriasDisponibles = ProductCategory::select('product_category.id', 'product_category.category_name')
            ->join('products', 'product_category.id', '=', 'products.category_id')
            ->join('product_locations', 'products.id', '=', 'product_locations.product_id')
            ->where('products.is_public', true)
            ->where('product_locations.is_active', true)
            ->distinct()
            ->orderBy('product_category.category_name')
            ->pluck('product_category.category_name', 'product_category.id')
            ->toArray();

        return Inertia::render('Public/Propiedades/Mapa', [
            'productsConUbicacion'  => $productsConUbicacion,
            'categoriasDisponibles' => $categoriasDisponibles,
            'totalPropiedades'      => $totalCount,
            'filtrosAplicados' => [
                'categoria'   => $request->get('categoria') ? (int)$request->get('categoria') : null,
                'operacion'   => $request->get('operacion'),
                'precio_min'  => $request->get('precio_min') ? (float)$request->get('precio_min') : null,
                'precio_max'  => $request->get('precio_max') ? (float)$request->get('precio_max') : null,
                'habitaciones'=> $request->get('habitaciones') ? (int)$request->get('habitaciones') : null,
                'banos'       => $request->get('banos') ? (int)$request->get('banos') : null,
                'ubicaciones' => $request->get('ubicaciones'),
            ]
        ]);
    }

    /**
     * Devuelve el detalle completo de una propiedad para el popup del mapa.
     * Se llama bajo demanda cuando el usuario hace click en un marcador.
     */
    public function preview(int $id): \Illuminate\Http\JsonResponse
    {
        $product = Product::with([
            'images' => fn($q) => $q->orderBy('is_primary', 'desc')->orderBy('order'),
            'location',
            'category',
        ])
        ->where('is_public', true)
        ->findOrFail($id);

        return response()->json([
            'id'                    => $product->id,
            'name'                  => $product->name,
            'codigo_inmueble'       => $product->codigo_inmueble ?? $product->sku ?? 'N/A',
            'price_usd'             => $product->price_usd ? (float) $product->price_usd : null,
            'price_bob'             => $product->price_bob ? (float) $product->price_bob : null,
            'operacion'             => $product->operacion,
            'category'              => $product->category?->category_name ?? null,
            'habitaciones'          => $product->habitaciones,
            'banos'                 => $product->banos,
            'ambientes'             => $product->ambientes,
            'cocheras'              => $product->cocheras,
            'superficie_construida' => $product->superficie_construida,
            'superficie_util'       => $product->superficie_util,
            'ano_construccion'      => $product->ano_construccion,
            'direccion'             => $product->location?->address,
            'comision'              => $product->comision,
            'images'                => $product->images->map(fn($img) => [
                'image_path' => $img->image_path,
                'is_primary' => $img->is_primary,
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
            'price_usd' => $propiedad->price_usd ? (float) $propiedad->price_usd : null,
            'price_bob' => $propiedad->price_bob ? (float) $propiedad->price_bob : null,
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
                    'price_usd' => $product->price_usd ? (float) $product->price_usd : null,
                    'price_bob' => $product->price_bob ? (float) $product->price_bob : null,
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