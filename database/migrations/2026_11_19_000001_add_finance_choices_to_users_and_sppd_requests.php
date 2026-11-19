<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PPTK, KPA (Setuju Bayar), dan Bendahara dipilih untuk tiap pegawai pelaksana di Tahap 1 Buat SPPD
 * (seperti memilih pejabat alur persetujuan), tersimpan di pegawai itu untuk perjalanan berikutnya,
 * lalu dicatat ke SPPD saat diajukan. Lihat App\Services\SppdFinanceOfficials.
 *
 *  - users.pptk_id / kpa_id / kpa_label / bendahara_id: pilihan tersimpan per pegawai pelaksana.
 *  - sppd_requests.kpa_id / kpa_label / bendahara_id / bendahara_label: yang dipakai dokumen biaya
 *    (pptk_id sudah ada).
 *
 * Pengaturan Setuju Bayar per unit (departments.setuju_bayar_*) tidak dipakai lagi. Sebelum kolomnya
 * dihapus, setiap SPPD yang sudah ada mencatat penandatangan persis seperti yang tercetak selama ini:
 * Setuju Bayar = pengaturan unit terdekat lalu Kepala OPD (label null = label bawaan dokumen lama),
 * bendahara = pegawai aktif berjabatan "Bendahara Pengeluaran" di unit pelaksana lalu induknya.
 * Yang dulu kosong tetap kosong (dipilih di Portal Dokumen).
 */
return new class extends Migration
{
  private const INDEPENDENT_ZONES = ['kelurahan', 'puskesmas', 'rumah_sakit'];

  /** @var Collection<int, object> */
  private Collection $departments;

  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->foreignId('pptk_id')->nullable()->after('atasan_id')
        ->comment('PPTK pilihan untuk SPPD pegawai ini (diingat untuk perjalanan berikutnya)')
        ->constrained('users')->nullOnDelete();
      $table->foreignId('kpa_id')->nullable()->after('pptk_id')
        ->comment('Penandatangan Setuju Bayar (KPA) pilihan untuk SPPD pegawai ini')
        ->constrained('users')->nullOnDelete();
      $table->string('kpa_label', 150)->nullable()->after('kpa_id')
        ->comment('Label jabatan KPA yang tercetak');
      $table->foreignId('bendahara_id')->nullable()->after('kpa_label')
        ->comment('Bendahara pilihan untuk SPPD pegawai ini')
        ->constrained('users')->nullOnDelete();
    });

    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->foreignId('kpa_id')->nullable()->after('pptk_id')
        ->comment('Penandatangan Setuju Bayar (KPA) SPPD ini')
        ->constrained('users')->nullOnDelete();
      $table->string('kpa_label', 150)->nullable()->after('kpa_id')
        ->comment('Label KPA tercetak; null = label bawaan dokumen lama (Pengguna Anggaran / nama jabatan)');
      $table->foreignId('bendahara_id')->nullable()->after('kpa_label')
        ->comment('Bendahara SPPD ini')
        ->constrained('users')->nullOnDelete();
      $table->string('bendahara_label', 150)->nullable()->after('bendahara_id')
        ->comment('Jabatan bendahara tercetak, dicatat saat dipilih');
    });

    if (Schema::hasColumn('departments', 'setuju_bayar_user_id')) {
      $this->departments = DB::table('departments')
        ->get(['id', 'parent_id', 'type', 'can_view_children_data', 'setuju_bayar_user_id', 'setuju_bayar_label'])
        ->keyBy('id');
      $this->recordExistingSppds();

      Schema::table('departments', function (Blueprint $table) {
        $table->dropConstrainedForeignId('setuju_bayar_user_id');
        $table->dropColumn('setuju_bayar_label');
      });
    }
  }

  public function down(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->foreignId('setuju_bayar_user_id')->nullable()->after('parent_id')
        ->comment('Penandatangan Setuju Bayar pada dokumen cetak; null = fallback kepala_opd')
        ->constrained('users')->nullOnDelete();
      $table->string('setuju_bayar_label', 150)->nullable()->after('setuju_bayar_user_id')
        ->comment('Label jabatan penandatangan Setuju Bayar; null = label bawaan');
    });

    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->dropConstrainedForeignId('kpa_id');
      $table->dropConstrainedForeignId('bendahara_id');
      $table->dropColumn(['kpa_label', 'bendahara_label']);
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('pptk_id');
      $table->dropConstrainedForeignId('kpa_id');
      $table->dropConstrainedForeignId('bendahara_id');
      $table->dropColumn('kpa_label');
    });
  }

  private function recordExistingSppds(): void
  {
    DB::table('sppd_requests')->select(['id', 'user_id'])->chunkById(200, function (Collection $sppds) {
      $units = DB::table('users')->whereIn('id', $sppds->pluck('user_id')->unique())->pluck('department_id', 'id');
      foreach ($sppds as $sppd) {
        $unit = $this->departments->get($units->get($sppd->user_id));
        if (! $unit) {
          continue;
        }

        [$kpaId, $kpaLabel] = $this->legacySetujuBayar($unit);
        $bendaharaId = $this->legacyBendahara($unit);

        DB::table('sppd_requests')->where('id', $sppd->id)->update([
          'kpa_id' => $kpaId,
          'kpa_label' => $kpaLabel,
          'bendahara_id' => $bendaharaId,
          'bendahara_label' => $bendaharaId ? 'Bendahara Pengeluaran' : null,
        ]);
      }
    });
  }

  /**
   * Department::resolveSetujuBayar sebelum migrasi: pengaturan terdekat dari unit pelaksana sampai
   * root zona data (pegawai nonaktif dilewati; label dicari terpisah), lalu Kepala OPD aktif di root.
   *
   * @return array{0: ?int, 1: ?string}
   */
  private function legacySetujuBayar(object $unit): array
  {
    $root = $this->scopeRoot($unit);
    $userId = null;
    $label = null;
    for ($dept = $unit; $dept; $dept = $dept->id === $root->id ? null : $this->departments->get($dept->parent_id)) {
      if (! $userId && $dept->setuju_bayar_user_id
        && DB::table('users')->where('id', $dept->setuju_bayar_user_id)->where('is_active', true)->exists()) {
        $userId = (int) $dept->setuju_bayar_user_id;
      }
      if ($label === null && $dept->setuju_bayar_label) {
        $label = $dept->setuju_bayar_label;
      }
    }

    $userId ??= DB::table('users')
      ->join('model_has_roles', fn ($join) => $join->on('model_has_roles.model_id', '=', 'users.id')
        ->where('model_has_roles.model_type', 'App\\Models\\User'))
      ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
      ->where('roles.name', 'kepala_opd')
      ->where('users.department_id', $root->id)
      ->where('users.is_active', true)
      ->orderBy('users.id')
      ->value('users.id');

    return [$userId, $label];
  }

  /** Bendahara yang tercetak sebelum migrasi: jabatan "Bendahara Pengeluaran" di unit pelaksana, lalu induknya. */
  private function legacyBendahara(object $unit): ?int
  {
    foreach (array_unique(array_filter([$unit->id, $unit->parent_id])) as $departmentId) {
      $id = DB::table('users')
        ->join('positions', 'positions.id', '=', 'users.position_id')
        ->where('positions.name', 'Bendahara Pengeluaran')
        ->where('users.is_active', true)
        ->where('users.department_id', $departmentId)
        ->orderBy('users.id')
        ->value('users.id');
      if ($id) {
        return (int) $id;
      }
    }

    return null;
  }

  /** Department::getScopeRootDepartment. */
  private function scopeRoot(object $dept): object
  {
    while ($dept->parent_id && ! in_array($dept->type, self::INDEPENDENT_ZONES, true)) {
      $parent = $this->departments->get($dept->parent_id);
      if (! $parent || ! $parent->can_view_children_data) {
        break;
      }
      $dept = $parent;
    }

    return $dept;
  }
};
