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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('drivers')->onDelete('set null');
            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropIndex(['driver_id']);
            $table->dropColumn('driver_id');
        });
    }
};
