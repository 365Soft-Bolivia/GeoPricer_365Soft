<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductImageController extends Controller
{
    /**
     * Subir nuevas imágenes
     */
    public function store(Request $request, int $productId)
    {
        try {
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
                $path = $image->store('productos/' . $productId, 'public');

                $productImage = ProductImage::create([
                    'product_id' => $productId,
                    'image_path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'is_primary' => $isFirstImage && $index === 0,
                    'order' => $product->images()->count() + $index,
                ]);

                $uploaded[] = [
                    'id' => $productImage->id,
                    'image_path' => $productImage->image_path,
                    'original_name' => $productImage->original_name,
                    'is_primary' => $productImage->is_primary,
                    'order' => $productImage->order,
                ];
            }

            return back()->with([
                'success' => 'Imágenes subidas correctamente.',
                'uploaded' => $uploaded,
            ]);

        } catch (\Exception $e) {
            Log::error('Error subiendo imágenes: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al subir imágenes: ' . $e->getMessage()]);
        }
    }

    /**
     * Establecer imagen como principal
     */
    public function setPrimary(int $productId, int $imageId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            // Verificar que la imagen existe y pertenece al producto
            $image = ProductImage::where('product_id', $productId)
                ->where('id', $imageId)
                ->firstOrFail();

            // Remover el flag de principal de todas las imágenes del producto
            ProductImage::where('product_id', $productId)
                ->update(['is_primary' => false]);
            
            // Establecer la nueva imagen principal
            $image->update(['is_primary' => true]);

            Log::info("Imagen {$imageId} establecida como principal para producto {$productId}");

            return back()->with('success', 'Imagen principal actualizada correctamente.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Imagen o producto no encontrado: {$e->getMessage()}");
            return back()->withErrors(['error' => 'Imagen no encontrada.']);
        } catch (\Exception $e) {
            Log::error('Error estableciendo imagen principal: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar imagen principal.']);
        }
    }

    /**
     * Eliminar imagen
     */
    public function destroy(int $productId, int $imageId)
    {
        try {
            // Verificar que la imagen existe y pertenece al producto
            $image = ProductImage::where('product_id', $productId)
                ->where('id', $imageId)
                ->firstOrFail();

            $wasPrimary = $image->is_primary;
            $imagePath = $image->image_path;
            
            // Eliminar archivo físico si existe
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                Log::info("Archivo eliminado: {$imagePath}");
            } else {
                Log::warning("Archivo no encontrado: {$imagePath}");
            }
            
            // Eliminar registro de la base de datos
            $image->delete();
            Log::info("Imagen {$imageId} eliminada de producto {$productId}");

            // Si era la imagen principal, asignar otra
            if ($wasPrimary) {
                $newPrimary = ProductImage::where('product_id', $productId)
                    ->orderBy('order')
                    ->first();
                
                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                    Log::info("Nueva imagen principal: {$newPrimary->id}");
                }
            }

            return back()->with('success', 'Imagen eliminada correctamente.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Imagen no encontrada: {$e->getMessage()}");
            return back()->withErrors(['error' => 'Imagen no encontrada.']);
        } catch (\Exception $e) {
            Log::error('Error eliminando imagen: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar imagen: ' . $e->getMessage()]);
        }
    }

    /**
     * Reordenar imágenes
     */
    public function reorder(Request $request, int $productId)
    {
        try {
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

            Log::info("Orden actualizado para producto {$productId}");

            return back()->with('success', 'Orden actualizado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error reordenando imágenes: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al reordenar imágenes.']);
        }
    }
}