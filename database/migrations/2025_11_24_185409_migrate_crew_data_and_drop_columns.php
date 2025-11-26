<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing data
        $trips = DB::table('trips')->get();
        foreach ($trips as $trip) {
            if ($trip->crew_name) {
                DB::table('trip_crews')->insert([
                    'trip_id' => $trip->id,
                    'name' => $trip->crew_name,
                    'phone' => $trip->crew_phone,
                    'address' => $trip->crew_address,
                    'created_at' => $trip->created_at,
                    'updated_at' => $trip->updated_at,
                ]);
            }
        }

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['crew_name', 'crew_phone', 'crew_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('crew_name')->nullable();
            $table->string('crew_phone')->nullable();
            $table->text('crew_address')->nullable();
        });

        // Restore data (take the first crew found for each trip)
        $tripCrews = DB::table('trip_crews')->orderBy('id')->get();
        foreach ($tripCrews as $crew) {
            // Only update if trip still exists and crew fields are empty (to avoid overwriting if multiple crews existed)
            // Ideally we should pick the "primary" one, but here we just take the first one encountered.
            // Since we can't easily know which one was original, this is a best-effort restore.
            DB::table('trips')
                ->where('id', $crew->trip_id)
                ->whereNull('crew_name')
                ->update([
                    'crew_name' => $crew->name,
                    'crew_phone' => $crew->phone,
                    'crew_address' => $crew->address,
                ]);
        }
    }
};
