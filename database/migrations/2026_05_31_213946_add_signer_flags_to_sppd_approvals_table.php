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
        Schema::table('sppd_approvals', function (Blueprint $table) {
            $table->boolean('signs_spt')->default(false)->after('status');
            $table->boolean('signs_sppd')->default(false)->after('signs_spt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppd_approvals', function (Blueprint $table) {
            $table->dropColumn(['signs_spt', 'signs_sppd']);
        });
    }
};
