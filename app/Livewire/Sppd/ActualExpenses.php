<?php

namespace App\Livewire\Sppd;

use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Laporan Pengeluaran Riil. CRUD reaktif (tanpa reload) via Livewire; tabel
 * dirender ulang server-side tiap aksi. Status kunci tombol cetak (hasPptk)
 * disegarkan dari event `pptk-saved` yang dikirim komponen PptkSelector.
 */
class ActualExpenses extends Component
{
  use AuthorizesSppdAccess, InteractsWithToast;

  public SppdRequest $sppd;
  public bool $hasPptk = false;

  public bool $showAddModal = false;
  public bool $showEditModal = false;
  public bool $showBulkModal = false;

  public string $addUserId = '';
  public string $addUserName = '';
  public string $addDescription = '';
  public string $addAmount = '';

  public ?int $editExpenseId = null;
  public string $editDescription = '';
  public string $editAmount = '';

  public string $bulkDescription = '';
  public string $bulkAmount = '';
  public array $selectedUserIds = [];

  public function mount(SppdRequest $sppd): void
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $this->sppd = $sppd;
    $this->hasPptk = (bool) $sppd->pptk_id;
    $this->selectedUserIds = $this->peopleIds();
  }

  /** Disegarkan saat PptkSelector menyimpan PPTK, agar tombol cetak membuka. */
  #[On('pptk-saved')]
  public function onPptkSaved(bool $hasPptk = true): void
  {
    $this->hasPptk = $hasPptk;
  }

  protected function validationAttributes(): array
  {
    return [
      'addDescription' => 'uraian biaya', 'addAmount' => 'nominal biaya',
      'editDescription' => 'uraian biaya', 'editAmount' => 'nominal biaya',
      'bulkDescription' => 'uraian biaya', 'bulkAmount' => 'nominal biaya',
      'selectedUserIds' => 'pegawai',
    ];
  }

  public function canManage(): bool
  {
    return Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
  }

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

  public function openAdd(string $userId, string $userName): void
  {
    $this->resetValidation();
    $this->addUserId = $userId;
    $this->addUserName = $userName;
    $this->addDescription = '';
    $this->addAmount = '';
    $this->showAddModal = true;
  }

  public function saveAdd(): void
  {
    $this->guard();
    $this->validate([
      'addUserId' => 'required|exists:users,id',
      'addDescription' => 'required|string|max:500',
      'addAmount' => 'required|numeric|min:0',
    ]);

    $this->sppd->actualExpenses()->create([
      'user_id' => $this->addUserId,
      'description' => $this->addDescription,
      'amount' => (int) $this->addAmount,
    ]);

    $this->showAddModal = false;
    $this->toastSuccess('Pengeluaran riil berhasil ditambahkan.');
  }

  public function openEdit(int $expenseId): void
  {
    $expense = $this->sppd->actualExpenses()->findOrFail($expenseId);
    $this->resetValidation();
    $this->editExpenseId = $expense->id;
    $this->editDescription = $expense->description;
    $this->editAmount = (string) $expense->amount;
    $this->showEditModal = true;
  }

  public function saveEdit(): void
  {
    $this->guard();
    $expense = $this->sppd->actualExpenses()->findOrFail($this->editExpenseId);
    $this->validate([
      'editDescription' => 'required|string|max:500',
      'editAmount' => 'required|numeric|min:0',
    ]);

    $expense->update([
      'description' => $this->editDescription,
      'amount' => (int) $this->editAmount,
    ]);

    $this->showEditModal = false;
    $this->toastSuccess('Pengeluaran riil berhasil diperbarui.');
  }

  public function delete(int $expenseId): void
  {
    $this->guard();
    $this->sppd->actualExpenses()->findOrFail($expenseId)->delete();
    $this->toastSuccess('Pengeluaran riil berhasil dihapus.');
  }

  public function openBulk(): void
  {
    $this->resetValidation();
    $this->bulkDescription = '';
    $this->bulkAmount = '';
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
      'bulkDescription' => 'required|string|max:500',
      'bulkAmount' => 'required|numeric|min:0',
    ]);

    foreach ($this->selectedUserIds as $userId) {
      $this->sppd->actualExpenses()->create([
        'user_id' => $userId,
        'description' => $this->bulkDescription,
        'amount' => (int) $this->bulkAmount,
      ]);
    }

    $this->showBulkModal = false;
    $this->toastSuccess('Pengeluaran riil berhasil ditambahkan untuk pegawai terpilih.');
  }

  public function render()
  {
    $this->sppd->load(['user', 'followers.user', 'actualExpenses.user']);

    return view('livewire.sppd.actual-expenses', [
      'people' => $this->people(),
    ]);
  }
}
