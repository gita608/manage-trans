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
            $table->string('status')->default('assigned')->after('title');
        });

        // Migrate existing data: set trip status based on crew statuses
        $trips = \App\Models\Trip::with('crews')->get();
        foreach ($trips as $trip) {
            if ($trip->crews->isEmpty()) {
                $trip->status = \App\Models\TripCrew::STATUS_ASSIGNED;
            } else {
                $total = $trip->crews->count();
                $completed = $trip->crews->where('status', \App\Models\TripCrew::STATUS_COMPLETED)->count();
                
                if ($completed === $total) {
                    $trip->status = \App\Models\TripCrew::STATUS_COMPLETED;
                } else {
                    $inProgress = $trip->crews->where('status', \App\Models\TripCrew::STATUS_IN_PROGRESS)->count();
                    if ($inProgress > 0 || $completed > 0) {
                        $trip->status = \App\Models\TripCrew::STATUS_IN_PROGRESS;
                    } else {
                        $trip->status = \App\Models\TripCrew::STATUS_ASSIGNED;
                    }
                }
            }
            $trip->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
