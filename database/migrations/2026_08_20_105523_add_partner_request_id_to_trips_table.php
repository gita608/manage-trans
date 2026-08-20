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
            $table->foreignId('partner_request_id')->nullable()->after('partner_id')->constrained('partner_requests')->onDelete('set null');
            $table->index('partner_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['partner_request_id']);
            $table->dropIndex(['partner_request_id']);
            $table->dropColumn('partner_request_id');
        });
    }
};
