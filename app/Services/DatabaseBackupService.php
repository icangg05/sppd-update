<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

  /** Default retensi bila config('backup.keep') tidak diset (1 per minggu). */
  public const MAX_BACKUPS = 8;

  /**
   * Buat backup untuk MINGGU berjalan; kembalikan nama file .sql.gz.
   *
   * Nama file deterministik per minggu (berbasis tanggal hari Minggu pembuka
   * minggu), sehingga membuat backup beberapa kali dalam minggu yang sama akan
   * MENIMPA file minggu itu — menjamin tepat satu backup per minggu.
   */
  public function create(?string $filename = null): string
  {
    @set_time_limit(0);

    $filename ??= $this->currentWeekFilename();
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

    // createFromTimestamp() default-nya UTC (Carbon 3); paksa ke timezone aplikasi
    // agar kolom "Dibuat" konsisten dengan zona waktu sistem (mis. WITA).
    $list = array_map(fn($path) => [
      'name' => basename($path),
      'size' => filesize($path),
      'created_at' => Carbon::createFromTimestamp(filemtime($path), config('app.timezone')),
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

  /** Sisakan hanya $keep backup terbaru; hapus selebihnya. */
  public function prune(?int $keep = null): int
  {
    $keep = $keep ?? (int) config('backup.keep', self::MAX_BACKUPS);
    $all = $this->all();
    $deleted = 0;

    foreach (array_slice($all, $keep) as $backup) {
      if ($this->delete($backup['name'])) {
        $deleted++;
      }
    }

    return $deleted;
  }

  // ───────────────────────── import (khusus development) ─────────────────────────

  /** Import hanya boleh di lingkungan pengembangan, tidak pernah di produksi. */
  public static function importEnabled(): bool
  {
    return app()->environment('local', 'development');
  }

  /**
   * Ganti SELURUH isi database dengan file .sql / .sql.gz.
   *
   * Urutan: file divalidasi & diekstrak dulu (database belum disentuh) → backup
   * pengaman dibuat → semua tabel dihapus → SQL dijalankan. Bila import gagal,
   * database dipulihkan otomatis dari backup pengaman.
   *
   * @return string nama file backup pengaman (tampil di daftar backup)
   */
  public function import(string $sourcePath): string
  {
    if (! self::importEnabled()) {
      throw new \RuntimeException('Import database hanya tersedia di mode development.');
    }

    @set_time_limit(0);

    $sqlPath = $this->extractSql($sourcePath);

    try {
      $safety = $this->create($this->safetyFilename());

      try {
        $this->replaceDatabaseWith($sqlPath);
      } catch (\Throwable $e) {
        Log::error('Import database gagal, memulihkan dari backup pengaman.', ['error' => $e->getMessage()]);

        try {
          $this->replaceDatabaseWith($this->extractSql($this->directory() . '/' . $safety));
        } catch (\Throwable $rollback) {
          throw new \RuntimeException(
            'Import gagal DAN pemulihan otomatis juga gagal. Pulihkan manual dari backup ' . $safety
              . '. Galat import: ' . $this->shortError($e) . ' Galat pemulihan: ' . $this->shortError($rollback),
            previous: $e
          );
        }

        throw new \RuntimeException(
          'Import gagal; database sudah dikembalikan seperti semula. Galat: ' . $this->shortError($e),
          previous: $e
        );
      }

      return $safety;
    } finally {
      @unlink($sqlPath);
    }
  }

  /** Nama backup pengaman sebelum import (lolos isValidName, ikut daftar backup). */
  private function safetyFilename(): string
  {
    $database = config('database.connections.mysql.database');

    return "backup-{$database}-pre-import-" . Carbon::now()->format('Y-m-d-His') . '.sql.gz';
  }

  /**
   * Ekstrak (bila gzip) ke file .sql sementara dan pastikan isinya dump SQL yang
   * memuat struktur tabel — supaya file salah ditolak SEBELUM database dihapus.
   */
  private function extractSql(string $sourcePath): string
  {
    $in = @fopen($sourcePath, 'rb');
    if ($in === false) {
      throw new \RuntimeException('File import tidak dapat dibaca.');
    }
    $magic = fread($in, 2);
    fclose($in);

    $tmpDir = storage_path('app/tmp');
    if (! is_dir($tmpDir)) {
      mkdir($tmpDir, 0775, true);
    }
    $sqlPath = tempnam($tmpDir, 'import-');

    $isGzip = $magic === "\x1f\x8b";
    $in = $isGzip ? gzopen($sourcePath, 'rb') : fopen($sourcePath, 'rb');
    $out = fopen($sqlPath, 'wb');

    $hasTable = false;
    $tail = '';
    try {
      while (true) {
        $chunk = $isGzip ? gzread($in, 1 << 20) : fread($in, 1 << 20);
        if ($chunk === false) {
          throw new \RuntimeException('File import rusak (gagal diekstrak).');
        }
        if ($chunk === '') {
          break;
        }
        fwrite($out, $chunk);

        // Sisa 20 byte potongan sebelumnya ikut diperiksa agar kata yang terbelah tetap terdeteksi.
        $hasTable = $hasTable || stripos($tail . $chunk, 'CREATE TABLE') !== false;
        $tail = substr($chunk, -20);
      }
    } catch (\Throwable $e) {
      fclose($out);
      @unlink($sqlPath);
      throw $e;
    } finally {
      $isGzip ? gzclose($in) : fclose($in);
    }
    fclose($out);

    if (! $hasTable) {
      @unlink($sqlPath);
      throw new \RuntimeException('File bukan dump database: tidak ada perintah CREATE TABLE.');
    }

    return $sqlPath;
  }

  /** Hapus semua tabel/view lalu jalankan file SQL. */
  private function replaceDatabaseWith(string $sqlPath): void
  {
    Schema::dropAllViews();
    Schema::dropAllTables();

    if ($this->mysqlClientAvailable()) {
      $this->runWithMysqlClient($sqlPath);
    } else {
      // Cadangan tanpa binary: cukup untuk dump buatan aplikasi (tanpa DELIMITER).
      DB::unprepared(file_get_contents($sqlPath));
    }

    DB::reconnect();
    // Cache (mis. permission Spatie, pengaturan) masih berisi data database lama.
    Cache::flush();
  }

  private function mysqlClientAvailable(): bool
  {
    $probe = new Process(['bash', '-c', 'command -v mysql']);
    $probe->run();

    return $probe->isSuccessful();
  }

  /**
   * Jalankan SQL lewat klien mysql (mendukung DELIMITER dari mysqldump). Klien
   * MariaDB memaksa verifikasi TLS yang gagal pada sertifikat self-signed MySQL
   * di container, jadi TLS dimatikan (jaringan internal, khusus development).
   */
  private function runWithMysqlClient(string $sqlPath): void
  {
    $c = config('database.connections.mysql');

    $version = new Process(['mysql', '--version']);
    $version->run();
    $noTls = str_contains($version->getOutput(), 'MariaDB') ? '--skip-ssl' : '--ssl-mode=DISABLED';

    $process = new Process([
      'mysql', $noTls, '--default-character-set=utf8mb4',
      '-h', (string) $c['host'], '-P', (string) $c['port'], '-u', (string) $c['username'],
      (string) $c['database'],
    ], null, ['MYSQL_PWD' => (string) $c['password']], null, 1800);

    $input = fopen($sqlPath, 'rb');
    try {
      $process->setInput($input);
      $process->run();
    } finally {
      if (is_resource($input)) {
        fclose($input);
      }
    }

    if (! $process->isSuccessful()) {
      // Klien mysql mengulang potongan SQL di antara garis "-----"; cukup ambil baris ERROR-nya.
      $lines = array_filter(array_map('trim', explode("\n", $process->getErrorOutput())));
      $error = collect($lines)->last(fn($line) => str_starts_with($line, 'ERROR')) ?? end($lines);

      throw new \RuntimeException($error ?: 'Klien mysql berhenti dengan kode ' . $process->getExitCode() . '.');
    }
  }

  private function shortError(\Throwable $e): string
  {
    return \Illuminate\Support\Str::limit($e->getMessage(), 300);
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
