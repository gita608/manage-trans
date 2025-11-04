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
        // Insert favicon setting
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'key' => 'favicon',
            'value' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove favicon setting
        \Illuminate\Support\Facades\DB::table('settings')->where('key', 'favicon')->delete();
    }
};
