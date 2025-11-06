<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $productos = Product::with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('codigo_inmueble', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('category_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'codigo_inmueble' => $product->codigo_inmueble,
                    'price' => $product->price,
                    'superficie_util' => $product->superficie_util,
                    'superficie_construida' => $product->superficie_construida,
                    'ambientes' => $product->ambientes,
                    'habitaciones' => $product->habitaciones,
                    'banos' => $product->banos,
                    'cocheras' => $product->cocheras,
                    'ano_construccion' => $product->ano_construccion,
                    'operacion' => $product->operacion,
                    'comision' => $product->comision,
                    'taxes' => $product->taxes,
                    'description' => $product->description,
                    'sku' => $product->sku,
                    'hsn_sac_code' => $product->hsn_sac_code,
                    'allow_purchase' => $product->allow_purchase,
                    'is_public' => $product->is_public,
                    'downloadable' => $product->downloadable,
                    'downloadable_file' => $product->downloadable_file,
                    'default_image' => $product->default_image,
                    'estado' => $product->estado ?? 1,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'category_name' => $product->category->category_name,
                    ] : null,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $categorias = ProductCategory::orderBy('category_name')
            ->get(['id', 'category_name']);

        return Inertia::render('Proyectos', [
            'productos' => $productos->values(),
            'categorias' => $categorias,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'codigo_inmueble' => 'required|string|max:255|unique:products',
            'price' => 'required|numeric|min:0',
            'superficie_util' => 'nullable|numeric|min:0',
            'superficie_construida' => 'nullable|numeric|min:0',
            'ambientes' => 'nullable|integer|min:0',
            'habitaciones' => 'nullable|integer|min:0',
            'banos' => 'nullable|integer|min:0',
            'cocheras' => 'nullable|integer|min:0',
            'ano_construccion' => 'nullable|digits:4|integer|min:1900|max:' . date('Y'),
            'operacion' => ['required', Rule::in(['venta', 'alquiler', 'anticretico'])],
            'comision' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_category,id',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'hsn_sac_code' => 'nullable|string|max:255',
            'allow_purchase' => 'boolean',
            'is_public' => 'boolean',
            'downloadable' => 'boolean',
            'downloadable_file' => 'nullable|string|max:255',
            'default_image' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'codigo_inmueble.required' => 'El código de inmueble es obligatorio.',
            'codigo_inmueble.unique' => 'Este código de inmueble ya está registrado.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'operacion.required' => 'El tipo de operación es obligatorio.',
            'operacion.in' => 'El tipo de operación debe ser: venta, alquiler o anticrético.',
            'ano_construccion.digits' => 'El año de construcción debe tener 4 dígitos.',
            'ano_construccion.max' => 'El año de construcción no puede ser mayor al año actual.',
            'comision.max' => 'La comisión no puede ser mayor al 100%.',
            'sku.unique' => 'Este SKU ya está registrado.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ]);

        // Agregar campos automáticos
        $validated['estado'] = 1;
        $validated['added_by'] = auth()->id();

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Proyecto creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'codigo_inmueble' => 'required|string|max:255|unique:products,codigo_inmueble,' . $product->id,
            'price' => 'required|numeric|min:0',
            'superficie_util' => 'nullable|numeric|min:0',
            'superficie_construida' => 'nullable|numeric|min:0',
            'ambientes' => 'nullable|integer|min:0',
            'habitaciones' => 'nullable|integer|min:0',
            'banos' => 'nullable|integer|min:0',
            'cocheras' => 'nullable|integer|min:0',
            'ano_construccion' => 'nullable|digits:4|integer|min:1900|max:' . date('Y'),
            'operacion' => ['required', Rule::in(['venta', 'alquiler', 'anticretico'])],
            'comision' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_category,id',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $product->id,
            'hsn_sac_code' => 'nullable|string|max:255',
            'allow_purchase' => 'boolean',
            'is_public' => 'boolean',
            'downloadable' => 'boolean',
            'downloadable_file' => 'nullable|string|max:255',
            'default_image' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'codigo_inmueble.required' => 'El código de inmueble es obligatorio.',
            'codigo_inmueble.unique' => 'Este código de inmueble ya está registrado.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'operacion.required' => 'El tipo de operación es obligatorio.',
            'operacion.in' => 'El tipo de operación debe ser: venta, alquiler o anticrético.',
            'ano_construccion.digits' => 'El año de construcción debe tener 4 dígitos.',
            'ano_construccion.max' => 'El año de construcción no puede ser mayor al año actual.',
            'comision.max' => 'La comisión no puede ser mayor al 100%.',
            'sku.unique' => 'Este SKU ya está registrado.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ]);

        $validated['last_updated_by'] = auth()->id();

        $product->update($validated);

        return redirect()->back()
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function toggleStatus(int $id)
    {
        $product = Product::findOrFail($id);

        $nuevoEstado = $product->estado == 1 ? 0 : 1;
        $product->update(['estado' => $nuevoEstado]);

        return redirect()->back()->with([
            'success' => $nuevoEstado == 1 ? 'Proyecto activado.' : 'Proyecto desactivado.',
        ]);
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $nombreProducto = $product->name;
        
        $product->delete();

        return redirect()->back()->with([
            'success' => "Proyecto '{$nombreProducto}' eliminado correctamente.",
        ]);
    }
    public function show(int $id)
{
    $producto = Product::with('category')->findOrFail($id);

    return Inertia::render('Proyectos/ProyectosShow', [
        'producto' => [
            'id' => $producto->id,
            'name' => $producto->name,
            'codigo_inmueble' => $producto->codigo_inmueble,
            'price' => $producto->price,
            'superficie_util' => $producto->superficie_util,
            'superficie_construida' => $producto->superficie_construida,
            'ambientes' => $producto->ambientes,
            'habitaciones' => $producto->habitaciones,
            'banos' => $producto->banos,
            'cocheras' => $producto->cocheras,
            'ano_construccion' => $producto->ano_construccion,
            'operacion' => $producto->operacion,
            'comision' => $producto->comision,
            'taxes' => $producto->taxes,
            'description' => $producto->description,
            'sku' => $producto->sku,
            'hsn_sac_code' => $producto->hsn_sac_code,
            'allow_purchase' => $producto->allow_purchase,
            'is_public' => $producto->is_public,
            'downloadable' => $producto->downloadable,
            'downloadable_file' => $producto->downloadable_file,
            'default_image' => $producto->default_image,
            'estado' => $producto->estado ?? 1,
            'category' => $producto->category ? [
                'id' => $producto->category->id,
                'category_name' => $producto->category->category_name,
            ] : null,
            'created_at' => $producto->created_at->format('Y-m-d H:i:s'),
        ],
    ]);
}

}