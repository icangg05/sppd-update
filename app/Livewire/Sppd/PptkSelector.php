<?php

namespace App\Livewire\Sppd;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Pemilih PPTK pada halaman Laporan Pengeluaran Riil dengan pencarian
 * server-side. Kandidat dibatasi pada pegawai aktif di OPD induk pelaksana
 * (root department + seluruh sub-unitnya). Hanya admin OPD & super admin yang
 * boleh mengubah PPTK.
 */
class PptkSelector extends Component
{
  use InteractsWithToast;

  public SppdRequest $sppd;
  public ?int $pptk_id = null;

  public function mount(SppdRequest $sppd): void
  {
    $this->sppd = $sppd;
    $this->pptk_id = $sppd->pptk_id;
  }

  /** Hanya admin OPD & super admin yang boleh mengubah PPTK. */
  public function canManage(): bool
  {
    return Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
  }

  /** ID department: zona data OPD pelaksana (root zona + sub-unit yang berbagi data). */
  protected function allowedDeptIds(): array
  {
    $dept = $this->sppd->user->department;

    if (! $dept) {
      return array_filter([$this->sppd->user->department_id]);
    }

    return $dept->getScopeRootDepartment()->getScopedRelatedIds()->all();
  }

  /**
   * Query dasar kandidat PPTK: pegawai aktif di lingkup OPD induk pelaksana,
   * dengan mengecualikan akun administratif (admin OPD & super admin).
   */
  protected function candidateQuery()
  {
    return User::whereIn('department_id', $this->allowedDeptIds())
      ->where('is_active', true)
      ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin_opd', 'super_admin']));
  }

  public function updatePptk(): void
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($this->sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    $this->validate([
      'pptk_id' => [
        'required',
        // Pakai query kandidat yang sama agar role admin_opd/super_admin ditolak.
        fn ($attribute, $value, $fail) => $this->candidateQuery()->whereKey($value)->exists()
          ? null
          : $fail('PPTK yang dipilih tidak valid.'),
      ],
    ], [], ['pptk_id' => 'PPTK']);

    $this->sppd->update(['pptk_id' => $this->pptk_id]);
    $this->sppd->refresh()->load('pptk');

    // Beri tahu halaman induk agar tombol cetak per pegawai langsung aktif
    // tanpa reload (dari kondisi PPTK kosong → terisi).
    $this->dispatch('pptk-saved', hasPptk: true);

    $this->toastSuccess('PPTK berhasil diperbarui.');
  }

  public function render()
  {
    // Opsi diserahkan ke komponen searchable-select (filter di sisi klien).
    // Lingkup kandidat tetap dibatasi OPD induk pelaksana & difilter di server.
    $options = [];

    if ($this->canManage()) {
      $options = $this->candidateQuery()
        ->orderBy('name')
        ->get(['id', 'name', 'nip'])
        ->map(fn ($u) => [
          'value' => (string) $u->id,
          'label' => $u->name . ($u->nip ? ' — NIP. ' . $u->nip : ''),
        ])
        ->all();
    }

    return view('livewire.sppd.pptk-selector', compact('options'));
  }
}
