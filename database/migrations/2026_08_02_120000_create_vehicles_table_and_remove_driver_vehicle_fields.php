<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('number')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        $drivers = DB::table('drivers')
            ->where(function ($query) {
                $query->whereNotNull('vehicle_name')
                    ->orWhereNotNull('vehicle_brand')
                    ->orWhereNotNull('vehicle_info');
            })
            ->where(function ($query) {
                $query->where('vehicle_name', '!=', '')
                    ->orWhere('vehicle_brand', '!=', '')
                    ->orWhere('vehicle_info', '!=', '');
            })
            ->get();

        $now = now();

        foreach ($drivers as $driver) {
            $hasName = filled($driver->vehicle_name);
            $hasBrand = filled($driver->vehicle_brand);
            $hasInfo = filled($driver->vehicle_info);

            if (!$hasName && !$hasBrand && !$hasInfo) {
                continue;
            }

            DB::table('vehicles')->insert([
                'name' => $hasName ? $driver->vehicle_name : 'Unnamed Vehicle',
                'brand' => $hasBrand ? $driver->vehicle_brand : null,
                'number' => null,
                'info' => $hasInfo ? $driver->vehicle_info : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['vehicle_name', 'vehicle_brand', 'vehicle_info']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->text('vehicle_info')->nullable()->after('contact');
            $table->string('vehicle_name')->nullable()->after('vehicle_info');
            $table->string('vehicle_brand')->nullable()->after('vehicle_name');
        });

        Schema::dropIfExists('vehicles');
    }
};
