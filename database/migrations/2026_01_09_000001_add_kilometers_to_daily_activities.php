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
        Schema::table('daily_activities', function (Blueprint $table) {
            $table->decimal('kilometers_driven', 10, 2)->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_activities', function (Blueprint $table) {
            $table->dropColumn('kilometers_driven');
        });
    }
};
