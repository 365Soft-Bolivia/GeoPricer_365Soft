<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,           // 1. Primero crear roles
            UsersTableSeeder::class,            // 2. Luego usuarios
            RoleUserTableSeeder::class,         // 3. Relaciones rol-usuario
            SessionsTableSeeder::class,         // 4. Sesiones
            ProductCategorySeeder::class,       // 5. Categorías de productos
        ]);
    }
}