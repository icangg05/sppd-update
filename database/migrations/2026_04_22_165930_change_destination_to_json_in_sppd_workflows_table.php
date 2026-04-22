<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing string data to JSON array format
        $workflows = DB::table('sppd_workflows')->get();
        foreach ($workflows as $w) {
            if ($w->destination && !str_contains($w->destination, '[')) {
                DB::table('sppd_workflows')
                    ->where('id', $w->id)
                    ->update(['destination' => json_encode([$w->destination])]);
            }
        }

        Schema::table('sppd_workflows', function (Blueprint $table) {
            $table->json('destination')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sppd_workflows', function (Blueprint $table) {
            $table->string('destination')->nullable()->change();
        });
    }
};
