<?php

namespace App\Livewire\Sppd;

use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdAdvanceReceipt;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Kuitansi Panjar. Form massal: satu nominal panjar per pegawai, disimpan
 * sekaligus tanpa reload via Livewire. Nominal kosong => data panjar pegawai
 * itu dihapus. Nomor kuitansi digenerate otomatis saat pertama dibuat.
 */
class AdvanceReceipts extends Component
{
  use AuthorizesSppdAccess, InteractsWithToast;

  public SppdRequest $sppd;
  public bool $hasBendahara = false;

  /** Peta user_id => nominal panjar (digit murni sebagai string). */
  public array $amounts = [];

  public bool $showBulkModal = false;
  public string $bulkAmount = '';

  public function mount(SppdRequest $sppd): void
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $this->sppd = $sppd;
    $this->hasBendahara = User::whereHas('position', fn ($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $sppd->user->department_id)
      ->where('is_active', true)
      ->exists();

    foreach ($this->people() as $person) {
      $receipt = $sppd->advanceReceipts->where('user_id', $person['id'])->first();
      $this->amounts[$person['id']] = (string) ($receipt?->amount ?? '');
    }
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

  protected function guard(): void
  {
    abort_unless($this->canManage(), 403, 'Aksi ini tidak diizinkan.');
    abort_unless(in_array($this->sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');
  }

  /** Isi nominal ke pegawai yang masih kosong, lalu langsung simpan. */
  public function applyBulk(): void
  {
    $bulk = preg_replace('/\D/', '', $this->bulkAmount);
    foreach ($this->amounts as $id => $amount) {
      if ($amount === '' || (int) $amount === 0) {
        $this->amounts[$id] = $bulk;
      }
    }
    $this->showBulkModal = false;
    $this->bulkAmount = '';
    $this->saveAll();
  }

  public function saveAll(): void
  {
    $this->guard();
    $this->validate(
      ['amounts.*' => 'nullable|numeric|min:0'],
      [],
      ['amounts.*' => 'nominal panjar'],
    );

    foreach ($this->amounts as $userId => $amount) {
      $amount = ($amount === '' || $amount === null) ? null : (int) $amount;

      if ($amount === null) {
        $this->sppd->advanceReceipts()->where('user_id', $userId)->delete();
        continue;
      }

      $receipt = $this->sppd->advanceReceipts()->where('user_id', $userId)->first();

      if ($receipt) {
        $receipt->update(['amount' => $amount]);
      } else {
        $this->sppd->advanceReceipts()->create([
          'user_id' => $userId,
          'amount' => $amount,
          'receipt_number' => $this->nextReceiptNumber(),
        ]);
      }
    }

    $this->toastSuccess('Seluruh data kuitansi panjar berhasil disimpan.');
  }

  protected function nextReceiptNumber(): string
  {
    $code = $this->sppd->user->department?->code ?? 'SPPD';
    $seq = SppdAdvanceReceipt::whereYear('created_at', now()->year)->count() + 1;

    return sprintf('KP-%s-%s-%04d', $code, now()->year, $seq);
  }

  public function render()
  {
    $this->sppd->load(['user.department', 'followers.user', 'advanceReceipts', 'actualExpenses', 'costDetails']);

    return view('livewire.sppd.advance-receipts', [
      'people' => $this->people(),
      'hasActualExpenses' => $this->sppd->actualExpenses->count() > 0,
      'hasCostDetails' => $this->sppd->costDetails->count() > 0,
    ]);
  }
}
