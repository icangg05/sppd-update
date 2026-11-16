<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan role (diatur di menu Role & Permission): keterangan untuk pengguna, tampil di
 * daftar role. Diisi awal untuk role yang di unit kerja Rumah Sakit punya sebutan lain
 * (lihat DepartmentType::roleTitle).
 */
return new class extends Migration
{
  public const NOTES = [
    'kepala_opd'     => 'Di unit kerja Rumah Sakit, role ini disebut Direktur (Direktur RSUD memakai role ini).',
    'sekretaris_opd' => 'Di unit kerja Rumah Sakit, role ini disebut Sekretaris (Sekretaris RSUD memakai role ini).',
  ];

  private function table(): string
  {
    return config('permission.table_names.roles', 'roles');
  }

  public function up(): void
  {
    if (! Schema::hasColumn($this->table(), 'note')) {
      Schema::table($this->table(), function (Blueprint $table) {
        $table->text('note')->nullable()->after('label');
      });
    }

    foreach (self::NOTES as $role => $note) {
      DB::table($this->table())->where('name', $role)->whereNull('note')->update(['note' => $note]);
    }
  }

  public function down(): void
  {
    Schema::table($this->table(), function (Blueprint $table) {
      $table->dropColumn('note');
    });
  }
};
