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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('loggable_type'); // Model class (e.g., App\Models\Trip)
            $table->unsignedBigInteger('loggable_id'); // Model ID
            $table->string('action'); // created, updated, deleted, etc.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Who performed the action
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->text('description')->nullable(); // Human-readable description
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['loggable_type', 'loggable_id']);
            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
