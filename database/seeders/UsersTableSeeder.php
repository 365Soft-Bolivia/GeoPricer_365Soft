<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'Administrador',
                'email' => 'administrador@gmail.com',
                'password' => '$2y$12$9jNXpnRBew/hV7XQLsC0O.K6RNppcjL1NQzVB80PD2l7iuJXN1Ky2',
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed' => 0,
                'two_factor_email_confirmed' => 0,
                'image' => null,
                'country_phonecode' => null,
                'mobile' => null,
                'gender' => 'male',
                'salutation' => null,
                'locale' => 'es',
                'status' => 'active',
                'login' => 'enable',
                'onesignal_player_id' => null,
                'last_login' => null,
                'email_notifications' => 1,
                'country_id' => null,
                'dark_theme' => 0,
                'rtl' => 0,
                'two_fa_verify_via' => null,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'admin_approval' => 1,
                'permission_sync' => 1,
                'google_calendar_status' => 1,
                'remember_token' => null,
                'created_at' => '2025-11-17 06:49:24',
                'updated_at' => '2025-11-17 06:49:24',
                'customised_permissions' => 0,
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'trial_ends_at' => null,
                'headers' => null,
                'register_ip' => null,
                'location_details' => null,
                'inactive_date' => null,
                'twitter_id' => null,
                'is_client_contact' => null
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'juan',
                'email' => 'juan55@gmail.com',
                'password' => '$2y$12$ABFeO7lvnIHHg8ZcTcg75uEctzPr9y7zSok5yfSbxkPGX.7xF3yCu',
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed' => 0,
                'two_factor_email_confirmed' => 0,
                'image' => null,
                'country_phonecode' => null,
                'mobile' => null,
                'gender' => 'male',
                'salutation' => null,
                'locale' => 'es',
                'status' => 'active',
                'login' => 'enable',
                'onesignal_player_id' => null,
                'last_login' => null,
                'email_notifications' => 1,
                'country_id' => null,
                'dark_theme' => 0,
                'rtl' => 0,
                'two_fa_verify_via' => null,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'admin_approval' => 1,
                'permission_sync' => 1,
                'google_calendar_status' => 1,
                'remember_token' => null,
                'created_at' => '2025-11-17 07:06:53',
                'updated_at' => '2025-12-02 18:47:17',
                'customised_permissions' => 0,
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'trial_ends_at' => null,
                'headers' => null,
                'register_ip' => null,
                'location_details' => null,
                'inactive_date' => null,
                'twitter_id' => null,
                'is_client_contact' => null
            ]
        ]);
    }
}