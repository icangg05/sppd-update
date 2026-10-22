<?php

namespace App\Livewire\Sppd;

use App\Livewire\Concerns\InteractsWithToast;
use App\Livewire\Concerns\SearchesEmployees;
use App\Models\Department;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Penandatangan "Setuju Bayar" pada Portal Dokumen SPPD: kolom Setuju Bayar /
 * Mengetahui di kuitansi, pengeluaran riil, dan rincian biaya. Disimpan per
 * instansi (root zona data pelaksana, lihat Department::resolveSetujuBayar), jadi
 * berlaku untuk seluruh SPPD instansi itu. Diatur Admin OPD instansi / Super Admin.
 */
class SetujuBayarSettings extends Component
{
  use InteractsWithToast;
  use SearchesEmployees;

  #[Locked]
  public int $sppdId;

  public bool $showModal = false;
  /** Bertambah tiap modal dibuka: isi modal dibuat ulang (wire:key) agar state Alpine pembukaan sebelumnya tidak tersisa. */
  #[Locked]
  public int $opened = 0;
  /** Kosong = Kepala OPD (bawaan). */
  public $signerId = '';
  public string $label = '';

  public function mount(SppdRequest $sppd): void
  {
    $this->sppdId = $sppd->id;
  }

  private function sppd(): SppdRequest
  {
    return SppdRequest::with(['user.department.parent', 'pptk'])->findOrFail($this->sppdId);
  }

  /** Instansi tempat pengaturan disimpan: root zona data unit kerja pelaksana. */
  private function instansi(SppdRequest $sppd): ?Department
  {
    return $sppd->user?->department?->getScopeRootDepartment();
  }

  private function canManage(?Department $instansi): bool
  {
    $actor = Auth::user();
    if (! $instansi) {
      return false;
    }
    if ($actor->hasRole('super_admin')) {
      return true;
    }

    return $actor->hasRole('admin_opd')
      && ($actor->department?->getScopedRelatedIds() ?? collect([$actor->department_id]))->contains($instansi->id);
  }

  /** Kandidat: pegawai aktif di zona data instansi, tanpa akun administratif. */
  private function candidateQuery(Department $instansi)
  {
    return User::where('is_active', true)
      ->whereIn('department_id', $instansi->getScopedRelatedIds())
      ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin_opd', 'super_admin']));
  }

  /**
   * Pencarian penandatangan di server (lihat x-form.searchable-select searchMethod).
   * Pilihan "Kepala OPD (bawaan)" selalu di atas saat kata kunci kosong.
   */
  #[Renderless]
  public function searchSigners(string $term = ''): array
  {
    $instansi = $this->instansi($this->sppd());
    abort_unless($this->canManage($instansi), 403);

    $result = $this->employeeOptions($this->candidateQuery($instansi), $term);
    if (trim($term) === '') {
      $kepala = $instansi->defaultSetujuBayarUser()?->load('position');
      array_unshift($result['options'], ['value' => '', 'label' => 'Kepala OPD (bawaan)' . ($kepala ? " - {$kepala->name}" : '')]
        + ($kepala ? static::employeeDetails($kepala) : ['name' => null, 'nip' => null, 'position' => null]));
    }

    return $result;
  }

  public function open(): void
  {
    $instansi = $this->instansi($this->sppd());
    if (! $this->canManage($instansi)) {
      $this->toastError('Penandatangan Setuju Bayar diatur oleh Admin OPD instansi pelaksana atau Super Admin.');
      return;
    }

    $this->signerId = $instansi->setuju_bayar_user_id ?? '';
    $this->label = $instansi->setuju_bayar_label ?? '';
    $this->resetValidation();
    $this->opened++;
    $this->showModal = true;
  }

  public function save(): void
  {
    $instansi = $this->instansi($this->sppd());
    if (! $this->canManage($instansi)) {
      $this->toastError('Penandatangan Setuju Bayar diatur oleh Admin OPD instansi pelaksana atau Super Admin.');
      return;
    }

    $this->validate([
      'signerId' => ['nullable', function (string $attribute, $value, \Closure $fail) use ($instansi) {
        if ($value && ! $this->candidateQuery($instansi)->whereKey($value)->exists()) {
          $fail("Penandatangan harus pegawai aktif di lingkup {$instansi->name}.");
        }
      }],
      'label' => ['nullable', 'string', 'max:150'],
    ], [], ['signerId' => 'pegawai penandatangan', 'label' => 'label jabatan']);

    $instansi->update([
      'setuju_bayar_user_id' => $this->signerId ?: null,
      'setuju_bayar_label'   => trim($this->label) ?: null,
    ]);

    $this->showModal = false;
    // Isian kosong (mis. setelah Reset pengaturan di modal) = kembali ke Kepala OPD.
    $this->toastSuccess($instansi->setuju_bayar_user_id || $instansi->setuju_bayar_label
      ? "Penandatangan Setuju Bayar {$instansi->name} disimpan."
      : "Pengaturan Setuju Bayar {$instansi->name} direset. Dokumen kembali memakai Kepala OPD.");
  }

  /**
   * Nama, NIP, dan label yang tercetak, dengan aturan yang sama seperti dokumen:
   * label kosong memakai nama jabatan penandatangan, lalu "Kepala Dinas".
   *
   * @return array{name: ?string, nip: ?string, role: string}
   */
  private function signature(?User $signer, ?string $label): array
  {
    return [
      'name' => $signer?->name,
      'nip'  => $signer?->nip,
      'role' => trim((string) $label) ?: ($signer?->position?->name ?? 'Kepala Dinas'),
    ];
  }

  public function render()
  {
    $sppd = $this->sppd();
    $instansi = $this->instansi($sppd);
    $canManage = $this->canManage($instansi);

    // Yang berlaku sekarang (tersimpan), untuk baris ringkas di halaman.
    $resolved = $sppd->user?->department?->resolveSetujuBayar() ?? ['user' => null, 'label' => null];
    $current = $this->signature($resolved['user'], $resolved['label'])
      + ['isDefault' => ! $instansi?->setuju_bayar_user_id];

    // Pratinjau di modal: isi awal dari server, lalu dihitung ulang di klien (Alpine)
    // saat pegawai/label diubah. $people = data pegawai per id ('' = Kepala OPD).
    // Tetap dirender setelah modal pernah dibuka, agar isi tidak hilang saat animasi
    // tutup sesudah Simpan.
    $preview = null;
    $people = null;
    $selectedLabel = null;
    if ($this->opened && $instansi) {
      $kepala = $instansi->defaultSetujuBayarUser()?->load('position');
      $chosen = $this->signerId ? User::with('position')->find($this->signerId) : null;
      $preview = $this->signature($chosen ?? $kepala, $this->label);
      $people = ['' => $kepala ? static::employeeDetails($kepala) : null]
        + ($chosen ? [(string) $chosen->id => static::employeeDetails($chosen)] : []);
      $selectedLabel = $chosen ? static::employeeLabel($chosen) : null;
    }

    return view('livewire.sppd.setuju-bayar-settings', [
      'sppd'      => $sppd,
      'instansi'  => $instansi,
      'canManage' => $canManage,
      'current'   => $current,
      'preview'   => $preview,
      'people'    => $people,
      'selectedLabel' => $selectedLabel,
      'pptk'      => $sppd->pptk,
      'date'      => now()->translatedFormat('d F Y'),
    ]);
  }
}
