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
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('license_number')->nullable()->change();
            $table->string('contact')->nullable()->change();
            $table->text('vehicle_info')->nullable()->change();
            $table->integer('age')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('license_number')->nullable(false)->change();
            $table->string('contact')->nullable(false)->change();
            $table->text('vehicle_info')->nullable(false)->change();
            $table->integer('age')->nullable(false)->change();
        });
    }
};
