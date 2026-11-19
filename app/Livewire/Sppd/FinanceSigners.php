<?php

namespace App\Livewire\Sppd;

use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdFinanceOfficials;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Kartu "Pejabat Keuangan" di Portal Dokumen: PPTK, KPA (Setuju Bayar), dan Bendahara SPPD ini,
 * dipakai kuitansi, pengeluaran riil, dan rincian biaya. Mengubahnya di sini juga memperbarui pilihan
 * tersimpan pegawai pelaksana, yang dicatat ke SPPD berikutnya saat diajukan (SppdFinanceOfficials).
 * Modal pilihnya punya tab pratinjau blok tanda tangan tiap dokumen.
 */
class FinanceSigners extends Component
{
  use AuthorizesSppdAccess, InteractsWithToast;

  #[Locked]
  public int $sppdId;

  public bool $showFinanceModal = false;
  #[Locked]
  public ?string $financeRole = null;
  public string $financeSearch = '';
  public ?int $financeChoiceId = null;
  /** Label jabatan KPA yang tercetak (bawaan "Kuasa Pengguna Anggaran", bisa diedit). */
  public string $financeLabel = '';
  /** Bertambah tiap modal dibuka: isi modal dibuat ulang (tab Pilih Pegawai, daftar dari atas). */
  #[Locked]
  public int $financeOpenCount = 0;

  public function mount(SppdRequest $sppd): void
  {
    $this->sppdId = $sppd->id;
  }

  private function sppd(): SppdRequest
  {
    return SppdRequest::with(['user.department', 'pptk.position', 'kpa.position', 'bendahara.position'])->findOrFail($this->sppdId);
  }

  public function canManage(): bool
  {
    return Auth::user()->canManageCosts();
  }

  private function canPick(SppdRequest $sppd): bool
  {
    $this->authorizeCostMenu($sppd);

    return $this->canManage() && $sppd->user !== null;
  }

  /** @return array{id: ?int, label: ?string} pejabat SPPD ini untuk peran $role */
  private function current(SppdRequest $sppd, string $role): array
  {
    return match ($role) {
      'pptk' => ['id' => $sppd->pptk_id, 'label' => null],
      'kpa' => ['id' => $sppd->kpa_id, 'label' => $sppd->kpa_label],
      'bendahara' => ['id' => $sppd->bendahara_id, 'label' => $sppd->bendahara_label],
    };
  }

  public function openFinance(string $role): void
  {
    $sppd = $this->sppd();
    if (! SppdFinanceOfficials::isRole($role) || ! $this->canPick($sppd)) {
      $this->toastError('Anda tidak dapat mengatur pejabat keuangan SPPD ini.');
      return;
    }

    $current = $this->current($sppd, $role);
    $this->financeRole = $role;
    $this->financeSearch = '';
    $this->financeChoiceId = $current['id'];
    $this->financeLabel = $role === 'kpa' ? ($current['label'] ?: SppdFinanceOfficials::DEFAULT_KPA_LABEL) : '';
    $this->resetValidation();
    $this->financeOpenCount++;
    $this->showFinanceModal = true;
  }

  public function pickFinance(int $id): void
  {
    $this->financeChoiceId = $id;
    $this->resetValidation('financeChoiceId');
  }

  public function saveFinance(): void
  {
    $sppd = $this->sppd();
    $role = $this->financeRole;
    abort_unless(SppdFinanceOfficials::isRole($role) && $this->canPick($sppd), 403, 'Aksi ini tidak diizinkan.');

    $this->validate(['financeLabel' => ['nullable', 'string', 'max:150']], [], ['financeLabel' => 'label jabatan']);
    if (! $this->financeChoiceId) {
      $this->addError('financeChoiceId', 'Pilih salah satu pegawai terlebih dahulu.');
      return;
    }
    $service = app(SppdFinanceOfficials::class);
    if (! $service->isCandidate($sppd->user, $role, $this->financeChoiceId)) {
      $this->addError('financeChoiceId', $role === 'bendahara'
        ? 'Pilih pegawai aktif berjabatan Bendahara di instansi pelaksana.'
        : 'Pilih pegawai aktif di instansi pelaksana.');
      return;
    }

    $official = User::with('position')->find($this->financeChoiceId);
    $service->setOnSppd($sppd, $role, $official, $role === 'kpa' ? $this->financeLabel : null);
    $this->showFinanceModal = false;
    $this->toastSuccess(SppdFinanceOfficials::ROLES[$role]['full'] . " SPPD ini: {$official->name}. Juga disimpan untuk perjalanan berikutnya {$sppd->user->name}.");
  }

  public function clearFinance(): void
  {
    $sppd = $this->sppd();
    $role = $this->financeRole;
    abort_unless(SppdFinanceOfficials::isRole($role) && $this->canPick($sppd), 403, 'Aksi ini tidak diizinkan.');

    app(SppdFinanceOfficials::class)->setOnSppd($sppd, $role, null);
    $this->showFinanceModal = false;
    $this->toastSuccess(SppdFinanceOfficials::ROLES[$role]['full'] . ' SPPD ini dikosongkan.');
  }

  /**
   * Isi modal (hanya saat terbuka): kandidat hasil pencarian (pilihan saat ini tetap tampil walau tidak
   * termasuk hasil), dan pratinjau dokumen dengan calon yang sedang dipilih.
   */
  private function financeModalData(SppdRequest $sppd, SppdFinanceOfficials $service): ?array
  {
    $pelaksana = $this->showFinanceModal ? $sppd->user : null;
    if (! $pelaksana || ! SppdFinanceOfficials::isRole($this->financeRole)) {
      return null;
    }

    $found = $service->search($pelaksana, $this->financeRole, $this->financeSearch);
    $chosen = $this->financeChoiceId
      ? User::with(['position:id,name', 'department:id,name'])->find($this->financeChoiceId)
      : null;
    if ($chosen && ! $found->contains('id', $chosen->id)) {
      $found->prepend($chosen);
    }

    return [
      'role' => $this->financeRole,
      'meta' => SppdFinanceOfficials::ROLES[$this->financeRole],
      'pelaksana' => $pelaksana->name,
      'hasCurrent' => $this->current($sppd, $this->financeRole)['id'] !== null,
      'candidates' => $found->map(fn (User $u) => [
        'id' => $u->id,
        'name' => $u->name,
        'nip' => $u->nip,
        'position' => $u->position?->name,
        'unit' => $u->department?->name,
      ])->all(),
      'previews' => $service->previewDocuments($sppd, $this->financeRole, $chosen, $this->financeLabel),
      // Halaman Data Pegawai hanya untuk Super Admin & Admin OPD (middleware route master.*).
      'canManageEmployees' => Auth::user()->hasAnyRole(['super_admin', 'admin_opd']),
    ];
  }

  public function render()
  {
    $sppd = $this->sppd();
    $service = app(SppdFinanceOfficials::class);
    $pelaksana = $sppd->user;

    $items = [
      'pptk' => ['user' => $sppd->pptk, 'label' => null],
      // Label kosong = SPPD lama: label bawaan dokumen (Pengguna Anggaran / nama jabatan).
      'kpa' => ['user' => $sppd->kpa, 'label' => $sppd->kpa ? ($sppd->kpa_label ?? 'Pengguna Anggaran (bawaan)') : null],
      'bendahara' => ['user' => $sppd->bendahara, 'label' => $sppd->bendahara_label],
    ];
    foreach ($items as $role => $item) {
      $items[$role]['warning'] = $pelaksana ? $service->warningFor($item['user'], $pelaksana, $role) : null;
    }

    return view('livewire.sppd.finance-signers', [
      'sppd' => $sppd,
      'items' => $items,
      'missing' => collect($items)->filter(fn ($item) => ! $item['user'])->count(),
      'canManage' => $this->canManage(),
      'financeModal' => $this->financeModalData($sppd, $service),
    ]);
  }
}
