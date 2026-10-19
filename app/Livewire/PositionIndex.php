<?php

namespace App\Livewire;

use App\Enums\PositionScope;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class PositionIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $scopeFilter = '';

  // Tab sumber jabatan: '' semua, system (bawaan), manual (tambahan semua OPD,
  // khusus Super Admin), mine (input saya / OPD saya).
  #[Url(keep: true)]
  public string $sourceFilter = '';

  /**
   * Tab sumber yang tersedia. Admin OPD tidak melihat "Tambahan Semua OPD"
   * agar tidak tertukar dengan "Input OPD Saya".
   */
  private function availableSources(): array
  {
    return auth()->user()->hasRole('super_admin')
      ? ['', 'system', 'manual', 'mine']
      : ['', 'system', 'mine'];
  }

  // Konfirmasi hapus
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedScopeFilter(): void
  {
    $this->resetPage();
  }

  public function updatedSourceFilter(): void
  {
    $this->resetPage();
  }

  public function setSource(string $source): void
  {
    $this->sourceFilter = in_array($source, $this->availableSources(), true) ? $source : '';
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search       = '';
    $this->scopeFilter  = '';
    $this->sourceFilter = '';
    $this->resetPage();
  }

  #[On('position-saved')]
  public function onPositionSaved(): void
  {
    $this->resetPage();
  }

  public function confirmDelete(int $id): void
  {
    $position = Position::with('creator')->find($id);
    if (! $position) {
      return;
    }

    abort_unless($position->isManageableBy(auth()->user()), 403);

    $this->deletingId      = $position->id;
    $this->deletingName    = $position->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('position-delete-countdown');
  }

  public function closeDeleteModal(): void
  {
    $this->showDeleteModal = false;
    $this->deletingId      = null;
    $this->deletingName    = null;
  }

  public function delete(): void
  {
    $this->showDeleteModal = false;

    if (! $this->deletingId) {
      return;
    }

    $position = Position::with('creator')->withCount('users')->findOrFail($this->deletingId);
    abort_unless($position->isManageableBy(auth()->user()), 403);

    // Proteksi: jabatan yang masih dipakai pegawai tidak boleh dihapus.
    if ($position->users_count > 0) {
      $this->closeDeleteModal();
      $this->toastError("Gagal: jabatan \"{$position->name}\" masih dipakai {$position->users_count} pegawai.");
      return;
    }

    $name = $position->name;
    $position->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Jabatan \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $user         = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    // Filter pencarian & cakupan — dipakai bersama oleh daftar dan hitungan tab.
    $filtered = function () {
      return Position::query()
        ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
        ->when($this->scopeFilter !== '', fn ($q) => $q->where('uniqueness_scope', $this->scopeFilter));
    };

    // Super Admin: input akun sendiri. Admin OPD: input akun OPD-nya (sama dengan hak ubah).
    $applySource = function ($query, string $source) use ($user, $isSuperAdmin) {
      return match ($source) {
        'system' => $query->whereNull('created_by'),
        'manual' => $query->whereNotNull('created_by'),
        'mine'   => $isSuperAdmin
          ? $query->where('created_by', $user->id)
          : $query->whereHas('creator', fn ($q) => $q->where('department_id', $user->department_id ?? 0)),
        default  => $query,
      };
    };

    // Nilai dari URL yang tidak tersedia untuk peran ini dianggap "Semua".
    if (! in_array($this->sourceFilter, $this->availableSources(), true)) {
      $this->sourceFilter = '';
    }

    $sourceCounts = collect($this->availableSources())
      ->mapWithKeys(fn ($source) => [$source => $applySource($filtered(), $source)->count()]);

    // Jumlah pemangku hanya ditampilkan untuk Super Admin.
    $positions = $applySource($filtered(), $this->sourceFilter)
      ->with('creator.department')
      ->when($isSuperAdmin, fn ($q) => $q->withCount('users'))
      ->orderBy('level')
      ->orderBy('name')
      ->paginate(20)
      ->onEachSide(1);

    $scopes     = PositionScope::cases();
    $roleLabels = Role::pluck('label', 'name');

    return view('livewire.positions.index', compact('positions', 'scopes', 'roleLabels', 'isSuperAdmin', 'sourceCounts'))
      ->title('Kelola Data Jabatan');
  }
}
