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
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->text('note')->nullable();
            $table->date('activity_date');
            $table->timestamps();

            $table->index('driver_id');
            $table->index('activity_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activities');
    }
};
