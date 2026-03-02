<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductLocation;

echo "=== VERIFICACIÓN DE INSTALACIÓN DEL SISTEMA INTELIGENTE ===\n\n";

// 1. Verificar tablas
echo "1. VERIFICANDO TABLAS...\n";
$tables = DB::select("SHOW TABLES");
echo "✅ Total de tablas: " . count($tables) . "\n\n";

// 2. Verificar categorías
echo "2. VERIFICANDO CATEGORÍAS...\n";
$categorias = ProductCategory::orderBy('category_name')->get();
echo "✅ Categorías creadas: " . $categorias->count() . "\n";
echo "   Deben ser 15 categorías\n\n";

if ($categorias->count() === 15) {
    echo "   ✅ CORRECTO: 15 categorías creadas\n";
    foreach ($categorias as $cat) {
        $count = Product::where('category_id', $cat->id)->count();
        echo sprintf("   • %-30s | %d productos\n", $cat->category_name, $count);
    }
} else {
    echo "   ⚠️  ADVERTENCIA: Se esperaban 15 categorías, pero hay " . $categorias->count() . "\n";
}
echo "\n";

// 3. Verificar productos
echo "3. VERIFICANDO PRODUCTOS...\n";
$productCount = Product::count();
echo "✅ Total de productos: " . $productCount . "\n\n";

// 4. Verificar ubicaciones
echo "4. VERIFICANDO UBICACIONES...\n";
$locationCount = ProductLocation::count();
echo "✅ Total de ubicaciones: " . $locationCount . "\n\n";

if ($productCount > 0) {
    echo "5. VERIFICANDO PRODUCTOS CON UBICACIÓN...\n";
    $productsWithLocation = Product::whereHas('location')->count();
    $productsWithoutLocation = Product::whereDoesntHave('location')->count();

    echo "✅ Productos con ubicación: " . $productsWithLocation . "\n";
    echo "   (Estos aparecerán en el mapa)\n\n";
    echo "⚠️  Productos sin ubicación: " . $productsWithoutLocation . "\n";
    echo "   (No aparecerán en el mapa)\n\n";

    if ($productsWithLocation > 0) {
        echo "   EJEMPLOS DE PRODUCTOS CON UBICACIÓN:\n";
        $examples = Product::with('location')
            ->whereHas('location')
            ->take(3)
            ->get();

        foreach ($examples as $p) {
            echo sprintf("   • ID: %-5d | %-25s | Lat: %8.4f, Lng: %8.4f\n",
                $p->id,
                substr($p->name, 0, 25),
                $p->location->latitude,
                $p->location->longitude
            );
        }
    }
    echo "\n";
} else {
    echo "5. No hay productos para verificar\n";
    echo "   💡 Ejecuta el módulo de Inyección de Datos para importar productos de prueba\n\n";
}

// 6. Verificar que las ubicaciones estén activas
if ($locationCount > 0) {
    echo "6. VERIFICANDO ESTADO DE UBICACIONES...\n";
    $inactiveLocations = ProductLocation::where('is_active', false)->count();

    echo "✅ Ubicaciones activas: " . ($locationCount - $inactiveLocations) . "\n";
    echo "   (Estas aparecerán en el mapa)\n\n";

    if ($inactiveLocations > 0) {
        echo "⚠️  Ubicaciones inactivas: " . $inactiveLocations . "\n";
        echo "   (No aparecerán en el mapa)\n\n";
    }
}

// 7. Resumen final
echo "=== RESUMEN FINAL ===\n";
echo "✅ Base de datos: " . DB::connection()->getDatabaseName() . "\n";
echo "✅ Tablas: " . count($tables) . "\n";
echo "✅ Categorías: " . $categorias->count() . " / 15\n";
echo "✅ Productos: " . $productCount . "\n";
echo "✅ Ubicaciones: " . $locationCount . "\n";

if ($productCount >= 10 && $locationCount >= 10) {
    echo "\n🎉 ¡SISTEMA LISTO PARA PRODUCCIÓN!\n";
    echo "   - Base de datos configurada\n";
    echo "   - Migraciones ejecutadas\n";
    echo "   - Productos importados\n";
    echo "   - Ubicaciones creadas\n";
    echo "   - Listo para usar el mapa\n";
} else {
    echo "\n📝 SIGUIENTE PASO: Importar productos de prueba\n";
    echo "   1. Ve a: http://localhost:8000/admin/data-import\n";
    echo "   2. Importa el archivo: prueba_importacion.json\n";
    echo "   3. Verifica que se importen 10 productos con ubicaciones\n";
}

echo "\n";
