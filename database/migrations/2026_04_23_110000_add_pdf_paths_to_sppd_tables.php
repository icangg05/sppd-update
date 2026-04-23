<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sppd_requests', function (Blueprint $table) {
            $table->string('spt_path')->nullable()->after('attachment')->comment('Path file SPT PDF');
            $table->string('sppd_path')->nullable()->after('spt_path')->comment('Path file SPPD Pelaksana Utama PDF');
        });

        Schema::table('sppd_followers', function (Blueprint $table) {
            $table->string('sppd_path')->nullable()->after('user_id')->comment('Path file SPPD Pengikut PDF');
        });
    }

    public function down(): void
    {
        Schema::table('sppd_requests', function (Blueprint $table) {
            $table->dropColumn(['spt_path', 'sppd_path']);
        });

        Schema::table('sppd_followers', function (Blueprint $table) {
            $table->dropColumn('sppd_path');
        });
    }
};
