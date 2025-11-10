<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request, int $productId)
    {
        $product = Product::findOrFail($productId);

        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'images.required' => 'Debe seleccionar al menos una imagen.',
            'images.*.image' => 'El archivo debe ser una imagen.',
            'images.*.mimes' => 'La imagen debe ser: jpeg, png, jpg, gif o webp.',
            'images.*.max' => 'La imagen no debe superar los 5MB.',
        ]);

        $uploaded = [];
        $isFirstImage = $product->images()->count() === 0;

        foreach ($request->file('images') as $index => $image) {
            // Guardar en storage/app/public/productos/{id}
            $path = $image->store('productos/' . $productId, 'public');

            $productImage = ProductImage::create([
                'product_id' => $productId,
                'image_path' => $path, // Esto guarda: "productos/23/imagen.jpg"
                'original_name' => $image->getClientOriginalName(),
                'is_primary' => $isFirstImage && $index === 0,
                'order' => $product->images()->count() + $index,
            ]);

            $uploaded[] = [
                'id' => $productImage->id,
                'image_path' => $productImage->image_path, // Enviar image_path
                'original_name' => $productImage->original_name,
                'is_primary' => $productImage->is_primary,
            ];
        }

        return back()->with([
            'success' => 'Imágenes subidas correctamente.',
            'uploaded' => $uploaded,
        ]);
    }

    public function setPrimary(int $productId, int $imageId)
    {
        $product = Product::findOrFail($productId);
        
        $product->images()->update(['is_primary' => false]);
        
        $image = ProductImage::where('product_id', $productId)
            ->where('id', $imageId)
            ->firstOrFail();
        
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Imagen principal actualizada.');
    }

    public function destroy(int $productId, int $imageId)
    {
        $image = ProductImage::where('product_id', $productId)
            ->where('id', $imageId)
            ->firstOrFail();

        $wasPrimary = $image->is_primary;
        
        // Eliminar archivo físico
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $image->delete();

        if ($wasPrimary) {
            $newPrimary = ProductImage::where('product_id', $productId)
                ->orderBy('order')
                ->first();
            
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Imagen eliminada correctamente.');
    }

    public function reorder(Request $request, int $productId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:product_images,id',
            'images.*.order' => 'required|integer',
        ]);

        foreach ($request->images as $imageData) {
            ProductImage::where('id', $imageData['id'])
                ->where('product_id', $productId)
                ->update(['order' => $imageData['order']]);
        }

        return back()->with('success', 'Orden actualizado correctamente.');
    }
}