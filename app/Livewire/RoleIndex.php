<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class RoleIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  // Konfirmasi hapus
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  public function mount(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->resetPage();
  }

  public function confirmDelete(int $id): void
  {
    $role = Role::find($id);
    if (! $role) {
      return;
    }

    // Role inti tidak boleh dihapus.
    if ($role->name === 'super_admin') {
      $this->toastError('Role super_admin tidak dapat dihapus.');
      return;
    }

    $this->deletingId      = $role->id;
    $this->deletingName    = $role->label ?? $role->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('role-delete-countdown');
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

    $role = Role::findOrFail($this->deletingId);

    if ($role->name === 'super_admin') {
      $this->closeDeleteModal();
      $this->toastError('Role super_admin tidak dapat dihapus.');
      return;
    }

    $name = $role->label ?? $role->name;
    $role->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Role \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $roles = Role::with('permissions')
      ->withCount('users')
      ->when($this->search !== '', function ($q) {
        $q->where(function ($sub) {
          $sub->where('name', 'like', "%{$this->search}%")
            ->orWhere('label', 'like', "%{$this->search}%");
        });
      })
      ->orderBy('name')
      ->paginate(25)
      ->onEachSide(1);

    return view('livewire.roles.index', compact('roles'))
      ->title('Kelola Role');
  }
}
