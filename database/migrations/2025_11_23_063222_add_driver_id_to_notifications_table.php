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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop existing foreign key constraint on user_id
            $table->dropForeign(['user_id']);
            
            // Make user_id nullable since notifications can be for drivers
            $table->foreignId('user_id')->nullable()->change();
            
            // Re-add foreign key constraint as nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Add driver_id column
            $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('drivers')->onDelete('cascade');
            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop driver_id foreign key and column
            $table->dropForeign(['driver_id']);
            $table->dropIndex(['driver_id']);
            $table->dropColumn('driver_id');
            
            // Revert user_id to not nullable
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
