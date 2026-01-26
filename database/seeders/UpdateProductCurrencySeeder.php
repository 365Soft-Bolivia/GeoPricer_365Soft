<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class UpdateProductCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Actualizar todas las propiedades que no tienen moneda asignada
        $updated = Product::whereNull('currency')
            ->update(['currency' => 'USD']);

        $this->command->info("Actualizadas {$updated} propiedades con moneda por defecto USD.");
    }
}
