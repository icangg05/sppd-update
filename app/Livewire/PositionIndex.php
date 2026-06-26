<?php

namespace App\Livewire;

use App\Enums\PositionScope;
use App\Helpers\SmartTitle;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PositionIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $scopeFilter = '';

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';
  public int $level = 1000;
  public string $scope = PositionScope::NONE->value;

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

  public function updatedScopeFilter(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search      = '';
    $this->scopeFilter = '';
    $this->resetPage();
  }

  public function openCreateModal(): void
  {
    $this->reset(['name', 'level', 'scope', 'editingId']);
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function openEditModal(int $id): void
  {
    $position = Position::findOrFail($id);

    $this->editingId = $position->id;
    $this->name      = $position->name;
    $this->level     = $position->level;
    $this->scope     = ($position->uniqueness_scope ?? PositionScope::NONE)->value;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    $this->validate([
      'name'  => ['required', 'string', 'max:255'],
      'level' => ['required', 'integer', 'min:0', 'max:9999'],
      'scope' => ['required', 'in:' . implode(',', array_column(PositionScope::cases(), 'value'))],
    ]);

    // Cegah duplikasi nama jabatan (case-insensitive).
    $existing = Position::findByName($this->name);
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Jabatan \"{$existing->name}\" sudah terdaftar.");
      return;
    }

    $data = [
      'name'             => SmartTitle::convert(trim($this->name)),
      'level'            => $this->level,
      'uniqueness_scope' => $this->scope,
    ];

    if ($this->editingId) {
      Position::findOrFail($this->editingId)->update($data);
      $message = 'Jabatan berhasil diperbarui.';
    } else {
      Position::create($data);
      $message = 'Jabatan baru berhasil ditambahkan.';
    }

    $this->reset(['name', 'level', 'scope', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $position = Position::find($id);
    if (! $position) {
      return;
    }

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

    $position = Position::withCount('users')->findOrFail($this->deletingId);

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
    $query = Position::withCount('users')->orderBy('level')->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->scopeFilter !== '') {
      $query->where('uniqueness_scope', $this->scopeFilter);
    }

    $positions = $query->paginate(20)->onEachSide(1);
    $scopes    = PositionScope::cases();

    return view('livewire.positions.index', compact('positions', 'scopes'))
      ->title('Kelola Data Jabatan');
  }
}
