<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Country;
use App\Models\ForeignCity;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ForeignCityIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $countryFilter = '';

  // Form tambah / edit
  public bool $showFormModal = false;
  public ?int $editingId = null;
  public string $name = '';
  public string $country_id = '';

  // Mode tambah: input hingga 5 kota sekaligus dalam satu negara.
  public array $names = [''];
  public const MAX_NAMES = 5;

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

  public function updatedCountryFilter(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search        = '';
    $this->countryFilter = '';
    $this->resetPage();
  }

  public function openCreateModal(): void
  {
    $this->reset(['name', 'country_id', 'editingId', 'names']);
    // Negara yang sedang difilter langsung terpilih di form tambah.
    $this->country_id = $this->countryFilter;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function addNameRow(): void
  {
    if (count($this->names) < self::MAX_NAMES) {
      $this->names[] = '';
    }
  }

  public function removeNameRow(int $i): void
  {
    if (count($this->names) <= 1) {
      return;
    }
    unset($this->names[$i]);
    $this->names = array_values($this->names);
  }

  public function openEditModal(int $id): void
  {
    $city = ForeignCity::findOrFail($id);

    $this->editingId  = $city->id;
    $this->name       = $city->name;
    $this->country_id = (string) $city->country_id;
    $this->resetValidation();
    $this->showFormModal = true;
  }

  public function save(): void
  {
    if ($this->editingId) {
      $this->saveEdit();
      return;
    }

    $this->saveBatch();
  }

  private function saveEdit(): void
  {
    $this->validate([
      'name'       => ['required', 'string', 'max:150'],
      'country_id' => ['required', 'exists:countries,id'],
    ]);

    // Cegah duplikasi nama kota dalam satu negara.
    $existing = ForeignCity::where('country_id', $this->country_id)
      ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->name))])
      ->first();
    if ($existing && $existing->id !== $this->editingId) {
      $this->toastError("Kota \"{$existing->name}\" sudah terdaftar pada negara ini.");
      return;
    }

    ForeignCity::findOrFail($this->editingId)->update([
      'name'       => trim($this->name),
      'country_id' => $this->country_id,
    ]);

    $this->reset(['name', 'country_id', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();
    $this->toastSuccess('Kota berhasil diperbarui.');
  }

  private function saveBatch(): void
  {
    $this->validate(['country_id' => ['required', 'exists:countries,id']]);

    // Kumpulkan nama non-kosong, buang duplikat dalam batch (case-insensitive).
    $names = [];
    foreach ($this->names as $raw) {
      $n = trim($raw);
      if ($n === '') {
        continue;
      }
      if (mb_strlen($n) > 150) {
        $this->addError('names', 'Setiap nama maksimal 150 karakter.');
        return;
      }
      $names[mb_strtolower($n)] = $n;
    }

    if (empty($names)) {
      $this->addError('names', 'Isi minimal satu nama kota.');
      return;
    }

    // Nama yang sudah ada di negara ini dilewati (bukan error fatal).
    $created = 0;
    $skipped = [];
    foreach ($names as $key => $n) {
      $exists = ForeignCity::where('country_id', $this->country_id)
        ->whereRaw('LOWER(name) = ?', [$key])
        ->exists();
      if ($exists) {
        $skipped[] = $n;
        continue;
      }
      ForeignCity::create(['name' => $n, 'country_id' => $this->country_id]);
      $created++;
    }

    if ($created === 0) {
      $this->toastError('Semua nama sudah terdaftar pada negara ini.');
      return;
    }

    $this->reset(['names', 'country_id', 'editingId']);
    $this->showFormModal = false;
    $this->resetPage();

    $message = "{$created} kota berhasil ditambahkan.";
    if ($skipped) {
      $message .= ' Dilewati (sudah ada): ' . implode(', ', $skipped) . '.';
    }
    $this->toastSuccess($message);
  }

  public function confirmDelete(int $id): void
  {
    $city = ForeignCity::find($id);
    if (! $city) {
      return;
    }

    $this->deletingId      = $city->id;
    $this->deletingName    = $city->name;
    $this->showDeleteModal = true;

    // Mulai hitung mundur 10 detik sebelum tombol Hapus aktif.
    $this->dispatch('foreign-city-delete-countdown');
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

    // Tujuan SPPD menyimpan nama kota sebagai teks, jadi menghapus kota dari
    // daftar tidak mengubah data SPPD yang sudah ada.
    $city = ForeignCity::findOrFail($this->deletingId);
    $name = $city->name;
    $city->delete();

    $this->deletingId   = null;
    $this->deletingName = null;
    $this->toastSuccess("Kota \"{$name}\" berhasil dihapus.");
  }

  public function render()
  {
    $query = ForeignCity::with('country')->orderBy('name');

    if ($this->search !== '') {
      $query->where('name', 'like', "%{$this->search}%");
    }

    if ($this->countryFilter !== '') {
      $query->where('country_id', $this->countryFilter);
    }

    $cities         = $query->paginate(20)->onEachSide(1);
    $countryOptions = Country::selectOptions('id');

    return view('livewire.foreign-cities.index', compact('cities', 'countryOptions'))
      ->title('Kelola Data Kota Luar Negeri');
  }
}
