<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Menaikkan role pegawai aktif yang masih Staf agar sesuai jabatannya
 * (Position::roleForDepartment — mengikuti alur SPPD unit kerja). Pegawai
 * dengan role selain Staf tidak disentuh. Role kewenangan tunggal yang sudah
 * dipegang orang lain, atau diperebutkan beberapa kandidat di unit yang sama,
 * dilewati lalu dilaporkan.
 */
class SyncPositionRoles extends Command
{
  protected $signature = 'positions:sync-roles
    {--type=* : Jenis unit kerja yang diproses (mis. --type=kelurahan). Kosong = semua}
    {--position=* : Hanya jabatan dengan nama ini (mis. --position="Sekretaris Camat"). Kosong = semua}
    {--dry-run : Tampilkan rencana perubahan tanpa menyimpan}';

  protected $description = 'Samakan role pegawai Staf dengan role jabatannya sesuai alur SPPD unit kerja';

  public function handle(): int
  {
    $types     = array_filter($this->option('type'));
    $positions = array_map('mb_strtolower', array_filter($this->option('position')));
    $dryRun    = (bool) $this->option('dry-run');

    $users = User::with(['position', 'department', 'roles'])
      ->where('is_active', true)
      ->whereHas('position', fn ($q) => $q->whereNotNull('default_role'))
      ->when($types, fn ($q) => $q->whereHas('department', fn ($d) => $d->whereIn('type', $types)))
      ->orderBy('department_id')
      ->orderBy('id')
      ->get();

    $existingRoles = Role::pluck('name')->all();

    // Tahap 1: kumpulkan kandidat (pegawai Staf yang role jabatannya berbeda).
    $candidates = [];
    foreach ($users as $user) {
      $current = $user->roles->first()?->name;
      if (($current !== null && $current !== 'staf') || $user->isDprdMember()) {
        continue;
      }

      $target = $user->position->roleForDepartment($user->department);
      if (! $target || $target === $current || ! in_array($target, $existingRoles, true)) {
        continue;
      }

      $perOpd = in_array($target, config('role_uniqueness.department', []), true);

      $candidates[] = [
        'user'     => $user,
        // Filter --position hanya memilih yang diubah; deteksi kandidat ganda tetap
        // melihat semua kandidat di unit agar bentrok tidak tersembunyi oleh filter.
        'selected' => ! $positions || in_array(mb_strtolower($user->position->name), $positions, true),
        'current'  => $current,
        'target'   => $target,
        'isSingle' => $perOpd || in_array($target, config('role_uniqueness.system', []), true),
        'key'      => $perOpd ? "{$target}|{$user->department_id}" : $target,
      ];
    }

    // Role tunggal yang diperebutkan beberapa kandidat sekaligus tidak diberikan ke
    // siapa pun — tidak bisa dipastikan mana pejabat yang sebenarnya.
    $contested = collect($candidates)->where('isSingle', true)->countBy('key')->filter(fn ($n) => $n > 1);

    // Tahap 2: terapkan, lewati yang bentrok.
    $changes   = [];
    $conflicts = [];
    foreach (array_filter($candidates, fn ($c) => $c['selected']) as $c) {
      $user = $c['user'];
      $row  = [$user->name, $user->department?->name ?? '-', $user->position->name, $c['current'] ?? '-', $c['target']];

      $reason = User::roleConflictHolder($c['target'], $user->department_id, $user->id)?->name;
      if (! $reason && $c['isSingle'] && $contested->has($c['key'])) {
        $reason = 'Lebih dari satu kandidat di unit ini';
      }

      if ($reason) {
        $conflicts[] = [...$row, $reason];
        continue;
      }

      if (! $dryRun) {
        $user->syncRoles([$c['target']]);
      }

      $changes[] = $row;
    }

    $this->info(($dryRun ? '[DRY RUN] Rencana perubahan: ' : 'Role diperbarui: ') . count($changes) . ' pegawai');
    if ($changes) {
      $this->table(['Nama', 'Unit Kerja', 'Jabatan', 'Role Lama', 'Role Baru'], $changes);
    }

    if ($conflicts) {
      $this->warn('Dilewati karena role tunggal bentrok di unit yang sama (perlu dicek manual): ' . count($conflicts));
      $this->table(['Nama', 'Unit Kerja', 'Jabatan', 'Role Lama', 'Role Tujuan', 'Alasan / Sudah Dipegang'], $conflicts);
    }

    return self::SUCCESS;
  }
}
