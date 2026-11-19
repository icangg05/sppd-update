<?php

namespace App\Console\Commands;

use App\Enums\ApprovalStatus;
use App\Enums\SppdStatus;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdRequest;
use App\Models\User;
use App\Support\PersonSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * Isi snapshot data orang (App\Support\PersonSnapshot) untuk SPPD yang sudah disetujui/selesai
 * sebelum fitur pembekuan ada. Hanya baris yang snapshot-nya masih kosong yang diisi, jadi aman
 * dijalankan ulang. Dipanggil oleh migrasi 2026_11_19_000002.
 *
 * Jabatan (position_id), pangkat, jabatan DPRD, dan role dipulihkan ke kondisi saat disetujui dari
 * log aktivitas: nilai pada waktu T = nilai lama ("old") dari perubahan pertama setelah T, atau nilai
 * sekarang bila tidak berubah sejak T. Nama & NIP selalu memakai data sekarang, karena perubahannya
 * di log hampir selalu koreksi (keputusan user 2026-11-19).
 *
 * Data yang sedang dilengkapi tidak dipulihkan ke kondisi belum lengkapnya: nilai yang KOSONG saat
 * disetujui (jabatan/pangkat belum diisi) memakai data sekarang, dan nama/level jabatan memakai
 * master Jabatan sekarang (ganti nama jabatan = perapian, mis. "Walikota" → "Wali Kota"). Yang
 * dipulihkan hanya pergantian sungguhan, mis. Kepala Dinas yang jabatannya dilepas saat Ganti
 * pejabat. Log yang sudah dipangkas (logs:prune) membuat nilainya jatuh ke data sekarang.
 */
class SnapshotSppdPeople extends Command
{
  protected $signature = 'sppd:snapshot-people {--dry-run : Hitung saja, tanpa menyimpan}';

  protected $description = 'Bekukan nama/NIP/pangkat/jabatan orang di SPPD yang sudah disetujui/selesai (yang belum punya snapshot)';

  /** @var array<string, Collection<int, Collection<int, Activity>>> log "updated"/"deleted" per subjek, urut lama → baru */
  private array $logs = [];

  /** @var array<int, ?Position> */
  private array $positions = [];

  /** @var array<int, ?Rank> */
  private array $ranks = [];

  public function handle(): int
  {
    $dryRun = (bool) $this->option('dry-run');
    $counts = ['sppd' => 0, 'penyetuju' => 0, 'pelaksana' => 0, 'pengikut' => 0];

    SppdRequest::query()
      ->whereIn('status', [SppdStatus::APPROVED->value, SppdStatus::COMPLETED->value])
      ->with(['user', 'followers.user', 'approvals.approver'])
      ->chunkById(100, function (Collection $sppds) use ($dryRun, &$counts) {
        foreach ($sppds as $sppd) {
          $counts['sppd']++;
          // Waktu SPPD disetujui penuh = tindakan persetujuan terakhir.
          $approvedAt = $sppd->approvals->pluck('acted_at')->filter()->max() ?? $sppd->updated_at;

          foreach ($sppd->approvals as $approval) {
            $acted = in_array($approval->status, [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED], true);
            if (! $acted || ! empty($approval->approver_snapshot) || ! $approval->approver) {
              continue;
            }
            $counts['penyetuju']++;
            if (! $dryRun) {
              $approval->approver_snapshot = $this->snapshotAt($approval->approver, $approval->acted_at ?? $approvedAt);
              $this->saveUntouched($approval);
            }
          }

          if (empty($sppd->pelaksana_snapshot) && $sppd->user) {
            $counts['pelaksana']++;
            if (! $dryRun) {
              $sppd->pelaksana_snapshot = $this->snapshotAt($sppd->user, $approvedAt);
              $this->saveUntouched($sppd);
            }
          }

          foreach ($sppd->followers as $follower) {
            if (! empty($follower->snapshot) || ! $follower->user) {
              continue;
            }
            $counts['pengikut']++;
            if (! $dryRun) {
              $follower->snapshot = $this->snapshotAt($follower->user, $approvedAt);
              $this->saveUntouched($follower);
            }
          }
        }
      });

    $this->info(sprintf(
      '%s %d SPPD diperiksa: %d penyetuju, %d pelaksana, %d pengikut %s.',
      $dryRun ? 'Dry-run:' : 'Selesai.',
      $counts['sppd'], $counts['penyetuju'], $counts['pelaksana'], $counts['pengikut'],
      $dryRun ? 'akan dibekukan' : 'dibekukan',
    ));

    return self::SUCCESS;
  }

  /**
   * Simpan snapshot tanpa event & tanpa mengubah updated_at: pengisian data lama bukan perubahan
   * SPPD (updated_at dipakai untuk urutan & tampilan "terakhir diperbarui").
   */
  private function saveUntouched(Model $model): void
  {
    $model->timestamps = false;
    $model->saveQuietly();
    $model->timestamps = true;
  }

  /** Data $user seperti pada waktu $at (lihat docblock kelas). */
  private function snapshotAt(User $user, ?CarbonInterface $at): array
  {
    $current = PersonSnapshot::fromUser($user);
    if (! $at) {
      return $current->toArray();
    }

    // Kosong saat disetujui = belum diisi waktu itu (dilengkapi belakangan): pakai data sekarang.
    $atApproval = function (string $field, mixed $current) use ($user, $at): mixed {
      $value = $this->valueAt(User::class, $user->id, $field, $at, $current);

      return filled($value) ? $value : $current;
    };

    $positionId = $atApproval('position_id', $user->position_id);
    $rankId = $atApproval('rank_id', $user->rank_id);
    $position = $positionId ? $this->position((int) $positionId) : null;
    $rank = $rankId ? $this->rank((int) $rankId) : null;

    // Role hanya tercatat di log saat Ganti pejabat ("role" berisi nama role dipisah koma).
    $roleLog = $this->valueAt(User::class, $user->id, 'role', $at, null);
    $roleNames = $roleLog !== null
      ? array_values(array_filter(array_map('trim', explode(',', (string) $roleLog))))
      : $current->roleNames;

    return (new PersonSnapshot(
      name: $current->name,
      nip: $current->nip,
      rankName: $rank?->name,
      rankGroup: $rank?->group,
      positionName: $positionId ? $this->positionValue((int) $positionId, $position, 'name') : null,
      positionLevel: $positionId ? $this->nullableInt($this->positionValue((int) $positionId, $position, 'level')) : null,
      roleNames: $roleNames,
      roleLabel: $roleLog !== null ? $this->roleLabel($user, $roleNames) : $current->roleLabel,
      dprdJabatan: $atApproval('dprd_jabatan', $user->dprd_jabatan),
    ))->toArray();
  }

  /** Nilai $field subjek pada waktu $at: "old" dari perubahan pertama setelah $at, atau $current. */
  private function valueAt(string $type, int $id, string $field, CarbonInterface $at, mixed $current): mixed
  {
    foreach ($this->logsOf($type, $id) as $log) {
      $old = $log->attribute_changes?->get('old') ?? [];
      if ($log->created_at > $at && is_array($old) && array_key_exists($field, $old)) {
        return $old[$field];
      }
    }

    return $current;
  }

  /** Nama/level jabatan menurut master Jabatan sekarang; jabatan yang sudah dihapus diambil dari log terakhirnya. */
  private function positionValue(int $id, ?Position $position, string $field): mixed
  {
    if ($position) {
      return $position->{$field};
    }

    $last = $this->logsOf(Position::class, $id)
      ->map(fn (Activity $log) => $log->attribute_changes?->get('attributes') ?? [])
      ->filter(fn ($attributes) => is_array($attributes) && array_key_exists($field, $attributes))
      ->last();

    return $last[$field] ?? null;
  }

  /** @return Collection<int, Activity> */
  private function logsOf(string $type, int $id): Collection
  {
    $this->logs[$type] ??= Activity::query()
      ->where('subject_type', $type)
      ->whereIn('event', ['updated', 'deleted'])
      ->orderBy('id')
      ->get(['id', 'subject_id', 'event', 'attribute_changes', 'created_at'])
      ->groupBy('subject_id');

    return $this->logs[$type]->get($id, collect());
  }

  private function position(int $id): ?Position
  {
    if (! array_key_exists($id, $this->positions)) {
      $this->positions[$id] = Position::find($id);
    }

    return $this->positions[$id];
  }

  private function rank(int $id): ?Rank
  {
    if (! array_key_exists($id, $this->ranks)) {
      $this->ranks[$id] = Rank::find($id);
    }

    return $this->ranks[$id];
  }

  /** @param list<string> $roleNames */
  private function roleLabel(User $user, array $roleNames): ?string
  {
    $role = $roleNames ? Role::where('name', $roleNames[0])->first() : null;

    return $role ? $user->roleLabelFor($role) : null;
  }

  private function nullableInt(mixed $value): ?int
  {
    return $value === null || $value === '' ? null : (int) $value;
  }
}
