<?php

namespace App\Livewire\Sppd;

use App\Livewire\Concerns\InteractsWithToast;
use App\Livewire\Concerns\SearchesEmployees;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * PPTK SPPD di Portal Dokumen: ringkasan + modal pengaturan. Kandidat dibatasi
 * pada pegawai aktif di OPD induk pelaksana (root department + seluruh sub-unitnya).
 * Hanya admin OPD & super admin yang boleh mengubah PPTK.
 */
class PptkSelector extends Component
{
  use InteractsWithToast;
  use SearchesEmployees;

  public SppdRequest $sppd;
  public ?int $pptk_id = null;
  public bool $showModal = false;
  /** Bertambah tiap modal dibuka: isi modal dibuat ulang (wire:key) agar pilihan yang batal disimpan tidak tersisa. */
  #[Locked]
  public int $opened = 0;

  public function mount(SppdRequest $sppd): void
  {
    $this->sppd = $sppd;
  }

  /** Pencarian kandidat PPTK di server (lihat x-form.searchable-select searchMethod). */
  #[Renderless]
  public function searchPptk(string $term = ''): array
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');

    return $this->employeeOptions($this->candidateQuery(), $term);
  }

  /** Buka modal dengan PPTK aktif terpilih. */
  public function open(): void
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');

    $this->pptk_id = $this->sppd->pptk_id;
    $this->opened++;
    $this->showModal = true;
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

    // Validasi manual → pesan lewat toast (bukan error inline).
    if (! $this->pptk_id) {
      $this->toastError('PPTK wajib dipilih terlebih dahulu.');
      return;
    }

    // Pakai query kandidat yang sama agar role admin_opd/super_admin ditolak.
    if (! $this->candidateQuery()->whereKey($this->pptk_id)->exists()) {
      $this->toastError('PPTK yang dipilih tidak valid.');
      return;
    }

    $this->sppd->update(['pptk_id' => $this->pptk_id]);
    $this->sppd->refresh()->load('pptk');

    $this->showModal = false;
    $this->toastSuccess('PPTK berhasil diperbarui.');
  }

  public function render()
  {
    // Kandidat dicari di server lewat searchPptk; di sini cukup label PPTK terpilih.
    $selected = $this->pptk_id ? User::find($this->pptk_id) : null;

    return view('livewire.sppd.pptk-selector', [
      'selectedLabel' => $selected ? static::employeeLabel($selected) : null,
    ]);
  }
}
