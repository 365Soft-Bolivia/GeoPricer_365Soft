<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Proyectos', [
            'products' => Product::with('category')->get(),
            'categories' => ProductCategory::select('id', 'category_name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'codigo_inmueble' => 'required|unique:products',
            'price' => 'required|numeric',
            'category_id' => 'nullable|exists:product_categories,id'
        ]);

        Product::create($request->all());

        return back()->with('success', 'Producto creado con éxito');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric'
        ]);

        $product->update($request->all());

        return back()->with('success', 'Producto actualizado con éxito');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Producto eliminado');
    }
}
