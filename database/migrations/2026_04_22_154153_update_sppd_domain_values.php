<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update sppd_requests
        DB::table('sppd_requests')->where('domain', 'luar_daerah')->update(['domain' => 'ldlp']);
        DB::table('sppd_requests')->where('domain', 'bimtek')->update(['domain' => 'ldlp']);

        // Update sppd_workflows
        DB::table('sppd_workflows')->where('destination', 'luar_daerah')->update(['destination' => 'ldlp']);
        DB::table('sppd_workflows')->where('destination', 'bimtek')->update(['destination' => 'ldlp']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sppd_requests')->where('domain', 'ldlp')->update(['domain' => 'luar_daerah']);
        DB::table('sppd_workflows')->where('destination', 'ldlp')->update(['destination' => 'luar_daerah']);
    }
};
