<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Perbaiki data lama: kelurahan yang dibuat lewat form mewarisi type=kecamatan
   * dari induknya. Set kembali ke 'kelurahan' agar diperlakukan sebagai zona data
   * mandiri (lihat Department::getScopedDescendantIds/getScopeRootDepartment).
   */
  public function up(): void
  {
    $kecamatanIds = DB::table('departments')->where('type', 'kecamatan')->pluck('id');

    DB::table('departments')
      ->whereIn('parent_id', $kecamatanIds)
      ->where('name', 'like', 'Kelurahan%')
      ->update(['type' => 'kelurahan']);
  }

  public function down(): void
  {
    // Tidak dibalik: koreksi data satu arah.
  }
};
