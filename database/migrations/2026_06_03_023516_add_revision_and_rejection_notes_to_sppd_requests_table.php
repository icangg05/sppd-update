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
            $table->text('revision_note')->nullable()->after('notes');
            $table->text('rejection_note')->nullable()->after('revision_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppd_requests', function (Blueprint $table) {
            $table->dropColumn(['revision_note', 'rejection_note']);
        });
    }
};
