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
        // Permissions table - defines all available permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'view_trips', 'create_trips', 'edit_drivers'
            $table->string('display_name'); // e.g., 'View Trips'
            $table->string('category'); // e.g., 'trips', 'drivers', 'vessels', 'staff'
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Role permissions - assigns permissions to roles (Admin, Staff)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('role'); // 1 = Admin, 2 = Staff
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role', 'permission_id']);
        });

        // User permissions - assigns specific permissions to individual users (overrides role permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->boolean('granted')->default(true); // true = granted, false = revoked
            $table->timestamps();
            
            $table->unique(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
