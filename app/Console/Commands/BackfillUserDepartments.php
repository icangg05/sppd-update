<?php

namespace App\Console\Commands;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menetapkan department untuk pegawai (role "staf") yang masih department_id = null
 * akibat impor data lama (lihat ImportLegacyData). SKPD asal mereka tidak ada di
 * table_skpd legacy sehingga tidak bisa dipulihkan deterministik; dipakai heuristik
 * jabatan dari table_pegawai legacy:
 *   - Guru / Kepala Sekolah / Pengawas Sekolah -> Dinas Pendidikan
 *   - Tenaga kesehatan (bidan, perawat, dokter, apoteker, puskesmas, dll) -> Dinas Kesehatan
 *   - Sisanya / jabatan kosong / NIP tak ada di legacy -> department holding "Belum Ditentukan"
 *
 * Akun Super Admin (department_id null, bukan role staf) sengaja tidak disentuh.
 * Idempoten: hanya menyentuh user yang masih null.
 */
class BackfillUserDepartments extends Command
{
  protected $signature = 'app:backfill-departments {--dry-run : Hitung & laporkan saja, tanpa menulis}';

  protected $description = 'Tetapkan department untuk pegawai staf tanpa department via heuristik jabatan data lama';

  private const DEPT_PENDIDIKAN = 27; // Dinas Pendidikan, Kepemudaan dan Olahraga
  private const DEPT_KESEHATAN   = 25; // Dinas Kesehatan

  private const RE_PENDIDIKAN = '/\b(GURU|KEPALA SEKOLAH|PENGAWAS SEKOLAH|PAMONG)\b/';
  private const RE_KESEHATAN  = '/\b(BIDAN|PERAWAT|DOKTER|APOTEKER|NUTRISIONIS|SANITARIAN|GIZI|FARMASI|PUSKESMAS|KESEHATAN|MEDIS|RADIOGRAFER|PEREKAM MEDIS|ANALIS (LAB|KESEHATAN)|PRANATA LAB|EPIDEMIOLOG|PENYULUH KESEHATAN)\b/';

  public function handle(): int
  {
    $dry = (bool) $this->option('dry-run');

    $old = DB::connection('mysql_old');
    $this->info('Mengecek koneksi database lama...');
    try {
      $old->getPdo();
    } catch (\Throwable $e) {
      $this->error('Gagal konek ke koneksi "mysql_old": ' . $e->getMessage());
      return self::FAILURE;
    }

    if ($dry) {
      $this->warn('MODE DRY-RUN: tidak ada data yang ditulis.');
    }

    // 1. Department holding.
    $holdingId = $this->resolveHoldingDepartment($dry);

    // 2. Target: pegawai staf tanpa department & punya NIP.
    $targets = User::query()
      ->whereNull('department_id')
      ->whereNotNull('nip')
      ->where('nip', '<>', '')
      ->whereHas('roles', fn ($q) => $q->where('name', 'staf'))
      ->get(['id', 'nip']);

    $this->line('Pegawai staf tanpa department: ' . $targets->count());

    $counts = ['pendidikan' => 0, 'kesehatan' => 0, 'holding' => 0, 'nip_tak_ada' => 0];

    foreach ($targets->chunk(500) as $chunk) {
      $nips = $chunk->pluck('nip')->map(fn ($n) => trim((string) $n))->all();

      // Jabatan terbaru per NIP (baris pegawai_id terbesar, non-hapus).
      $latestIds = $old->table('table_pegawai')
        ->where('status_delete', 0)
        ->whereIn('pegawai_nip', $nips)
        ->selectRaw('MAX(pegawai_id) AS id')
        ->groupBy('pegawai_nip')
        ->pluck('id');

      $byNip = $old->table('table_pegawai')
        ->whereIn('pegawai_id', $latestIds)
        ->get(['pegawai_nip', 'pegawai_jabatan', 'pegawai_namajabatan'])
        ->keyBy(fn ($p) => trim((string) $p->pegawai_nip));

      foreach ($chunk as $user) {
        $nip = trim((string) $user->nip);
        $legacy = $byNip->get($nip);

        if (! $legacy) {
          $counts['nip_tak_ada']++;
          $deptId = $holdingId;
        } else {
          $jabatan = strtoupper(trim((string) ($legacy->pegawai_jabatan ?: $legacy->pegawai_namajabatan ?: '')));
          if ($jabatan !== '' && preg_match(self::RE_PENDIDIKAN, $jabatan)) {
            $deptId = self::DEPT_PENDIDIKAN;
            $counts['pendidikan']++;
          } elseif ($jabatan !== '' && preg_match(self::RE_KESEHATAN, $jabatan)) {
            $deptId = self::DEPT_KESEHATAN;
            $counts['kesehatan']++;
          } else {
            $deptId = $holdingId;
            $counts['holding']++;
          }
        }

        if (! $dry) {
          User::whereKey($user->id)->update(['department_id' => $deptId]);
        }
      }
    }

    $this->newLine();
    $this->table(['Tujuan', 'Jumlah'], [
      ['Dinas Pendidikan (27)', $counts['pendidikan']],
      ['Dinas Kesehatan (25)', $counts['kesehatan']],
      ['Holding "Belum Ditentukan" (jabatan lain/kosong)', $counts['holding']],
      ['  - di antaranya NIP tak ada di legacy', $counts['nip_tak_ada']],
      ['TOTAL diproses', array_sum([$counts['pendidikan'], $counts['kesehatan'], $counts['holding']])],
    ]);

    if ($dry) {
      $this->warn('Dry-run selesai. Jalankan tanpa --dry-run untuk menerapkan.');
    } else {
      $this->info('Selesai. Department pegawai telah diperbarui.');
    }

    return self::SUCCESS;
  }

  /** Pastikan department holding "Belum Ditentukan" ada; kembalikan id-nya. */
  private function resolveHoldingDepartment(bool $dry): int
  {
    $existing = Department::where('name', 'Belum Ditentukan')->first();
    if ($existing) {
      return $existing->id;
    }

    if ($dry) {
      $this->line('Department "Belum Ditentukan" belum ada (akan dibuat saat run sebenarnya).');
      return 0; // placeholder; tidak menulis di dry-run
    }

    $dept = Department::create([
      'name'      => 'Belum Ditentukan',
      'parent_id' => null,
      'code'      => null,
      'type'      => DepartmentType::OPD,
      'level'     => 0,
    ]);
    $this->line('Department "Belum Ditentukan" dibuat (id=' . $dept->id . ').');

    return $dept->id;
  }
}
