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

  // Sumber jabatan — Super Admin: system / opd; Admin OPD: mine.
  #[Url(keep: true)]
  public string $sourceFilter = '';

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

    $query = Position::with('creator.department')
      ->withCount('users')
      ->orderBy('level')
      ->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->scopeFilter !== '') {
      $query->where('uniqueness_scope', $this->scopeFilter);
    }

    match (true) {
      $isSuperAdmin && $this->sourceFilter === 'system' => $query->whereNull('created_by'),
      $isSuperAdmin && $this->sourceFilter === 'opd'    => $query->whereNotNull('created_by'),
      ! $isSuperAdmin && $this->sourceFilter === 'mine' => $query->whereHas(
        'creator',
        fn ($q) => $q->where('department_id', $user->department_id ?? 0)
      ),
      default => null,
    };

    $positions  = $query->paginate(20)->onEachSide(1);
    $scopes     = PositionScope::cases();
    $roleLabels = Role::pluck('label', 'name');

    return view('livewire.positions.index', compact('positions', 'scopes', 'roleLabels', 'isSuperAdmin'))
      ->title('Kelola Data Jabatan');
  }
}
