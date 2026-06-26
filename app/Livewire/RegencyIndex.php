<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdDestination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RegencyIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $provinceFilter = '';

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';
  public string $province_id = '';

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

  public function updatedProvinceFilter(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search         = '';
    $this->provinceFilter = '';
    $this->resetPage();
  }

  public function openCreateModal(): void
  {
    $this->reset(['name', 'province_id', 'editingId']);
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function openEditModal(int $id): void
  {
    $regency = Regency::findOrFail($id);

    $this->editingId   = $regency->id;
    $this->name        = $regency->name;
    $this->province_id = (string) $regency->province_id;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    $this->validate([
      'name'        => ['required', 'string', 'max:255'],
      'province_id' => ['required', 'exists:provinces,id'],
    ]);

    // Cegah duplikasi nama kabupaten/kota dalam satu provinsi.
    $existing = Regency::where('province_id', $this->province_id)
      ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->name))])
      ->first();
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Kabupaten/kota \"{$existing->name}\" sudah terdaftar pada provinsi ini.");
      return;
    }

    $data = [
      'name'        => trim($this->name),
      'province_id' => $this->province_id,
    ];

    if ($this->editingId) {
      Regency::findOrFail($this->editingId)->update($data);
      $message = 'Kabupaten/kota berhasil diperbarui.';
    } else {
      Regency::create($data);
      $message = 'Kabupaten/kota baru berhasil ditambahkan.';
    }

    $this->reset(['name', 'province_id', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $regency = Regency::find($id);
    if (! $regency) {
      return;
    }

    $this->deletingId      = $regency->id;
    $this->deletingName    = $regency->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('regency-delete-countdown');
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

    $regency = Regency::findOrFail($this->deletingId);

    // Proteksi: kabupaten/kota yang dipakai sebagai tujuan SPPD tidak boleh dihapus.
    if (SppdDestination::where('regency_id', $regency->id)->exists()) {
      $this->closeDeleteModal();
      $this->toastError("Gagal: \"{$regency->name}\" masih dipakai sebagai tujuan pada data SPPD.");
      return;
    }

    $name = $regency->name;
    $regency->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Kabupaten/kota \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $query = Regency::with('province')->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->provinceFilter !== '') {
      $query->where('province_id', $this->provinceFilter);
    }

    $regencies = $query->paginate(20)->onEachSide(1);
    $provinces = Province::orderBy('name')->get();

    return view('livewire.regencies.index', compact('regencies', 'provinces'))
      ->title('Kelola Data Kabupaten/Kota');
  }
}
