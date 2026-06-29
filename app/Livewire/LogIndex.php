<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
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

  public function mount(string $scope = 'system'): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    $this->scope = $scope === 'tte' ? 'tte' : 'system';
  }

  public function isTte(): bool
  {
    return $this->scope === 'tte';
  }

  /**
   * Query dasar yang sudah difilter sesuai lingkup halaman.
   * - tte    : hanya log_name = 'tte'
   * - system : selain 'tte' (aktivitas data biasa)
   */
  protected function scopedQuery()
  {
    return Activity::query()->when(
      $this->isTte(),
      fn ($q) => $q->where('log_name', 'tte'),
      fn ($q) => $q->where(fn ($s) => $s->where('log_name', '!=', 'tte')->orWhereNull('log_name')),
    );
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
      ->when($this->event !== '', fn ($q) => $q->where('event', $this->event))
      ->latest()
      ->paginate(30)
      ->onEachSide(1);

    $eventOptions = $this->scopedQuery()
      ->select('event')->whereNotNull('event')->distinct()->orderBy('event')
      ->pluck('event')
      ->map(fn ($e) => ['value' => $e, 'label' => ucfirst($e)])
      ->prepend(['value' => '', 'label' => 'Semua Event'])
      ->values()
      ->all();

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

    return view('livewire.logs.index', compact('activities', 'eventOptions', 'totalLogs', 'signedUrls'))
      ->title($this->isTte() ? 'Logs TTE' : 'Logs Aktivitas');
  }
}
