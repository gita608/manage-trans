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
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('follow_up_action');
            $table->string('crew_phone')->nullable()->after('to_location');
            $table->text('crew_address')->nullable()->after('crew_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->text('follow_up_action')->nullable();
            $table->dropColumn(['crew_phone', 'crew_address']);
        });
    }
};
