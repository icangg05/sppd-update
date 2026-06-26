<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Province;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProvinceIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';

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

  public function openCreateModal(): void
  {
    $this->reset(['name', 'editingId']);
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function openEditModal(int $id): void
  {
    $province = Province::findOrFail($id);

    $this->editingId = $province->id;
    $this->name      = $province->name;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    $this->validate([
      'name' => ['required', 'string', 'max:255'],
    ]);

    // Cegah duplikasi nama provinsi (case-insensitive).
    $existing = Province::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->name))])->first();
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Provinsi \"{$existing->name}\" sudah terdaftar.");
      return;
    }

    if ($this->editingId) {
      Province::findOrFail($this->editingId)->update(['name' => trim($this->name)]);
      $message = 'Provinsi berhasil diperbarui.';
    } else {
      Province::create(['name' => trim($this->name)]);
      $message = 'Provinsi baru berhasil ditambahkan.';
    }

    $this->reset(['name', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $province = Province::find($id);
    if (! $province) {
      return;
    }

    $this->deletingId      = $province->id;
    $this->deletingName    = $province->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('province-delete-countdown');
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

    $province = Province::withCount('regencies')->findOrFail($this->deletingId);

    // Proteksi: provinsi yang masih punya kabupaten/kota tidak boleh dihapus.
    if ($province->regencies_count > 0) {
      $this->closeDeleteModal();
      $this->toastError("Gagal: provinsi \"{$province->name}\" masih memiliki {$province->regencies_count} kabupaten/kota.");
      return;
    }

    $name = $province->name;
    $province->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Provinsi \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $query = Province::withCount('regencies')->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    $provinces = $query->paginate(20)->onEachSide(1);

    return view('livewire.provinces.index', compact('provinces'))
      ->title('Kelola Data Provinsi');
  }
}
