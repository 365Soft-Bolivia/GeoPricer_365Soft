<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Casa',
            'Departamento',
            'Penthouse',
            'Terreno',
            'Local Comercial',
            'Oficina',
            'Galpón',
            'Quinta',
            'Estudio/Monoambiente',
            'Dúplex',
            'Condominio',
            'Edificio',
            'Cochera',
            'Habitación',
            'Otros'
        ];

        foreach ($categories as $category) {
            ProductCategory::create([
               'category_name' => $category,
               'company_id' => 1
            ]);
        }
    }
}