<?php

namespace App\Livewire\Leadership;

use App\Enums\ApprovalStatus;
use App\Models\SppdApproval;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Riwayat Persetujuan/TTE — jejak keputusan (setuju/tolak) & langkah TTE
 * yang telah dilakukan pejabat penyetuju yang sedang login.
 */
#[Layout('layouts.app')]
class ApprovalHistory extends Component
{
  use WithPagination;

  #[Url(keep: true)]
  public string $search = '';

  /** '', 'approved', atau 'rejected'. */
  #[Url(keep: true)]
  public string $decision = '';

  /** Tahun perjalanan (start_date SPPD), '' = semua. */
  #[Url(keep: true)]
  public string $year = '';

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedYear(): void
  {
    $this->resetPage();
  }

  public function updatedDecision(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->decision = '';
    $this->year = '';
    $this->resetPage();
  }

  public function render()
  {
    $decided = [ApprovalStatus::APPROVED->value, ApprovalStatus::REJECTED->value];

    $query = SppdApproval::query()
      ->with(['sppdRequest.user.department', 'sppdRequest.destinations.regency'])
      ->where('approver_id', Auth::id())
      ->whereIn('status', $decided);

    if ($this->decision !== '' && in_array($this->decision, $decided, true)) {
      $query->where('status', $this->decision);
    }

    if ($this->year !== '') {
      $year = $this->year;
      $query->whereHas('sppdRequest', fn ($q) => $q->whereYear('start_date', $year));
    }

    if ($this->search !== '') {
      $search = $this->search;
      $query->whereHas('sppdRequest', function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
      });
    }

    $approvals = $query
      ->orderByDesc('acted_at')
      ->orderByDesc('id')
      ->paginate(15)
      ->onEachSide(1);

    // Tahun diambil dari riwayat pejabat ini saja agar tak menawarkan tahun kosong.
    $yearOptions = collect([['value' => '', 'label' => 'Semua Tahun']])
      ->concat(
        SppdApproval::query()
          ->where('approver_id', Auth::id())
          ->whereIn('sppd_approvals.status', $decided)
          ->join('sppd_requests', 'sppd_requests.id', '=', 'sppd_approvals.sppd_request_id')
          ->selectRaw('DISTINCT YEAR(sppd_requests.start_date) as y')
          ->orderByDesc('y')
          ->pluck('y')
          ->map(fn ($y) => ['value' => (string) $y, 'label' => (string) $y])
      )
      ->values()
      ->all();

    return view('livewire.leadership.approval-history', [
      'approvals' => $approvals,
      'yearOptions' => $yearOptions,
    ])->title('Riwayat Persetujuan');
  }
}
