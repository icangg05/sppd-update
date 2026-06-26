<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateUsernames extends Command
{
  protected $signature = 'users:generate-usernames
                          {--dry-run : Tampilkan rencana perubahan tanpa menyimpan}
                          {--force : Jalankan tanpa konfirmasi}';

  protected $description = 'Generate ulang username semua user (format depan.belakang), kecuali role super_admin & admin_opd.';

  /** Role yang username-nya tidak boleh diubah. */
  private const PROTECTED_ROLES = ['super_admin', 'admin_opd'];

  public function handle(UsernameGenerator $generator): int
  {
    $dryRun = (bool) $this->option('dry-run');

    $users = User::whereDoesntHave('roles', function ($q) {
      $q->whereIn('name', self::PROTECTED_ROLES);
    })->orderBy('id')->get();

    if ($users->isEmpty()) {
      $this->info('Tidak ada user yang memenuhi kriteria.');
      return self::SUCCESS;
    }

    if (! $dryRun && ! $this->option('force')) {
      $this->warn("Ini akan mengubah username {$users->count()} user (kecuali role: "
        . implode(', ', self::PROTECTED_ROLES) . ').');
      if (! $this->confirm('Lanjutkan?')) {
        $this->info('Dibatalkan.');
        return self::SUCCESS;
      }
    }

    $changes = [];   // [id, name, old, new]
    $skipped = [];   // user tanpa nama valid

    // Transaksi: update dijalankan sungguhan agar generator melihat username
    // yang baru ditetapkan (uniqueness akurat lintas-batch). Pada dry-run,
    // transaksi di-rollback di akhir sehingga tidak ada yang tersimpan.
    DB::beginTransaction();
    try {
      foreach ($users as $user) {
        $old = $user->username;
        $new = $generator->generate($user->name ?? '', $user->id);

        if ($new === null) {
          $skipped[] = $user;
          continue;
        }

        if ($new === $old) {
          continue; // sudah sesuai
        }

        $user->update(['username' => $new]);
        $changes[] = [$user->id, $user->name, $old, $new];
      }

      $dryRun ? DB::rollBack() : DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      $this->error('Gagal: ' . $e->getMessage());
      return self::FAILURE;
    }

    if ($changes) {
      $this->table(['ID', 'Nama', 'Username Lama', 'Username Baru'], $changes);
    }

    $unchanged = $users->count() - count($changes) - count($skipped);
    $this->newLine();
    $this->info(($dryRun ? '[DRY-RUN] ' : '') . count($changes) . ' username diubah, '
      . "{$unchanged} sudah sesuai, " . count($skipped) . ' dilewati (nama tidak valid).');

    foreach ($skipped as $user) {
      $this->warn("  Dilewati #{$user->id}: nama kosong/tidak valid (\"{$user->name}\").");
    }

    if ($dryRun) {
      $this->comment('Mode dry-run: tidak ada perubahan disimpan. Jalankan tanpa --dry-run untuk menerapkan.');
    }

    return self::SUCCESS;
  }
}
