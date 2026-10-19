<?php

use App\Models\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->string('default_role')->nullable()->after('uniqueness_scope')
        ->comment('Role sistem (roles.name) yang otomatis dipilih untuk pemangku jabatan');
      $table->foreignId('created_by')->nullable()->after('default_role')
        ->constrained('users')->nullOnDelete()
        ->comment('Pegawai yang menambahkan jabatan; null = bawaan sistem');
    });

    // Isi role default dari pola nama jabatan yang sudah ada.
    DB::table('positions')->whereNull('default_role')->orderBy('id')
      ->each(function ($position) {
        DB::table('positions')->where('id', $position->id)
          ->update(['default_role' => Position::guessDefaultRole($position->name)]);
      });

    // Pengusul pada pengajuan jabatan yang disetujui menjadi penambah jabatan.
    if (Schema::hasTable('position_requests')) {
      DB::table('position_requests')
        ->where('status', 'approved')
        ->whereNotNull('position_id')
        ->orderBy('id')
        ->each(function ($request) {
          DB::table('positions')
            ->where('id', $request->position_id)
            ->whereNull('created_by')
            ->update(['created_by' => $request->requested_by]);
        });
    }
  }

  public function down(): void
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->dropConstrainedForeignId('created_by');
      $table->dropColumn('default_role');
    });
  }
};
