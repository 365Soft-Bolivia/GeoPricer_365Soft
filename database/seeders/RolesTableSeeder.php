<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'El administrador puede gestionar todo lo relacionado con la aplicación..',
                'created_at' => '2025-10-29 17:16:12',
                'updated_at' => '2025-10-29 17:16:12'
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'employee',
                'display_name' => 'Agente',
                'description' => 'El agente gestiona las ventas de los inmuebles',
                'created_at' => '2025-10-29 17:16:12',
                'updated_at' => '2025-10-29 17:16:12'
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'name' => 'client',
                'display_name' => 'Cliente',
                'description' => 'Descripcion Test',
                'created_at' => '2025-10-29 17:16:12',
                'updated_at' => '2025-10-29 17:16:12'
            ]
        ]);
    }
}