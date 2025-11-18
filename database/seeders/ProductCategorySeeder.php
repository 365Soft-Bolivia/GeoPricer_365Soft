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
            'Casa','Departamento','Edificio','Galpón','Habitación','Local Comercial',
            'Oficina','Parqueo','Quinta Propiedad Agricola','Terreno'

        ];

        foreach ($categories as $category) {
            ProductCategory::create([
               'category_name' => $category,
               'company_id' => $this->command->option('companyId')
            ]);
        }
    }
}