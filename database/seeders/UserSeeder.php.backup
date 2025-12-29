<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Crear usuario directamente con DB y obtener el ID
        $userId = DB::table('users')->insertGetId([
            'name' => 'Administrador',
            'email' => 'administrador@gmail.com',
            'password' => '$2y$12$9jNXpnRBew/hV7XQLsC0O.K6RNppcjL1NQzVB80PD2l7iuJXN1Ky2',
            'status' => 'active',
            'company_id' => 1,
            'dark_theme' => 0,
            'rtl' => 0,
            'email_notifications' => 1,
            'gender' => 'male',
            'locale' => 'es',
            'login' => 'enable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_user')->insert([
            'user_id' => $userId,
            'role_id' => 1,
        ]);
    }
}