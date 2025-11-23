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
        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('expense_type_id')->constrained('trip_expense_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('receipt')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'driver_id']);
            $table->index('expense_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_expenses');
    }
};
