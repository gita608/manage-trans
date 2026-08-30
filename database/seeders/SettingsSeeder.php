<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => config('app.name', 'Laravel'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_logo',
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_signup',
                'value' => 'false',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_forgot_password',
                'value' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'android_version',
                'value' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ios_version',
                'value' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'force_android_version',
                'value' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'force_ios_version',
                'value' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'location_sync_intervel',
                'value' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'check_in_auto_checkout_hours',
                'value' => '12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
