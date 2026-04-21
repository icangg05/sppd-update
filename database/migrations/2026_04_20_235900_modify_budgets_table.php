<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('program')->nullable()->after('department_id');
            $table->string('kegiatan')->nullable()->after('program');
            $table->string('kode_rekening')->nullable()->after('kegiatan');
            $table->renameColumn('name', 'uraian');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->renameColumn('uraian', 'name');
            $table->dropColumn(['program', 'kegiatan', 'kode_rekening']);
        });
    }
};
