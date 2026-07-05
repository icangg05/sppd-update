<?php

namespace App\Livewire\Secretariat;

use App\Enums\SppdStatus;
use App\Livewire\Concerns\ScopesSppd;
use App\Models\SppdApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Monitoring Tindak Lanjut SPPD OPD — daftar kerja sekretaris/sekretariat untuk
 * mengejar kelengkapan administrasi perjalanan dinas dalam lingkup OPD-nya:
 * pegawai yang sedang dinas, SPPD selesai yang belum berlaporan, dan yang belum
 * berrealisasi biaya. Berbeda dari Daftar SPPD (index) yang menampilkan semua data.
 */
#[Layout('layouts.app')]
class OpdMonitoring extends Component
{
  use WithPagination;
  use ScopesSppd;

  /** Tab aktif: ongoing | no_report | no_expense */
  #[Url(keep: true)]
  public string $tab = 'ongoing';

  #[Url(keep: true)]
  public string $search = '';

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function setTab(string $tab): void
  {
    $this->tab = in_array($tab, ['ongoing', 'no_report', 'no_expense'], true) ? $tab : 'ongoing';
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->resetPage();
  }

  /** Pegawai yang sedang dalam periode perjalanan (disetujui & tanggal mencakup hari ini). */
  private function scopeOngoing(Builder $q): Builder
  {
    return $q->where('status', SppdStatus::APPROVED)
      ->whereDate('start_date', '<=', today())
      ->whereDate('end_date', '>=', today());
  }

  /** Perjalanan sudah lewat tapi laporan belum diunggah. */
  private function scopeNoReport(Builder $q): Builder
  {
    return $q->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED])
      ->whereDate('end_date', '<', today())
      ->where(function ($sub) {
        $sub->whereDoesntHave('report')
          ->orWhereHas('report', fn ($r) => $r->whereNull('report_file'));
      });
  }

  /** Perjalanan sudah lewat tapi realisasi biaya belum diinput. */
  private function scopeNoExpense(Builder $q): Builder
  {
    return $q->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED])
      ->whereDate('end_date', '<', today())
      ->whereDoesntHave('actualExpenses');
  }

  private function applyTab(Builder $q, string $tab): Builder
  {
    return match ($tab) {
      'no_report'  => $this->scopeNoReport($q),
      'no_expense' => $this->scopeNoExpense($q),
      default      => $this->scopeOngoing($q),
    };
  }

  public function render()
  {
    /** @var \App\Models\User $user */
    $user = Auth::user();

    $counts = [
      'ongoing'    => $this->scopeOngoing($this->scopedSppd($user))->count(),
      'no_report'  => $this->scopeNoReport($this->scopedSppd($user))->count(),
      'no_expense' => $this->scopeNoExpense($this->scopedSppd($user))->count(),
      'my_pending' => SppdApproval::readyForApprover($user->id)->count(),
    ];

    $query = $this->applyTab($this->scopedSppd($user), $this->tab)
      ->with(['user.department', 'destinations.regency', 'report']);

    if ($this->search !== '') {
      $search = $this->search;
      $query->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
      });
    }

    $sppds = $query->latest('end_date')->paginate(15)->onEachSide(1);

    return view('livewire.secretariat.opd-monitoring', [
      'sppds'  => $sppds,
      'counts' => $counts,
    ])->title('Monitoring Tindak Lanjut SPPD');
  }
}
