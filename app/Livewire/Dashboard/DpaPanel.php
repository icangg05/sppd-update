<?php

namespace App\Livewire\Dashboard;

use App\Enums\SppdStatus;
use App\Models\Budget;
use Livewire\Component;

class DpaPanel extends Component
{
  /** Ringkasan keseluruhan: year, pagu, realisasi, sisa, percentage. @var array<string,mixed> */
  public array $summary = [];

  /** Daftar per-item (program/kegiatan) — sudah berisi angka murah dari agregat. @var array<int,array> */
  public array $items = [];

  public string $title = 'DPA Tahun Berjalan';

  // Tampilkan asal OPD tiap item (khusus super_admin yang melihat lintas OPD).
  public bool $showDepartment = false;

  // Batas baris SPPD yang ditampilkan di modal agar tetap ringan
  // walau satu program punya ribuan SPPD.
  private const DETAIL_LIMIT = 20;

  // State modal rincian (di-query lazy saat diklik)
  public bool $showDetail = false;

  /** @var array<string,mixed>|null */
  public ?array $detail = null;

  /**
   * @param  array<string,mixed>  $summary
   * @param  array<int,array>  $items
   */
  public function mount(array $summary, array $items, string $title = 'DPA Tahun Berjalan', bool $showDepartment = false): void
  {
    $this->summary = $summary;
    $this->items = $items;
    $this->title = $title;
    $this->showDepartment = $showDepartment;
  }

  /**
   * Lazy-load rincian realisasi satu program/kegiatan saat tombol diklik.
   * Hanya di sinilah query "berat" (daftar SPPD pembentuk realisasi) dijalankan.
   */
  public function openDetail(int $budgetId): void
  {
    // Cegah IDOR: hanya budget yang ada di daftar (lingkup user) yang boleh dibuka.
    $item = collect($this->items)->firstWhere('id', $budgetId);
    if (! $item) {
      return;
    }

    $budget = Budget::find($budgetId);
    if (! $budget) {
      return;
    }

    $base = $budget->sppdRequests()
      ->whereIn('status', [SppdStatus::APPROVED->value, SppdStatus::COMPLETED->value]);

    // Jumlah total (1 query murah) — list hanya dibatasi top N kontributor.
    $total = (clone $base)->count();

    // Biaya per SPPD dihitung via subquery (withSum) — tidak memuat baris
    // cost_details/actual_expenses ke memori. Hanya 20 baris teratas diambil.
    $sppds = (clone $base)
      ->withSum('costDetails as cd_sum', 'total')
      ->withSum('actualExpenses as ae_sum', 'amount')
      ->with(['user', 'destinations.regency'])
      ->orderByRaw('(COALESCE(cd_sum, 0) + COALESCE(ae_sum, 0)) DESC')
      ->limit(self::DETAIL_LIMIT)
      ->get()
      ->map(fn ($s) => [
        'url'    => route('sppd.show', $s),
        'purpose' => $s->purpose,
        'user'   => $s->user->name ?? '-',
        'tujuan' => $s->destinations->first()?->regency?->name ?? '-',
        'status' => $s->status->label(),
        'color'  => $s->status->color(),
        'cost'   => (float) (($s->cd_sum ?? 0) + ($s->ae_sum ?? 0)),
      ])
      ->all();

    $this->detail = [
      'name'       => $item['name'],
      'department' => $item['department'] ?? null,
      'account'    => $item['account'] ?? null,
      'pagu'       => $item['pagu'],
      'realisasi'  => $item['realisasi'],
      'sisa'       => $item['sisa'],
      'percentage' => $item['percentage'],
      'sppds'      => $sppds,
      'shown'      => count($sppds),
      'total'      => $total,
    ];
    $this->showDetail = true;
  }

  public function closeDetail(): void
  {
    $this->showDetail = false;
    $this->detail = null;
  }

  public function render()
  {
    return view('livewire.dashboard.dpa-panel');
  }
}
