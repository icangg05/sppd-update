<?php

namespace App\Livewire\Sppd;

use App\Enums\CostCategory;
use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Rincian Biaya Perjalanan Dinas. CRUD reaktif (tanpa reload) via Livewire.
 * Tabel dirender ulang server-side tiap aksi, jadi tidak butuh state Alpine.
 * Field uang (tarif satuan) diformat ribuan di klien lewat @entangle.
 */
class CostDetails extends Component
{
  use AuthorizesSppdAccess, InteractsWithToast, WithFileUploads;

  public SppdRequest $sppd;
  public bool $hasBendahara = false;

  public bool $showAddModal = false;
  public bool $showEditModal = false;
  public bool $showBulkModal = false;

  // Tambah (per pegawai)
  public string $addUserId = '';
  public string $addUserName = '';
  public string $addCategory = '';
  public string $addDescription = '';
  public string $addUnitCost = '';
  public string $addQuantity = '1';
  public $addReceipt = null;

  // Edit
  public ?int $editCostId = null;
  public string $editCategory = '';
  public string $editDescription = '';
  public string $editUnitCost = '';
  public string $editQuantity = '1';
  public $editReceipt = null;

  // Input sekaligus
  public string $bulkCategory = '';
  public string $bulkDescription = '';
  public string $bulkUnitCost = '';
  public string $bulkQuantity = '1';
  public array $selectedUserIds = [];

  public function mount(SppdRequest $sppd): void
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $this->sppd = $sppd;
    $this->addCategory = CostCategory::cases()[0]->value;
    $this->bulkCategory = CostCategory::cases()[0]->value;
    $this->selectedUserIds = $this->peopleIds();

    $this->hasBendahara = User::whereHas('position', fn ($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $sppd->user->department_id)
      ->where('is_active', true)
      ->exists();
  }

  protected function validationAttributes(): array
  {
    return [
      'addCategory' => 'kategori biaya', 'addDescription' => 'uraian keterangan',
      'addUnitCost' => 'tarif satuan', 'addQuantity' => 'jumlah', 'addReceipt' => 'lampiran',
      'editCategory' => 'kategori biaya', 'editDescription' => 'uraian keterangan',
      'editUnitCost' => 'tarif satuan', 'editQuantity' => 'jumlah', 'editReceipt' => 'lampiran',
      'bulkCategory' => 'kategori biaya', 'bulkDescription' => 'uraian keterangan',
      'bulkUnitCost' => 'tarif satuan', 'bulkQuantity' => 'jumlah', 'selectedUserIds' => 'pegawai',
    ];
  }

  public function canManage(): bool
  {
    return Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
  }

  /** Pelaksana + pengikut, sebagai list [id, name, label]. */
  public function people()
  {
    $people = collect([['id' => (string) $this->sppd->user->id, 'name' => $this->sppd->user->name, 'label' => 'Pelaksana']]);
    foreach ($this->sppd->followers as $f) {
      $people->push(['id' => (string) $f->user->id, 'name' => $f->user->name, 'label' => 'Pengikut']);
    }

    return $people;
  }

  protected function peopleIds(): array
  {
    return $this->people()->pluck('id')->all();
  }

  protected function guard(): void
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($this->sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');
  }

  protected function categoryRule(): array
  {
    return ['required', Rule::in(array_column(CostCategory::cases(), 'value'))];
  }

  public function openAdd(string $userId, string $userName): void
  {
    $this->resetValidation();
    $this->addUserId = $userId;
    $this->addUserName = $userName;
    $this->addCategory = CostCategory::cases()[0]->value;
    $this->addDescription = '';
    $this->addUnitCost = '';
    $this->addQuantity = '1';
    $this->addReceipt = null;
    $this->showAddModal = true;
  }

  public function saveAdd(): void
  {
    $this->guard();
    $this->validate([
      'addUserId' => 'required|exists:users,id',
      'addCategory' => $this->categoryRule(),
      'addDescription' => 'required|string|max:500',
      'addUnitCost' => 'required|numeric|min:0',
      'addQuantity' => 'required|integer|min:1',
      'addReceipt' => 'nullable|image|max:20480',
    ]);

    $this->sppd->costDetails()->create([
      'user_id' => $this->addUserId,
      'cost_category' => $this->addCategory,
      'description' => $this->addDescription,
      'unit_cost' => (int) $this->addUnitCost,
      'quantity' => (int) $this->addQuantity,
      'receipt_photo' => $this->addReceipt ? $this->addReceipt->store(date('Y').'/nota_perjalanan', 'public') : null,
    ]);

    $this->showAddModal = false;
    $this->toastSuccess('Rincian biaya berhasil ditambahkan.');
  }

  public function openEdit(int $costId): void
  {
    $cost = $this->sppd->costDetails()->findOrFail($costId);
    $this->resetValidation();
    $this->editCostId = $cost->id;
    $this->editCategory = $cost->cost_category->value;
    $this->editDescription = $cost->description;
    $this->editUnitCost = (string) $cost->unit_cost;
    $this->editQuantity = (string) $cost->quantity;
    $this->editReceipt = null;
    $this->showEditModal = true;
  }

  public function saveEdit(): void
  {
    $this->guard();
    $cost = $this->sppd->costDetails()->findOrFail($this->editCostId);
    $this->validate([
      'editCategory' => $this->categoryRule(),
      'editDescription' => 'required|string|max:500',
      'editUnitCost' => 'required|numeric|min:0',
      'editQuantity' => 'required|integer|min:1',
      'editReceipt' => 'nullable|image|max:20480',
    ]);

    $attrs = [
      'cost_category' => $this->editCategory,
      'description' => $this->editDescription,
      'unit_cost' => (int) $this->editUnitCost,
      'quantity' => (int) $this->editQuantity,
    ];

    if ($this->editReceipt) {
      if ($cost->receipt_photo) {
        Storage::disk('public')->delete($cost->receipt_photo);
      }
      $attrs['receipt_photo'] = $this->editReceipt->store(date('Y').'/nota_perjalanan', 'public');
    }

    $cost->update($attrs);
    $this->showEditModal = false;
    $this->toastSuccess('Rincian biaya berhasil diperbarui.');
  }

  public function delete(int $costId): void
  {
    $this->guard();
    $cost = $this->sppd->costDetails()->findOrFail($costId);
    if ($cost->receipt_photo) {
      Storage::disk('public')->delete($cost->receipt_photo);
    }
    $cost->delete();
    $this->toastSuccess('Rincian biaya berhasil dihapus.');
  }

  public function openBulk(): void
  {
    $this->resetValidation();
    $this->bulkCategory = CostCategory::cases()[0]->value;
    $this->bulkDescription = '';
    $this->bulkUnitCost = '';
    $this->bulkQuantity = '1';
    $this->selectedUserIds = $this->peopleIds();
    $this->showBulkModal = true;
  }

  public function toggleSelectAll(): void
  {
    $all = $this->peopleIds();
    $this->selectedUserIds = count($this->selectedUserIds) === count($all) ? [] : $all;
  }

  public function saveBulk(): void
  {
    $this->guard();
    $this->validate([
      'selectedUserIds' => 'required|array|min:1',
      'selectedUserIds.*' => 'exists:users,id',
      'bulkCategory' => $this->categoryRule(),
      'bulkDescription' => 'required|string|max:500',
      'bulkUnitCost' => 'required|numeric|min:0',
      'bulkQuantity' => 'required|integer|min:1',
    ]);

    foreach ($this->selectedUserIds as $userId) {
      $this->sppd->costDetails()->create([
        'user_id' => $userId,
        'cost_category' => $this->bulkCategory,
        'description' => $this->bulkDescription,
        'unit_cost' => (int) $this->bulkUnitCost,
        'quantity' => (int) $this->bulkQuantity,
      ]);
    }

    $this->showBulkModal = false;
    $this->toastSuccess('Rincian biaya berhasil ditambahkan untuk pegawai terpilih.');
  }

  public function render()
  {
    $this->sppd->load(['user', 'followers.user', 'costDetails.user']);

    return view('livewire.sppd.cost-details', [
      'people' => $this->people(),
      'categoryOptions' => collect(CostCategory::cases())
        ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
    ]);
  }
}
