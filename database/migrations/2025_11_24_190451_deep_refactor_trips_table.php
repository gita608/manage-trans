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
        // 1. Add columns to trip_crews
        Schema::table('trip_crews', function (Blueprint $table) {
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->onDelete('cascade');
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('assigned');
        });

        // 2. Add title to trips
        Schema::table('trips', function (Blueprint $table) {
            $table->string('title')->nullable()->after('trip_date');
        });

        // 3. Migrate data
        $trips = DB::table('trips')->get();
        foreach ($trips as $trip) {
            // Update all crews for this trip with the trip's details
            DB::table('trip_crews')
                ->where('trip_id', $trip->id)
                ->update([
                    'vessel_id' => $trip->vessel_id,
                    'pick_up_time' => $trip->pick_up_time,
                    'from_location' => $trip->from_location,
                    'to_location' => $trip->to_location,
                    'remarks' => $trip->remarks,
                    'status' => $trip->status,
                ]);
            
            // Set a default title for the trip
            DB::table('trips')
                ->where('id', $trip->id)
                ->update(['title' => 'Trip on ' . $trip->trip_date]);
        }

        // 4. Drop columns from trips
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropColumn([
                'vessel_id',
                'pick_up_time',
                'from_location',
                'to_location',
                'remarks',
                'status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add columns back to trips
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->onDelete('cascade');
            $table->time('pick_up_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('assigned');
        });

        // 2. Migrate data back
        $trips = DB::table('trips')->get();
        foreach ($trips as $trip) {
            // Get the first crew for this trip
            $firstCrew = DB::table('trip_crews')->where('trip_id', $trip->id)->first();
            
            if ($firstCrew) {
                DB::table('trips')
                    ->where('id', $trip->id)
                    ->update([
                        'vessel_id' => $firstCrew->vessel_id,
                        'pick_up_time' => $firstCrew->pick_up_time,
                        'from_location' => $firstCrew->from_location,
                        'to_location' => $firstCrew->to_location,
                        'remarks' => $firstCrew->remarks,
                        'status' => $firstCrew->status,
                    ]);
            }
        }

        // 3. Drop columns from trip_crews
        Schema::table('trip_crews', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropColumn([
                'vessel_id',
                'pick_up_time',
                'from_location',
                'to_location',
                'remarks',
                'status'
            ]);
        });

        // 4. Drop title from trips
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
