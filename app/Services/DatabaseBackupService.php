<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Membuat backup database dalam format SQL (di-gzip) tanpa bergantung pada
 * binary `mysqldump` — seluruh dump dibangun lewat PDO sehingga andal di
 * container manapun. Cocok untuk database berukuran kecil–menengah.
 *
 * Hasil disimpan di storage/app/backups/*.sql.gz.
 */
class DatabaseBackupService
{
  private const DIR = 'backups';

  /** Jumlah baris per statement INSERT (multi-row) agar file ringkas. */
  private const ROWS_PER_INSERT = 100;

  /** Simpan maksimal 8 backup (1 per minggu = 2 bulan terakhir). */
  public const MAX_BACKUPS = 8;

  /**
   * Buat backup untuk MINGGU berjalan; kembalikan nama file .sql.gz.
   *
   * Nama file deterministik per minggu (berbasis tanggal hari Minggu pembuka
   * minggu), sehingga membuat backup beberapa kali dalam minggu yang sama akan
   * MENIMPA file minggu itu — menjamin tepat satu backup per minggu.
   */
  public function create(): string
  {
    @set_time_limit(0);

    $filename = $this->currentWeekFilename();
    $dir = $this->directory();
    if (! is_dir($dir)) {
      mkdir($dir, 0775, true);
    }

    $path = $dir . '/' . $filename;

    // Utamakan mysqldump (cepat & ringan, andal untuk DB besar). Bila binary
    // tidak tersedia / gagal, jatuh ke dump pure-PHP sebagai cadangan.
    if ($this->mysqldumpAvailable()) {
      try {
        $this->dumpWithMysqldump($path);

        return $filename;
      } catch (\Throwable $e) {
        @unlink($path); // buang file parsial sebelum fallback
        Log::warning('mysqldump gagal, fallback ke dump pure-PHP.', ['error' => $e->getMessage()]);
      }
    }

    $this->dumpWithPhp($path);

    return $filename;
  }

  /** Apakah binary mysqldump tersedia di container ini? */
  private function mysqldumpAvailable(): bool
  {
    $probe = new Process(['bash', '-c', 'command -v mysqldump']);
    $probe->run();

    return $probe->isSuccessful();
  }

  /**
   * Dump cepat via mysqldump, di-pipe langsung ke gzip lalu ke file (hemat
   * memori, cocok untuk DB ratusan MB). Password dikirim lewat env MYSQL_PWD
   * agar tidak tampil di daftar proses. pipefail memastikan kegagalan mysqldump
   * (bukan hanya gzip) terdeteksi.
   */
  private function dumpWithMysqldump(string $path): void
  {
    $c = config('database.connections.mysql');

    $script = 'set -o pipefail; mysqldump'
      . ' --single-transaction --quick --no-tablespaces --routines --triggers --events'
      . ' --default-character-set=utf8mb4'
      . ' -h ' . escapeshellarg((string) $c['host'])
      . ' -P ' . escapeshellarg((string) $c['port'])
      . ' -u ' . escapeshellarg((string) $c['username']) . ' '
      . escapeshellarg((string) $c['database'])
      . ' | gzip -9 > ' . escapeshellarg($path);

    $process = new Process(['bash', '-c', $script], null, ['MYSQL_PWD' => (string) $c['password']], null, 1800);
    $process->mustRun();
  }

  /** Dump cadangan murni PHP (tanpa binary eksternal) bila mysqldump tak ada. */
  private function dumpWithPhp(string $path): void
  {
    $database = config('database.connections.mysql.database');

    $gz = gzopen($path, 'wb9');
    if ($gz === false) {
      throw new \RuntimeException('Tidak dapat membuat file backup.');
    }

    try {
      gzwrite($gz, $this->header($database));

      foreach ($this->tables() as $table) {
        gzwrite($gz, $this->structure($table));
        $this->writeData($gz, $table);
      }

      gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    } finally {
      gzclose($gz);
    }
  }

  /** Daftar backup yang tersimpan, terbaru di atas. */
  public function all(): array
  {
    $dir = $this->directory();
    if (! is_dir($dir)) {
      return [];
    }

    $files = glob($dir . '/*.sql.gz') ?: [];

    $list = array_map(fn($path) => [
      'name' => basename($path),
      'size' => filesize($path),
      'created_at' => Carbon::createFromTimestamp(filemtime($path)),
    ], $files);

    usort($list, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

    return $list;
  }

  public function path(string $filename): ?string
  {
    if (! $this->isValidName($filename)) {
      return null;
    }

    $path = $this->directory() . '/' . $filename;

    return is_file($path) ? $path : null;
  }

  public function delete(string $filename): bool
  {
    $path = $this->path($filename);

    return $path ? @unlink($path) : false;
  }

  /** Nama file backup untuk minggu berjalan (berbasis hari Minggu pembuka minggu). */
  public function currentWeekFilename(): string
  {
    $database = config('database.connections.mysql.database');
    $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');

    return "backup-{$database}-week-{$weekStart}.sql.gz";
  }

  /** Sisakan hanya $keep backup terbaru; hapus selebihnya (retensi 2 bulan). */
  public function prune(int $keep = self::MAX_BACKUPS): int
  {
    $all = $this->all();
    $deleted = 0;

    foreach (array_slice($all, $keep) as $backup) {
      if ($this->delete($backup['name'])) {
        $deleted++;
      }
    }

    return $deleted;
  }

  // ───────────────────────── internal ─────────────────────────

  private function directory(): string
  {
    return storage_path('app/' . self::DIR);
  }

  /** Hanya izinkan nama file backup yang sah (cegah path traversal). */
  private function isValidName(string $filename): bool
  {
    return $filename === basename($filename)
      && (bool) preg_match('/^backup-[\w.\-]+\.sql\.gz$/', $filename);
  }

  private function tables(): array
  {
    return array_map(fn($row) => array_values((array) $row)[0], DB::select('SHOW TABLES'));
  }

  private function header(string $database): string
  {
    return "-- Backup Database SPPD Kota Kendari\n"
      . "-- Database : {$database}\n"
      . "-- Dibuat   : " . Carbon::now()->toDateTimeString() . "\n\n"
      . "SET NAMES utf8mb4;\n"
      . "SET FOREIGN_KEY_CHECKS=0;\n\n";
  }

  private function structure(string $table): string
  {
    $row = (array) DB::select("SHOW CREATE TABLE `{$table}`")[0];

    // Tabel biasa memakai kunci 'Create Table'; view memakai 'Create View'.
    if (isset($row['Create View'])) {
      return "DROP VIEW IF EXISTS `{$table}`;\n{$row['Create View']};\n\n";
    }

    return "DROP TABLE IF EXISTS `{$table}`;\n{$row['Create Table']};\n\n";
  }

  /** Tulis data tabel sebagai INSERT multi-baris, di-stream agar hemat memori. */
  private function writeData($gz, string $table): void
  {
    $pdo = DB::getPdo();
    $columns = null;
    $batch = [];

    foreach (DB::table($table)->cursor() as $record) {
      $record = (array) $record;
      $columns ??= array_keys($record);

      $values = array_map(
        fn($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
        array_values($record)
      );
      $batch[] = '(' . implode(',', $values) . ')';

      if (count($batch) >= self::ROWS_PER_INSERT) {
        $this->flush($gz, $table, $columns, $batch);
        $batch = [];
      }
    }

    if ($batch) {
      $this->flush($gz, $table, $columns, $batch);
    }
  }

  private function flush($gz, string $table, array $columns, array $batch): void
  {
    $cols = '`' . implode('`,`', $columns) . '`';
    gzwrite($gz, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n\n");
  }
}
