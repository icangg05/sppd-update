<?php

namespace App\Livewire;

use App\Enums\SppdStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdRequest;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class LogIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  // Lingkup halaman: 'tte' (khusus tanda tangan elektronik) atau 'system' (aktivitas lain).
  public string $scope = 'system';

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $event = '';

  // Konfirmasi bersihkan log
  public bool $showClearModal = false;

  // Cache lingkup department (untuk admin_opd) selama satu request.
  protected ?array $deptScopeCache = null;

  public function mount(string $scope = 'system'): void
  {
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $this->scope = $scope === 'tte' ? 'tte' : 'system';
  }

  public function isTte(): bool
  {
    return $this->scope === 'tte';
  }

  public function isSuperAdmin(): bool
  {
    return auth()->user()->hasRole('super_admin');
  }

  /**
   * Kumpulan id entitas milik department admin_opd (termasuk sub-unit).
   * Mengembalikan null untuk super_admin (tanpa batasan).
   *
   * @return array{deptIds: list<int>, userIds: list<int>, budgetIds: list<int>, sppdIds: list<int>}|null
   */
  protected function departmentScope(): ?array
  {
    if ($this->isSuperAdmin()) {
      return null;
    }

    if ($this->deptScopeCache !== null) {
      return $this->deptScopeCache;
    }

    /** @var User $user */
    $user = auth()->user();
    $dept = $user->department;
    $deptIds = $dept
      ? $dept->getAllRelatedIds()->map(fn ($id) => (int) $id)->all()
      : array_filter([(int) $user->department_id]);

    $userIds   = User::whereIn('department_id', $deptIds)->pluck('id')->all();
    $budgetIds = Budget::whereIn('department_id', $deptIds)->pluck('id')->all();
    $sppdIds   = SppdRequest::whereIn('user_id', $userIds ?: [0])->pluck('id')->all();

    return $this->deptScopeCache = compact('deptIds', 'userIds', 'budgetIds', 'sppdIds');
  }

  /**
   * Query dasar yang sudah difilter sesuai lingkup halaman.
   * - tte    : hanya log_name = 'tte'
   * - system : selain 'tte' (aktivitas data biasa)
   *
   * Untuk admin_opd, dibatasi hanya pada entitas milik department-nya.
   */
  protected function scopedQuery()
  {
    $query = Activity::query()->when(
      $this->isTte(),
      fn ($q) => $q->where('log_name', 'tte'),
      fn ($q) => $q->where(fn ($s) => $s->where('log_name', '!=', 'tte')->orWhereNull('log_name')),
    );

    $scope = $this->departmentScope();
    if ($scope !== null) {
      $query->where(function ($q) use ($scope) {
        if ($this->isTte()) {
          // Log TTE: subjek selalu SppdRequest milik pegawai department.
          $q->where('subject_type', SppdRequest::class)
            ->whereIn('subject_id', $scope['sppdIds'] ?: [0]);
        } else {
          $q->where(fn ($s) => $s->where('subject_type', User::class)->whereIn('subject_id', $scope['userIds'] ?: [0]))
            ->orWhere(fn ($s) => $s->where('subject_type', Budget::class)->whereIn('subject_id', $scope['budgetIds'] ?: [0]))
            ->orWhere(fn ($s) => $s->where('subject_type', SppdRequest::class)->whereIn('subject_id', $scope['sppdIds'] ?: [0]))
            ->orWhere(fn ($s) => $s->where('subject_type', Department::class)->whereIn('subject_id', $scope['deptIds'] ?: [0]));
        }
      });
    }

    // Sembunyikan log SPPD "disetujui" (perubahan status approved).
    // Log "ditolak" & revisi tetap tampil.
    if (! $this->isTte()) {
      $query->whereRaw(
        "NOT (subject_type = ? AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(attribute_changes, ?)), '') = ?)",
        [SppdRequest::class, '$.attributes.status', SppdStatus::APPROVED->value],
      );
    }

    return $query;
  }

  public function confirmClear(): void
  {
    $this->showClearModal = true;
    $this->dispatch('log-clear-countdown');
  }

  public function closeClearModal(): void
  {
    $this->showClearModal = false;
  }

  public function clearLogs(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $this->showClearModal = false;

    $count = $this->scopedQuery()->count();
    $this->scopedQuery()->delete();
    $this->resetPage();

    $label = $this->isTte() ? 'TTE' : 'aktivitas';
    $this->toastSuccess("Berhasil menghapus {$count} catatan {$label}.");
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedEvent(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->event  = '';
    $this->resetPage();
  }

  /**
   * Petakan nilai foreign key pada diff aktivitas menjadi nama yang terbaca.
   *
   * @return array{0: array<string, array{0: class-string, 1: string}>, 1: array<class-string, array<int, string>>}
   */
  protected function resolveForeignKeyNames($activities): array
  {
    // field => [model, kolom nama yang ditampilkan]
    $fkConfig = [
      'user_id'       => [\App\Models\User::class, 'name'],
      'pptk_id'       => [\App\Models\User::class, 'name'],
      'reviser_id'    => [\App\Models\User::class, 'name'],
      'creator_id'    => [\App\Models\User::class, 'name'],
      'head_id'       => [\App\Models\User::class, 'name'],
      'department_id' => [\App\Models\Department::class, 'name'],
      'parent_id'     => [\App\Models\Department::class, 'name'],
      'category_id'   => [\App\Models\SppdCategory::class, 'name'],
      'budget_id'     => [\App\Models\Budget::class, 'activity'],
      'rank_id'       => [\App\Models\Rank::class, 'name'],
      'position_id'   => [\App\Models\Position::class, 'name'],
    ];

    // Kumpulkan id per model dari kedua sisi diff (old & attributes).
    $idsByModel = [];
    foreach ($activities as $activity) {
      $changes = $activity->attribute_changes;
      foreach (['attributes', 'old'] as $side) {
        foreach ((array) data_get($changes, $side, []) as $field => $value) {
          if (isset($fkConfig[$field]) && is_numeric($value)) {
            $idsByModel[$fkConfig[$field][0]][] = (int) $value;
          }
        }
      }
    }

    $fkNames = [];
    foreach ($idsByModel as $model => $ids) {
      $col = collect($fkConfig)->firstWhere(fn ($c) => $c[0] === $model)[1];
      $fkNames[$model] = $model::query()
        ->whereIn('id', array_unique($ids))
        ->pluck($col, 'id')
        ->all();
    }

    return [$fkConfig, $fkNames];
  }

  /**
   * Pilihan event untuk dropdown, termasuk event sintetis SPPD (revisi/ditolak).
   *
   * @return list<array{value: string, label: string}>
   */
  protected function eventOptions(): array
  {
    // Event bawaan tetap memakai istilah Inggris (Created/Updated/Deleted/...).
    $options = $this->scopedQuery()
      ->select('event')->whereNotNull('event')->distinct()->orderBy('event')
      ->pluck('event')
      ->map(fn ($e) => ['value' => $e, 'label' => ucfirst($e)]);

    // Event sintetis khusus SPPD (hanya scope sistem) — sesuai badge di tabel.
    if (! $this->isTte()) {
      $options = $options
        ->push(['value' => 'revisi', 'label' => 'Revisi'])
        ->push(['value' => 'ditolak', 'label' => 'Ditolak']);
    }

    return $options->prepend(['value' => '', 'label' => 'Semua Event'])->values()->all();
  }

  /**
   * Terapkan filter event ke query, menangani event sintetis SPPD.
   */
  protected function applyEventFilter($query): void
  {
    match ($this->event) {
      'ditolak' => $query->where('subject_type', SppdRequest::class)->where('event', 'updated')
        ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(attribute_changes, ?)), '') = ?", ['$.attributes.status', SppdStatus::REJECTED->value]),
      'revisi'  => $query->where('subject_type', SppdRequest::class)->where('event', 'updated')
        ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(attribute_changes, ?)), '') <> ''", ['$.attributes.revision_note']),
      // Event 'updated' biasa: kecualikan SPPD yang berstatus ditolak.
      'updated' => $query->where('event', 'updated')
        ->whereRaw("NOT (subject_type = ? AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(attribute_changes, ?)), '') = ?)", [SppdRequest::class, '$.attributes.status', SppdStatus::REJECTED->value]),
      default   => $query->where('event', $this->event),
    };
  }

  public function render()
  {
    $activities = $this->scopedQuery()
      ->with(['causer', 'subject'])
      ->when($this->search !== '', function ($q) {
        $q->where(function ($sub) {
          $sub->where('description', 'like', "%{$this->search}%")
            ->orWhere('subject_type', 'like', "%{$this->search}%")
            ->orWhereHas('causer', function ($c) {
              $c->where('name', 'like', "%{$this->search}%");
            });
        });
      })
      ->when($this->event !== '', fn ($q) => $this->applyEventFilter($q))
      ->latest()
      ->paginate(30)
      ->onEachSide(1);

    $eventOptions = $this->eventOptions();

    $totalLogs = $this->scopedQuery()->count();

    // Untuk halaman TTE: petakan signature_id -> URL dokumen tertanda tangan.
    // Diresolusi saat tampil agar log lama (tanpa properti URL) tetap punya link.
    $signedUrls = [];
    if ($this->isTte()) {
      $ids = $activities->getCollection()
        ->map(fn ($a) => data_get($a->properties, 'signature_id'))
        ->filter()
        ->unique()
        ->all();

      if (! empty($ids)) {
        $signedUrls = \App\Models\SppdDigitalSignature::query()
          ->whereIn('id', $ids)
          ->get()
          ->mapWithKeys(fn ($sig) => [$sig->id => $sig->signed_file_url])
          ->filter()
          ->all();
      }
    }

    // Resolusi foreign key -> nama yang dapat dibaca (di-batch agar bebas N+1).
    [$fkConfig, $fkNames] = $this->resolveForeignKeyNames($activities->getCollection());

    return view('livewire.logs.index', compact('activities', 'eventOptions', 'totalLogs', 'signedUrls', 'fkConfig', 'fkNames'))
      ->title($this->isTte() ? 'Logs TTE' : 'Logs Aktivitas');
  }
}
