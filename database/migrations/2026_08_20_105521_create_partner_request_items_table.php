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
        Schema::create('partner_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_request_id')->constrained('partner_requests')->onDelete('cascade');
            $table->date('trip_date')->nullable();
            $table->time('pick_up_time')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->text('address')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('flight_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('sub_remark')->nullable();
            $table->string('vessel_name_raw')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->onDelete('set null');
            $table->timestamps();
            
            $table->index('partner_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_request_items');
    }
};
