<?php

namespace App\Livewire;

use App\Enums\ApprovalStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdApproval;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ganti Walikota / Wakil Walikota dari menu Walikota & Wakil Walikota, memakai wizard
 * Ganti pejabat yang sama dengan Buat SPPD (partial livewire.partials.official-wizard):
 * 1 pilih pengganti (pegawai Staf Sekretariat Daerah) → 2 jabatan → 3 konfirmasi.
 *
 * Akun pejabat lama tidak diubah namanya: ia kembali menjadi Staf, sehingga riwayat
 * persetujuan dan dokumen lama tetap atas namanya. Logika ada di
 * SppdWorkflowService::replaceKepalaDaerah.
 */
class ReplaceKepalaDaerahModal extends Component
{
  use InteractsWithToast;

  public bool $showOfficialModal = false;

  /** Pejabat yang diganti (Walikota / Wakil Walikota aktif). */
  #[Locked]
  public ?int $officialReplaceId = null;

  public int $officialStep = 1;
  public string $officialSearch = '';
  public ?int $officialCandidateId = null;
  public ?int $officialPositionId = null;

  /** Dibuka dari tombol Ganti di baris daftar Walikota & Wakil Walikota. */
  #[On('replace-kepala-daerah')]
  public function open(int $userId): void
  {
    $outgoing = $this->outgoing($userId);
    if (! $outgoing || ! $this->canManage($outgoing)) {
      $this->toastError('Pejabat ini tidak dapat diganti dari akun Anda.');
      return;
    }

    $this->reset(['officialSearch', 'officialCandidateId', 'officialPositionId']);
    $this->officialReplaceId = $outgoing->id;
    $this->officialStep = 1;
    $this->showOfficialModal = true;
  }

  /** Langkah 1: pengganti dipilih. Jabatan yang sudah sesuai langsung ke konfirmasi. */
  public function pickOfficialCandidate(int $candidateId): void
  {
    $outgoing = $this->outgoing($this->officialReplaceId);
    $role = $outgoing ? $this->roleOf($outgoing) : null;
    $candidate = $role ? $this->service()->officialCandidate($role, $outgoing, $candidateId) : null;
    if (! $candidate) {
      $this->toastError('Pegawai tidak dapat dipilih untuk jabatan ini.');
      return;
    }

    $this->officialCandidateId = $candidateId;
    $this->officialPositionId = null;

    if ($candidate['fits']) {
      $this->officialStep = 3;
      return;
    }

    // Jabatan pejabat lama langsung disarankan untuk dialihkan ke pengganti.
    $suggested = collect($this->service()->officialPositions($role, $outgoing, $candidateId, $outgoing->id))
      ->firstWhere('suggested', true);
    $this->officialPositionId = $suggested['id'] ?? null;
    $this->officialStep = 2;
  }

  /** Langkah 2: pilih jabatan. */
  public function chooseOfficialPosition(int $positionId): void
  {
    $outgoing = $this->outgoing($this->officialReplaceId);
    if (! $outgoing || ! $this->officialCandidateId) {
      return;
    }

    $position = collect($this->service()->officialPositions($this->roleOf($outgoing), $outgoing, $this->officialCandidateId, $outgoing->id))
      ->firstWhere('id', $positionId);
    if ($position && ! $position['holder']) {
      $this->officialPositionId = $positionId;
    }
  }

  public function officialNext(): void
  {
    if ($this->officialStep === 2 && $this->officialPositionId) {
      $this->officialStep = 3;
    }
  }

  public function officialBack(): void
  {
    // Konfirmasi tanpa ganti jabatan (jabatan sudah sesuai) kembali ke daftar pegawai.
    $this->officialStep = $this->officialStep === 3 && $this->officialPositionId ? 2 : 1;
  }

  /** Langkah 3: ganti pejabat, lalu muat ulang daftar Walikota & Wakil Walikota. */
  public function confirmOfficial(): void
  {
    $outgoing = $this->outgoing($this->officialReplaceId);
    if (! $outgoing || ! $this->canManage($outgoing)) {
      $this->toastError('Pejabat ini tidak dapat diganti dari akun Anda.');
      return;
    }

    $service = $this->service();
    $label = $service->roleLabel($this->roleOf($outgoing));
    try {
      $appointed = $service->replaceKepalaDaerah(Auth::user(), $outgoing->id, (int) $this->officialCandidateId, $this->officialPositionId);
    } catch (\RuntimeException $e) {
      $this->toastError($e->getMessage());
      return;
    }

    $this->showOfficialModal = false;
    $this->toastSuccess("{$appointed->name} menggantikan {$outgoing->name} sebagai {$label}.");
    $this->dispatch('kepala-daerah-replaced');
  }

  public function render()
  {
    $service = $this->service();
    $outgoing = $this->showOfficialModal ? $this->outgoing($this->officialReplaceId) : null;
    $official = null;

    if ($outgoing) {
      $role = $this->roleOf($outgoing);
      $candidate = $this->officialCandidateId ? $service->officialCandidate($role, $outgoing, $this->officialCandidateId) : null;
      $positions = $candidate && $this->officialStep >= 2
        ? $service->officialPositions($role, $outgoing, $candidate['id'], $outgoing->id)
        : [];

      // Bentuk data sama dengan SppdCreate::render agar partial wizard dapat dipakai bersama.
      $official = [
        'role'       => $role,
        'label'      => $service->roleLabel($role),
        'cityWide'   => true,
        'step'       => $candidate ? $this->officialStep : 1,
        'candidate'  => $candidate,
        'candidates' => $this->officialStep === 1 || ! $candidate
          ? $service->officialCandidates($role, $outgoing, $this->officialSearch)
          : [],
        'positions'  => $positions,
        'position'   => collect($positions)->firstWhere('id', $this->officialPositionId),
        'outgoing'   => [
          'name'     => $outgoing->name,
          'position' => $outgoing->position?->name,
          'released' => $service->releasedPosition($outgoing, $role, $this->officialPositionId),
          'newRole'  => $service->roleLabel($service->releasedRole($role)),
          'pending'  => SppdApproval::where('approver_id', $outgoing->id)->where('status', ApprovalStatus::PENDING)->count(),
        ],
      ];
    }

    return view('livewire.replace-kepala-daerah-modal', ['official' => $official]);
  }

  /** Walikota / Wakil Walikota aktif dengan id ini, atau null. */
  private function outgoing(?int $userId): ?User
  {
    $user = $userId ? User::with(['position', 'department', 'roles'])->where('is_active', true)->find($userId) : null;

    return $user && $this->roleOf($user) ? $user : null;
  }

  private function roleOf(User $user): ?string
  {
    return $user->roles->pluck('name')->first(fn (string $r) => in_array($r, User::KEPALA_DAERAH_ROLES, true));
  }

  /**
   * Sama dengan Ganti pejabat di Buat SPPD: Super Admin, atau Admin OPD yang lingkupnya mencakup
   * Sekretariat Daerah (asal calon Walikota/Wakil) dan unit kerja pejabat lama.
   */
  private function canManage(User $outgoing): bool
  {
    $actor = Auth::user();
    if ($actor->hasRole('super_admin')) {
      return true;
    }
    if (! $actor->hasRole('admin_opd') || ! $actor->department) {
      return false;
    }

    $scope = $actor->department->getScopedRelatedIds();
    $setda = $this->service()->officialPoolRoot((string) $this->roleOf($outgoing), $outgoing);

    return $setda !== null && $scope->contains($setda->id) && $scope->contains($outgoing->department_id);
  }

  private function service(): SppdWorkflowService
  {
    return app(SppdWorkflowService::class);
  }
}
