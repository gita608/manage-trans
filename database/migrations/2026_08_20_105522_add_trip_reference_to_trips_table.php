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
            $table->string('trip_reference')->nullable()->after('id');
        });
        
        // Backfill existing trips with TRP references based on their IDs
        $trips = \Illuminate\Support\Facades\DB::table('trips')->get();
        foreach ($trips as $trip) {
            \Illuminate\Support\Facades\DB::table('trips')
                ->where('id', $trip->id)
                ->update([
                    'trip_reference' => sprintf('TRP-%06d', $trip->id)
                ]);
        }
        
        // Now add unique index after backfilling
        Schema::table('trips', function (Blueprint $table) {
            $table->unique('trip_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique(['trip_reference']);
            $table->dropColumn('trip_reference');
        });
    }
};
