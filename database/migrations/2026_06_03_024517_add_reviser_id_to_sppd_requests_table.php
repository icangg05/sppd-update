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
        Schema::table('sppd_requests', function (Blueprint $table) {
            $table->foreignId('reviser_id')->nullable()->after('rejection_note')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppd_requests', function (Blueprint $table) {
            $table->dropForeign(['reviser_id']);
            $table->dropColumn('reviser_id');
        });
    }
};
