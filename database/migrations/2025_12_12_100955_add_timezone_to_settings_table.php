<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert timezone setting if it doesn't exist
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'app_timezone'],
            [
                'value' => config('app.timezone', 'Asia/Dubai'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove timezone setting
        \Illuminate\Support\Facades\DB::table('settings')->where('key', 'app_timezone')->delete();
    }
};
