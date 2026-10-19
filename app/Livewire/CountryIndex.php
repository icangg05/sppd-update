<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Country;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CountryIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';
  public string $iso2 = '';
  public string $aliases = '';

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
    $this->reset(['name', 'iso2', 'aliases', 'editingId']);
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function openEditModal(int $id): void
  {
    $country = Country::findOrFail($id);

    $this->editingId = $country->id;
    $this->name      = $country->name;
    $this->iso2      = (string) $country->iso2;
    $this->aliases   = (string) $country->aliases;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    $this->iso2 = strtoupper(trim($this->iso2));

    $this->validate([
      'name' => ['required', 'string', 'max:100'],
      'iso2' => ['nullable', 'alpha', 'size:2', Rule::unique('countries', 'iso2')->ignore($this->editingId)],
      'aliases' => ['nullable', 'string', 'max:255'],
    ], [
      'iso2.size'   => 'Kode negara harus 2 huruf (ISO 3166-1, misal: JP).',
      'iso2.alpha'  => 'Kode negara hanya boleh berisi huruf.',
      'iso2.unique' => 'Kode negara ini sudah dipakai negara lain.',
    ]);

    // Cegah duplikasi nama negara (case-insensitive).
    $existing = Country::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->name))])->first();
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Negara \"{$existing->name}\" sudah terdaftar.");
      return;
    }

    // Nama lain dirapikan: dipisah koma, tanpa spasi berlebih & duplikat.
    $aliases = collect(explode(',', $this->aliases))
      ->map(fn ($a) => trim($a))
      ->filter()
      ->unique(fn ($a) => mb_strtolower($a))
      ->implode(', ');

    $data = ['name' => trim($this->name), 'iso2' => $this->iso2 ?: null, 'aliases' => $aliases ?: null];

    if ($this->editingId) {
      Country::findOrFail($this->editingId)->update($data);
      $message = 'Negara berhasil diperbarui.';
    } else {
      Country::create($data);
      $message = 'Negara baru berhasil ditambahkan.';
    }

    $this->reset(['name', 'iso2', 'aliases', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $country = Country::find($id);
    if (! $country) {
      return;
    }

    $this->deletingId      = $country->id;
    $this->deletingName    = $country->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('country-delete-countdown');
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

    $country = Country::withCount('cities')->findOrFail($this->deletingId);

    // Proteksi: negara yang masih punya kota tidak boleh dihapus.
    if ($country->cities_count > 0) {
      $this->closeDeleteModal();
      $this->toastError("Gagal: negara \"{$country->name}\" masih memiliki {$country->cities_count} kota.");
      return;
    }

    $name = $country->name;
    $country->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Negara \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $query = Country::withCount('cities')->orderBy('name');

    if ($this->search !== '') {
      $query->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
        ->orWhere('aliases', 'like', "%{$this->search}%")
        ->orWhere('iso2', $this->search));
    }

    $countries = $query->paginate(20)->onEachSide(1);

    return view('livewire.countries.index', compact('countries'))
      ->title('Kelola Data Negara');
  }
}
