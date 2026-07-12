<?php

namespace App\Console\Commands;

use App\Enums\DprdJabatan;
use App\Enums\DprdPartai;
use App\Enums\EmployeeType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Migrasi data dari database lama (sppd-2026 / CodeIgniter, koneksi "mysql_old")
 * ke struktur baru. Saat ini: pegawai (table_pegawai -> users) dan DPA/anggaran
 * (table_anggaran -> budgets).
 *
 * Pemetaan SKPD lama -> department baru tidak deterministik (department dibersihkan
 * manual), jadi dipakai strategi berlapis: cocok kode -> cocok nama ternormalisasi
 * -> override manual. SKPD yang tetap tidak cocok dilaporkan.
 */
class ImportLegacyData extends Command
{
  protected $signature = 'app:import-legacy
    {--only=all : pegawai | dpa | dprd | all}
    {--years=2026 : Tahun anggaran yang diimpor untuk DPA, pisah koma. "all" untuk semua}
    {--password=password123 : Password default untuk semua pegawai yang diimpor}
    {--dry-run : Hitung & laporkan saja, tanpa menulis ke database}';

  protected $description = 'Impor data pegawai & DPA dari database lama (db_sppd) ke struktur baru';

  /**
   * Override manual skpd_id lama -> department_id baru, untuk SKPD yang direname/digabung
   * akibat reorganisasi OPD sehingga tidak cocok via kode maupun nama.
   * Beberapa adalah penilaian (mis. Bapenda -> Pajak/Retribusi, RSUD Antero Hamra -> RSUD
   * Kota Kendari, Dinas Pendidikan & Dinas Pemuda -> Dinas Pendidikan, Kepemudaan dan Olahraga).
   * Periksa kembali bila ragu.
   */
  private array $deptIdOverride = [
    5 => 5,     // Dinas Pemadam Kebakaran dan Penyelamatan -> Dinas Kebakaran
    247 => 91,  // Kelurahan Puunggaloba -> Kelurahan Punggaloba (beda ejaan)
    20 => 15,   // Badan Pendapatan Daerah -> Badan Pengelola Pajak dan Retribusi Daerah
    38 => 27,   // Dinas Pendidikan dan Kebudayaan -> Dinas Pendidikan, Kepemudaan dan Olahraga
    314 => 27,  // Dinas Pemuda dan Olahraga -> (digabung) Dinas Pendidikan, Kepemudaan dan Olahraga
    101 => 29,  // Dinas Ketahanan Pangan -> Dinas Pangan
    107 => 33,  // Dinas Perikanan -> Dinas Kelautan dan Perikanan
    108 => 34,  // Dinas Perdagangan Koperasi dan UMKM -> Dinas Perdagangan, Koperasi dan Ukm
    111 => 35,  // Dinas Pengendalian Penduduk dan KB -> Dinas Pengendalian Penduduk dan Kb
    296 => 39,  // Dinas Penanaman Modal dan PTSP -> Dinas Penanaman Modal dan PTSP
    297 => 39,
    318 => 1,   // Badan Pengelolaan Keuangan dan Aset Daerah -> BPKAD
    313 => 26,  // RSUD Antero Hamra -> Rumah Sakit Umum Daerah Kota Kendari
    // Puskesmas
    278 => 117, 283 => 120, 284 => 121, 294 => 127, 295 => 128, 306 => 115,
    // Bagian Setda
    193 => 158, 305 => 158, 194 => 162, 195 => 137, 196 => 175, 197 => 149,
    198 => 145, 199 => 171, 200 => 141, 201 => 133, 202 => 182, 203 => 154, 204 => 179,
  ];

  private array $deptMap = [];
  private array $deptUnmatched = [];
  private array $rankMap = [];
  private array $positionMap = [];

  public function handle(): int
  {
    $old = DB::connection('mysql_old');
    $dry = (bool) $this->option('dry-run');
    $only = $this->option('only');

    $this->info('Mengecek koneksi database lama...');
    try {
      $old->getPdo();
    } catch (\Throwable $e) {
      $this->error('Gagal konek ke koneksi "mysql_old": ' . $e->getMessage());
      return self::FAILURE;
    }

    $this->buildDepartmentMap($old);
    $this->buildRankMap();
    $this->buildPositionMap();

    if ($dry) {
      $this->warn('MODE DRY-RUN: tidak ada data yang ditulis.');
    }

    if (in_array($only, ['all', 'pegawai'], true)) {
      $this->importPegawai($old, $dry);
    }
    if (in_array($only, ['all', 'dpa'], true)) {
      $this->importDpa($old, $dry);
    }
    if (in_array($only, ['all', 'dprd'], true)) {
      $this->importDprd($old, $dry);
    }

    $this->reportUnmatchedDepartments();

    return self::SUCCESS;
  }

  // ──────────────────────────────────────────────────────────
  // Pemetaan referensi
  // ──────────────────────────────────────────────────────────

  private function buildDepartmentMap($old): void
  {
    $departments = DB::table('departments')->get(['id', 'name', 'code']);
    $byCode = [];
    $byName = [];
    foreach ($departments as $d) {
      if (!empty($d->code)) $byCode[trim($d->code)] = $d->id;
      $byName[$this->normalize($d->name)] = $d->id;
    }

    $skpds = $old->table('table_skpd')->get(['skpd_id', 'skpd_kode', 'skpd_nama']);
    foreach ($skpds as $s) {
      $id = null;
      $code = trim((string) $s->skpd_kode);
      $nama = trim((string) $s->skpd_nama);

      if (isset($this->deptIdOverride[$s->skpd_id])) {
        $id = $this->deptIdOverride[$s->skpd_id];
      } elseif ($code !== '' && isset($byCode[$code])) {
        $id = $byCode[$code];
      } elseif (isset($byName[$this->normalize($nama)])) {
        $id = $byName[$this->normalize($nama)];
      }

      if ($id !== null) {
        $this->deptMap[$s->skpd_id] = $id;
      } else {
        $this->deptUnmatched[$s->skpd_id] = $nama;
      }
    }

    $this->line(sprintf(
      'Department: %d/%d SKPD lama terpetakan, %d tidak cocok.',
      count($this->deptMap),
      count($skpds),
      count($this->deptUnmatched)
    ));
  }

  private function buildRankMap(): void
  {
    foreach (DB::table('ranks')->get(['id', 'group']) as $r) {
      if (!empty($r->group)) $this->rankMap[strtoupper(trim($r->group))] = $r->id;
    }
  }

  private function buildPositionMap(): void
  {
    foreach (DB::table('positions')->get(['id', 'name']) as $p) {
      $this->positionMap[$this->normalize($p->name)] = $p->id;
    }
  }

  // ──────────────────────────────────────────────────────────
  // Impor pegawai -> users
  // ──────────────────────────────────────────────────────────

  private function importPegawai($old, bool $dry): void
  {
    $this->newLine();
    $this->info('== Impor Pegawai -> users ==');

    // Dedup per NIP: ambil baris pegawai_id terbesar (paling baru) untuk tiap NIP non-hapus.
    $latestIds = $old->table('table_pegawai')
      ->where('status_delete', 0)
      ->whereRaw("TRIM(pegawai_nip) <> ''")
      ->selectRaw('MAX(pegawai_id) AS id')
      ->groupBy('pegawai_nip')
      ->pluck('id');

    $this->line('NIP unik (non-hapus): ' . $latestIds->count());

    $existingNip = DB::table('users')->whereNotNull('nip')->pluck('nip')
      ->map(fn ($n) => trim((string) $n))->flip();

    $hash = Hash::make($this->option('password'));
    $now = now();
    $inserted = 0;
    $skippedExisting = 0;
    $noDept = 0;
    $noRank = 0;
    $noPosition = 0;

    foreach ($latestIds->chunk(500) as $chunk) {
      $rows = $old->table('table_pegawai')->whereIn('pegawai_id', $chunk)->get();
      $batch = [];
      foreach ($rows as $p) {
        $nip = trim((string) $p->pegawai_nip);
        if ($nip === '' || isset($existingNip[$nip])) {
          $skippedExisting++;
          continue;
        }
        $existingNip[$nip] = true; // cegah duplikat dalam batch yang sama

        $deptId = $this->deptMap[$p->skpd_id] ?? null;
        $rankId = $this->rankMap[strtoupper(trim((string) $p->pegawai_golongan))] ?? null;
        $posId = $this->matchPosition($p);

        if ($deptId === null) $noDept++;
        if ($rankId === null) $noRank++;
        if ($posId === null) $noPosition++;

        $batch[] = [
          'name'          => trim((string) $p->pegawai_nama) ?: $nip,
          'username'      => $nip,
          'nip'           => $nip,
          'nik'           => trim((string) ($p->pegawai_nik ?? '')) ?: null,
          'phone'         => trim((string) ($p->pegawai_nohp ?? '')) ?: null,
          'password'      => $hash,
          'employee_type' => 'pns',
          'department_id' => $deptId,
          'rank_id'       => $rankId,
          'position_id'   => $posId,
          'is_active'     => 1,
          'created_at'    => $now,
          'updated_at'    => $now,
        ];
        $inserted++;
      }

      if (!$dry && $batch) {
        DB::table('users')->insert($batch);
      }
    }

    $assignedRole = $dry ? 0 : $this->assignDefaultRole();

    $this->table(['Metrik', 'Jumlah'], [
      ['Akan diinsert', $inserted],
      ['Dilewati (NIP sudah ada)', $skippedExisting],
      ['Tanpa department', $noDept],
      ['Tanpa rank/golongan', $noRank],
      ['Tanpa position/jabatan', $noPosition],
      ['Diberi role default (staf)', $assignedRole],
    ]);
  }

  /** Beri role default "staf" ke semua user yang belum punya role. Mengembalikan jumlah yang diberi. */
  private function assignDefaultRole(): int
  {
    $roleId = DB::table('roles')->where('name', 'staf')->where('guard_name', 'web')->value('id');
    if (!$roleId) {
      $this->warn('Role "staf" tidak ditemukan, lewati penetapan role default.');
      return 0;
    }

    $assigned = DB::table('users')
      ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))->from('model_has_roles')
          ->whereColumn('model_has_roles.model_id', 'users.id')
          ->where('model_has_roles.model_type', User::class);
      })
      ->pluck('id')
      ->map(fn ($id) => ['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $id])
      ->all();

    foreach (array_chunk($assigned, 1000) as $chunk) {
      DB::table('model_has_roles')->insert($chunk);
    }

    if ($assigned) {
      app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    return count($assigned);
  }

  private function matchPosition($p): ?int
  {
    foreach ([$p->pegawai_namajabatan ?? '', $p->pegawai_jabatan ?? ''] as $cand) {
      $cand = trim((string) $cand);
      if ($cand === '') continue;
      $key = $this->normalize($cand);
      if (isset($this->positionMap[$key])) return $this->positionMap[$key];
    }
    return null;
  }

  // ──────────────────────────────────────────────────────────
  // Impor DPA/anggaran -> budgets
  // ──────────────────────────────────────────────────────────

  private function importDpa($old, bool $dry): void
  {
    $this->newLine();
    $this->info('== Impor DPA (table_anggaran) -> budgets ==');

    $jenisMap = [
      1 => 'Perjalanan Dinas Dalam Daerah',
      2 => 'Perjalanan Dinas Luar Daerah',
      3 => 'Bimbingan Teknis',
    ];

    $query = $old->table('table_anggaran');
    $yearsOpt = trim((string) $this->option('years'));
    if (strtolower($yearsOpt) !== 'all') {
      $years = array_filter(array_map('intval', explode(',', $yearsOpt)));
      $query->whereIn('tahun', $years);
      $this->line('Tahun: ' . implode(', ', $years));
    } else {
      $this->line('Tahun: SEMUA');
    }

    $rows = $query->get();
    $this->line('Baris anggaran sumber: ' . $rows->count());

    $inserted = 0;
    $skippedNoDept = 0;
    $skippedExisting = 0;
    $now = now();

    foreach ($rows as $a) {
      $deptId = $this->deptMap[$a->skpd_id] ?? null;
      if ($deptId === null) {
        $skippedNoDept++;
        continue;
      }

      $account = trim((string) ($a->no_rekening ?? '')) ?: null;
      $activity = trim((string) ($a->nama_kegiatan ?? '')) ?: null;
      $description = trim((string) ($a->uraian ?? '')) ?: ($activity ?? '-');

      // Idempotent: lewati jika budget identik sudah ada.
      $exists = DB::table('budgets')
        ->where('department_id', $deptId)
        ->where('year', (int) $a->tahun)
        ->where('description', $description)
        ->when($account !== null, fn ($q) => $q->where('account_code', $account))
        ->exists();
      if ($exists) {
        $skippedExisting++;
        continue;
      }

      if (!$dry) {
        DB::table('budgets')->insert([
          'department_id' => $deptId,
          'program'       => trim((string) ($a->nama_program ?? '')) ?: null,
          'activity'      => $activity,
          'account_code'  => $account,
          'description'   => $description,
          'type'          => $jenisMap[(int) $a->jenis_anggaran] ?? null,
          'source'        => 'APBD',
          'year'          => (int) $a->tahun,
          'total_amount'  => (float) $a->pagu,
          'created_at'    => $now,
          'updated_at'    => $now,
        ]);
      }
      $inserted++;
    }

    $this->table(['Metrik', 'Jumlah'], [
      ['Akan diinsert', $inserted],
      ['Dilewati (department tak cocok)', $skippedNoDept],
      ['Dilewati (sudah ada)', $skippedExisting],
    ]);
  }

  // ──────────────────────────────────────────────────────────
  // Impor Anggota DPRD (table_anggotadprd) -> users
  // ──────────────────────────────────────────────────────────

  /**
   * Anggota DPRD masuk ke tabel users yang sama (employee_type = 'dprd'),
   * di department Sekretariat DPRD (type=dprd). dprd_jabatan & partai dipetakan
   * ke enum DprdJabatan/DprdPartai; jabatan bebas yang tak terpetakan dibiarkan null.
   * Dedup berdasarkan nama ternormalisasi (anggota DPRD tidak punya NIP).
   */
  private function importDprd($old, bool $dry): void
  {
    $this->newLine();
    $this->info('== Impor Anggota DPRD (table_anggotadprd) -> users ==');

    $deptId = DB::table('departments')->where('type', 'dprd')->orderBy('id')->value('id');
    if (!$deptId) {
      $this->error('Department bertipe "dprd" tidak ditemukan, lewati impor DPRD.');
      return;
    }
    $this->line('Department DPRD: id ' . $deptId);

    $rolePimpinan = DB::table('roles')->where('name', 'pimpinan_dprd')->where('guard_name', 'web')->value('id');
    $roleAnggota  = DB::table('roles')->where('name', 'anggota_dprd')->where('guard_name', 'web')->value('id');

    // Dedup terhadap user DPRD yang sudah ada (mis. hasil seeder), berdasarkan nama.
    $existingNames = DB::table('users')
      ->where('employee_type', EmployeeType::DPRD->value)
      ->pluck('name')
      ->map(fn ($n) => $this->normalize((string) $n))
      ->flip();

    $usedUsernames = DB::table('users')->whereNotNull('username')->pluck('username')
      ->map(fn ($u) => strtolower(trim((string) $u)))->flip()->all();

    $rows = $old->table('table_anggotadprd')->where('status', 0)->get();
    $this->line('Baris DPRD sumber (status aktif): ' . $rows->count());

    $hash = Hash::make($this->option('password'));
    $now = now();
    $inserted = 0;
    $skippedExisting = 0;
    $noJabatan = 0;
    $roleAssignments = [];

    foreach ($rows as $r) {
      $name = trim((string) $r->anggotadprd_name);
      if ($name === '') continue;

      $key = $this->normalize($name);
      if (isset($existingNames[$key])) {
        $skippedExisting++;
        continue;
      }
      $existingNames[$key] = true; // cegah duplikat antar baris sumber

      $jabatan = $this->mapDprdJabatan((string) $r->anggotadprd_jabatan);
      $partai  = $this->mapDprdPartai((string) $r->anggotadprd_partai);
      if ($jabatan === null) $noJabatan++;

      $username = $this->uniqueUsername($name, $usedUsernames);

      if (!$dry) {
        $id = DB::table('users')->insertGetId([
          'name'          => $name,
          'username'      => $username,
          'password'      => $hash,
          'employee_type' => EmployeeType::DPRD->value,
          'department_id' => $deptId,
          'dprd_jabatan'  => $jabatan?->value,
          'partai'        => $partai?->value,
          'is_active'     => 1,
          'created_at'    => $now,
          'updated_at'    => $now,
        ]);

        // Pimpinan = Ketua/Wakil Ketua DPRD; selain itu anggota biasa.
        $roleId = in_array($jabatan, [DprdJabatan::KETUA, DprdJabatan::WAKIL_1, DprdJabatan::WAKIL_2], true)
          ? $rolePimpinan
          : $roleAnggota;
        if ($roleId) {
          $roleAssignments[] = ['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $id];
        }
      }
      $inserted++;
    }

    if (!$dry && $roleAssignments) {
      foreach (array_chunk($roleAssignments, 1000) as $chunk) {
        DB::table('model_has_roles')->insert($chunk);
      }
      app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    $this->table(['Metrik', 'Jumlah'], [
      ['Akan diinsert', $inserted],
      ['Dilewati (nama sudah ada)', $skippedExisting],
      ['Tanpa dprd_jabatan (null)', $noJabatan],
      ['Role DPRD diberikan', count($roleAssignments)],
    ]);
  }

  /** Petakan jabatan bebas lama ke enum DprdJabatan. Mengembalikan null bila tak terpetakan. */
  private function mapDprdJabatan(string $raw): ?DprdJabatan
  {
    $s = $this->normalize($raw); // huruf besar, alfanumerik+spasi

    // Pimpinan DPRD (mengandung "DPRD" agar tidak tertukar dgn pimpinan komisi).
    if (str_contains($s, 'KETUA DPRD')) return DprdJabatan::KETUA;
    if (str_contains($s, 'DPRD')) {
      if (str_contains($s, 'WAKIL KETUA II')) return DprdJabatan::WAKIL_2;
      if (str_contains($s, 'WAKIL KETUA I')) return DprdJabatan::WAKIL_1;
    }

    // Keanggotaan komisi: tentukan nomor komisi (I/II/III, angka terbesar dulu),
    // lalu tingkat jabatannya (Wakil > Sekretaris > Ketua > Anggota).
    $k = match (true) {
      (bool) (preg_match('/KOMISI?\s*III\b/', $s) || str_contains($s, 'KOMISI 3')) => 3,
      (bool) (preg_match('/KOMISI?\s*II\b/', $s) || str_contains($s, 'KOMISI 2')) => 2,
      (bool) (preg_match('/KOMISI?\s*I\b/', $s) || str_contains($s, 'KOMISI 1')) => 1,
      default => null,
    };
    if ($k !== null) {
      if (str_contains($s, 'WAKIL')) {
        return [1 => DprdJabatan::WAKIL_KOMISI_1, 2 => DprdJabatan::WAKIL_KOMISI_2, 3 => DprdJabatan::WAKIL_KOMISI_3][$k];
      }
      if (str_contains($s, 'SEKRETARIS')) {
        return [1 => DprdJabatan::SEKRETARIS_KOMISI_1, 2 => DprdJabatan::SEKRETARIS_KOMISI_2, 3 => DprdJabatan::SEKRETARIS_KOMISI_3][$k];
      }
      if (str_contains($s, 'KETUA')) {
        return [1 => DprdJabatan::KETUA_KOMISI_1, 2 => DprdJabatan::KETUA_KOMISI_2, 3 => DprdJabatan::KETUA_KOMISI_3][$k];
      }
      return [1 => DprdJabatan::KOMISI_1, 2 => DprdJabatan::KOMISI_2, 3 => DprdJabatan::KOMISI_3][$k];
    }

    if (str_contains($s, 'ANGGOTA')) return DprdJabatan::ANGGOTA;

    return null;
  }

  /** Petakan nama partai/fraksi bebas lama ke enum DprdPartai. */
  private function mapDprdPartai(string $raw): ?DprdPartai
  {
    $s = $this->normalize($raw); // mis. "F PDI P", "F GOLONGAN KARYA", "F PKS"

    if (str_contains($s, 'GOLONGAN KARYA') || str_contains($s, 'GOLKAR')) return DprdPartai::GOLKAR;
    if (str_contains($s, 'PDI') || str_contains($s, 'PDIP')) return DprdPartai::PDIP;
    if (str_contains($s, 'GERINDRA')) return DprdPartai::GERINDRA;
    if (str_contains($s, 'NASDEM')) return DprdPartai::NASDEM;
    if (str_contains($s, 'PERINDO')) return DprdPartai::PERINDO;
    if (str_contains($s, 'PKS')) return DprdPartai::PKS;
    if (str_contains($s, 'PKB')) return DprdPartai::PKB;
    if (str_contains($s, 'PAN')) return DprdPartai::PAN;
    if (str_contains($s, 'PPP')) return DprdPartai::PPP;
    if (str_contains($s, 'PSI')) return DprdPartai::PSI;
    if (str_contains($s, 'HANURA')) return DprdPartai::HANURA;
    if (str_contains($s, 'DEMOKRAT')) return DprdPartai::DEMOKRAT;

    return null;
  }

  /** Buat username unik dari nama (slug), hindari bentrok dengan yang sudah dipakai. */
  private function uniqueUsername(string $name, array &$used): string
  {
    $base = Str::slug($name, '_');
    if ($base === '') $base = 'dprd';
    $base = substr($base, 0, 40);

    $candidate = $base;
    $i = 1;
    while (isset($used[$candidate])) {
      $candidate = $base . '_' . (++$i);
    }
    $used[$candidate] = true;
    return $candidate;
  }

  // ──────────────────────────────────────────────────────────
  // Util
  // ──────────────────────────────────────────────────────────

  private function reportUnmatchedDepartments(): void
  {
    if (!$this->deptUnmatched) return;
    $this->newLine();
    $this->warn('SKPD lama yang TIDAK terpetakan ke department (data terkait dilewati / department null):');
    $rows = [];
    foreach ($this->deptUnmatched as $skpdId => $nama) {
      $rows[] = [$skpdId, $nama];
    }
    $this->table(['skpd_id', 'skpd_nama'], $rows);
    $this->line('Tambahkan padanan di $deptNameOverride pada command bila perlu, lalu jalankan ulang.');
  }

  /** Normalisasi nama untuk pencocokan: huruf besar, hanya alfanumerik+spasi, rapatkan spasi. */
  private function normalize(string $s): string
  {
    $s = strtoupper(trim($s));
    $s = preg_replace('/[^A-Z0-9]+/u', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
  }
}
