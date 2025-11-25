<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductLocation;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Muestra la página de inicio pública
     */
    public function index(): Response
    {
        // Obtener propiedades destacadas (públicas y con imagen)
        $featured_properties = Product::with(['images', 'location', 'category'])
            ->where('is_public', true)
            ->has('images') // Solo propiedades con imágenes
            ->inRandomOrder()
            ->take(6)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'operacion' => $product->operacion ?? 'venta',
                    'ambientes' => $product->ambientes,
                    'habitaciones' => $product->habitaciones,
                    'superficie_construida' => $product->superficie_construida,
                    'direccion' => $product->location?->address,
                    'imagen_principal' => $product->images->where('is_primary', true)->first()?->image_path
                                        ?? $product->images->first()?->image_path,
                    'categoria' => $product->category?->category_name ?? 'Propiedad',
                ];
            });

        // Estadísticas reales basadas en datos existentes
        $stats = [
            'total_propiedades' => Product::where('is_public', true)->count(),
            'total_categorias' => ProductCategory::count(),
            'ubicaciones' => ProductLocation::count(),
            'clientes_satisfechos' => 150, // Este dato puede venir de una tabla de clientes o testimonios
        ];

        // Opciones para filtros basadas en datos reales
        $filter_options = [
            'categorias' => ProductCategory::orderBy('category_name')->pluck('category_name', 'id'),
            'operaciones' => [
                'venta' => 'Venta',
                'alquiler' => 'Alquiler',
                'anticretico' => 'Anticrético'
            ],
            'rango_precios' => [
                '0-50000' => 'Hasta $50,000',
                '50000-100000' => '$50,000 - $100,000',
                '100000-200000' => '$100,000 - $200,000',
                '200000-500000' => '$200,000 - $500,000',
                '500000+' => 'Más de $500,000'
            ],
            // No hay campo 'ciudad' en ProductLocation, solo 'address'
            'ubicaciones' => ProductLocation::whereNotNull('address')
                                        ->distinct('address')
                                        ->limit(10)
                                        ->pluck('address'),
        ];

        return Inertia::render('Public/Home', [
            'featured_properties' => $featured_properties,
            'stats' => $stats,
            'filter_options' => $filter_options,
        ]);
    }
}