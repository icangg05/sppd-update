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
  public ?string $selectedLabel = null;
  public string $search = '';

  public function mount(SppdRequest $sppd): void
  {
    $this->sppd = $sppd;
    $this->pptk_id = $sppd->pptk_id;
    $this->selectedLabel = $sppd->pptk?->name;
  }

  /** Hanya admin OPD & super admin yang boleh mengubah PPTK. */
  public function canManage(): bool
  {
    return Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
  }

  /** ID department: OPD induk pelaksana + seluruh sub-unitnya. */
  protected function allowedDeptIds(): array
  {
    $dept = $this->sppd->user->department;

    if (! $dept) {
      return array_filter([$this->sppd->user->department_id]);
    }

    return $dept->getRootDepartment()->getAllRelatedIds()->all();
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

  public function selectPptk(int $id): void
  {
    // Resolusi label di server agar aman (validasi lingkup) & bebas masalah quoting.
    $user = $this->candidateQuery()->find($id, ['id', 'name', 'nip']);

    if (! $user) {
      return;
    }

    $this->pptk_id = $user->id;
    $this->selectedLabel = $user->name . ($user->nip ? ' (' . $user->nip . ')' : '');
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
    $this->selectedLabel = $this->sppd->pptk?->name;

    $this->toastSuccess('PPTK berhasil diperbarui.');
  }

  public function render()
  {
    $candidates = collect();

    if ($this->canManage()) {
      $candidates = $this->candidateQuery()
        ->when(trim($this->search) !== '', function ($q) {
          $term = trim($this->search);
          $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%"));
        })
        ->orderBy('name')
        ->limit(25)
        ->get(['id', 'name', 'nip']);
    }

    return view('livewire.sppd.pptk-selector', compact('candidates'));
  }
}
