<?php

namespace App\Livewire\Sppd;

use App\Enums\ApprovalStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Vinkla\Hashids\Facades\Hashids;

#[Layout('layouts.app')]
class SppdCreate extends Component
{
  use InteractsWithToast;

  public ?int $user_id = null;
  public string $searchUser = '';
  public string $domain = 'dalam_daerah';
  public array $steps = [];
  public bool $hasHeader = false;
  public bool $isComplete = false;
  public string $errorMessage = '';
  public string $errorMessageName = '';
  public string $errorMessageRole = '';
  public array $userInfo = [];

  /**
   * Wizard Lengkapi Pejabat (per tahap, lewat tombol Lengkapi):
   * 1 pilih pegawai → 2 pilih/tambah jabatan (bila jabatannya belum sesuai) → 3 konfirmasi.
   * Wizard yang sama dipakai untuk mengganti pejabat yang sudah terisi (ikon edit).
   */
  public bool $showOfficialModal = false;
  public ?string $officialRole = null;
  /** Pejabat yang sedang diganti; null = melengkapi tahap kosong. */
  #[Locked]
  public ?int $officialReplaceId = null;
  public int $officialStep = 1;
  public string $officialSearch = '';
  public ?int $officialCandidateId = null;
  public ?int $officialPositionId = null;

  public function mount(): void
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Hanya Admin OPD atau Super Admin yang dapat membuat SPPD.');

    // Kembali dari Tahap 2: pulihkan pilihan pelaksana & domain agar tetap terpilih.
    // user_id datang sebagai hashid (getRouteKey), jadi perlu di-decode dulu.
    if (in_array(request('domain'), SppdDomain::values(), true)) {
      $this->domain = request('domain');
    }
    if ($hashid = request('user_id')) {
      $decoded = Hashids::decode($hashid);
      if (! empty($decoded)) {
        $this->user_id = (int) $decoded[0];
        $this->checkWorkflow();
      }
    }
  }

  public function updatedUserId(): void
  {
    $this->checkWorkflow();
  }

  public function selectUser(int $id): void
  {
    $this->user_id = $id;
    $this->checkWorkflow();
  }

  public function updatedDomain(): void
  {
    $this->checkWorkflow();
  }

  public function checkWorkflow(): void
  {
    $this->errorMessage = '';
    $this->errorMessageName = '';
    $this->errorMessageRole = '';
    $this->steps = [];
    $this->isComplete = false;
    $this->showOfficialModal = false;

    if (! $this->user_id) {
      return;
    }

    $user = User::with('department.parent')->find($this->user_id);
    if (! $user) {
      return;
    }

    $this->userInfo = [
      'name' => $user->name,
      'department' => $user->department?->name ?? 'Tanpa Unit Kerja',
      'role_label' => $user->roles->first()?->label ?? ($user->getRoleNames()->first() ?? 'Tanpa Role'),
    ];

    // 1. Cek perjalanan aktif
    $activeSppd = SppdRequest::where('user_id', $user->id)
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
              ->where('end_date', '>=', today());
          });
      })
      ->first();

    if ($activeSppd) {
      $this->errorMessageName = $user->name;
      $this->errorMessage = $activeSppd->status === SppdStatus::IN_PROGRESS
        ? 'masih memiliki SPPD yang sedang dalam proses persetujuan.'
        : 'masih memiliki SPPD yang sedang dalam periode perjalanan.';
      return;
    }

    // 2. Simulasi Workflow
    $workflowService = app(SppdWorkflowService::class);
    $this->steps = $workflowService->simulateApprovals($user, $this->domain);

    if (empty($this->steps)) {
      $this->errorMessage = 'Aturan alur untuk kategori ini belum dibuat oleh Administrator SPPD (Super Admin)';
      $this->errorMessageRole = $this->userInfo['role_label'];
      return;
    }

    // 3. Cek Kop Surat
    $this->hasHeader = (bool) ($user->department?->getInheritedLetterhead() && Str::contains($user->department->getInheritedLetterhead(), '/'));

    // 4. Verifikasi Kelengkapan Pejabat
    // Tahap berstatus "choose" (beberapa kandidat pejabat) dipilih di form detail.
    $allStepsFound = collect($this->steps)->doesntContain('status', 'not_found');

    if (! $allStepsFound) {
      $this->errorMessage = 'Ada pejabat struktural yang belum ditentukan dalam alur ini. Lengkapi lewat tombol pada tahap yang masih kosong.';
      return;
    }

    if (! $this->hasHeader) {
      $this->errorMessage = 'Unit kerja ' . e($this->userInfo['department']) . ' belum mengunggah <strong class="font-semibold">Kop Surat</strong> Resmi. Harap hubungi <strong class="font-semibold">Admin OPD</strong> Anda untuk melengkapi data dokumen.';
      return;
    }

    $this->isComplete = true;
  }

  /** Buka wizard untuk satu tahap yang pejabatnya belum ditemukan. */
  public function openOfficial(string $role): void
  {
    if (! collect($this->steps)->where('status', 'not_found')->contains('role', $role) || ! $this->canCompleteOfficial($role)) {
      return;
    }

    $this->startOfficialWizard($role, null);
  }

  /** Buka wizard untuk mengganti pejabat yang sudah mengisi suatu tahap. */
  public function replaceOfficial(string $role): void
  {
    $step = collect($this->steps)->where('status', 'found')->firstWhere('role', $role);
    if (! $step || ! $this->canReplaceOfficial($role, (int) $step['approver_id'])) {
      return;
    }

    $this->startOfficialWizard($role, (int) $step['approver_id']);
  }

  private function startOfficialWizard(string $role, ?int $replaceId): void
  {
    $this->reset(['officialSearch', 'officialCandidateId', 'officialPositionId']);
    $this->officialRole = $role;
    $this->officialReplaceId = $replaceId;
    $this->officialStep = 1;
    $this->showOfficialModal = true;
  }

  /** Langkah 1: pegawai dipilih. Jabatan yang sudah sesuai langsung ke konfirmasi. */
  public function pickOfficialCandidate(int $candidateId): void
  {
    $pelaksana = $this->pelaksana();
    $candidate = app(SppdWorkflowService::class)->officialCandidate((string) $this->officialRole, $pelaksana, $candidateId);
    if (! $candidate || ! $this->canManageDepartment(User::find($candidateId)?->department_id)) {
      $this->toastError('Pegawai tidak dapat dipilih untuk jabatan ini.');
      return;
    }

    $this->officialCandidateId = $candidateId;
    $this->officialPositionId = null;

    if ($candidate['fits']) {
      $this->officialStep = 3;
      return;
    }

    // Saran jabatan (jabatan pejabat lama, atau yang cocok dengan nama instansi) langsung terpilih.
    $suggested = collect(app(SppdWorkflowService::class)->officialPositions((string) $this->officialRole, $pelaksana, $candidateId, $this->officialReplaceId))
      ->firstWhere('suggested', true);
    $this->officialPositionId = $suggested['id'] ?? null;
    $this->officialStep = 2;
  }

  /** Langkah 2: pilih jabatan yang sudah ada. */
  public function chooseOfficialPosition(int $positionId): void
  {
    $position = collect(app(SppdWorkflowService::class)->officialPositions((string) $this->officialRole, $this->pelaksana(), (int) $this->officialCandidateId, $this->officialReplaceId))
      ->firstWhere('id', $positionId);
    if (! $position || $position['holder']) {
      return;
    }

    $this->officialPositionId = $positionId;
  }

  /**
   * Langkah 2: jabatan baru dari form Tambah Jabatan (sama dengan halaman Data Jabatan,
   * tingkatnya dikunci ke role tahap) langsung dipilih.
   */
  #[On('position-saved')]
  public function onPositionSaved(int $id, bool $created = false): void
  {
    if (! $created || ! $this->showOfficialModal || $this->officialStep !== 2) {
      return;
    }

    $this->chooseOfficialPosition($id);
    if ($this->officialPositionId !== $id) {
      $this->toastError('Jabatan "' . Position::find($id)?->name . '" sudah dijabat pegawai lain di unit ini.');
    }
  }

  /** Lanjut / kembali antar langkah wizard. */
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

  /**
   * Langkah 3: tetapkan pegawai (dan jabatannya bila diganti), lalu periksa ulang alur.
   * Aturan role mengikuti form Pegawai (lihat SppdWorkflowService::appointOfficial).
   */
  public function confirmOfficial(): void
  {
    $pelaksana = $this->pelaksana();
    $candidate = User::findOrFail((int) $this->officialCandidateId);

    if (! $this->canManageDepartment($pelaksana->department_id) || ! $this->canManageDepartment($candidate->department_id)) {
      $this->toastError('Pegawai berada di luar lingkup unit kerja Anda.');
      return;
    }

    $outgoing = $this->officialReplaceId ? User::find($this->officialReplaceId) : null;
    if ($this->officialReplaceId && ! $this->canReplaceOfficial((string) $this->officialRole, $this->officialReplaceId)) {
      $this->toastError('Pejabat ini berada di luar lingkup unit kerja Anda.');
      return;
    }

    $service = app(SppdWorkflowService::class);
    try {
      $appointed = $service->appointOfficial(Auth::user(), $pelaksana, $this->domain, (string) $this->officialRole, $candidate->id, $this->officialPositionId, $this->officialReplaceId);
    } catch (\RuntimeException $e) {
      $this->toastError($e->getMessage());
      return;
    }

    $label = $service->roleLabel((string) $this->officialRole);
    $this->toastSuccess($outgoing
      ? "{$appointed->name} menggantikan {$outgoing->name} sebagai {$label}."
      : "{$appointed->name} ditetapkan sebagai {$label}.");
    $this->checkWorkflow();
  }

  /**
   * Pengguna boleh melengkapi tahap bila instansi asal calonnya dalam lingkupnya —
   * Sekda/Wali Kota hanya oleh Admin Sekretariat Daerah atau Super Admin.
   */
  private function canCompleteOfficial(string $role): bool
  {
    $root = app(SppdWorkflowService::class)->officialPoolRoot($role, $this->pelaksana());

    return $root !== null && $this->canManageDepartment($root->id);
  }

  /** Mengganti pejabat: lingkupnya sama dengan melengkapi, dan pejabat lamanya juga dalam lingkup pengguna. */
  private function canReplaceOfficial(string $role, int $officialId): bool
  {
    return $this->canCompleteOfficial($role)
      && $this->canManageDepartment(User::find($officialId)?->department_id);
  }

  private function pelaksana(): User
  {
    return User::with('department.parent')->findOrFail($this->user_id);
  }

  /** Super Admin mengelola semua unit; Admin OPD hanya unit dalam lingkupnya. */
  private function canManageDepartment(?int $departmentId): bool
  {
    $actor = Auth::user();
    if ($actor->hasRole('super_admin')) {
      return true;
    }

    $dept = $actor->department;

    return $dept
      ? $dept->getScopedRelatedIds()->contains($departmentId)
      : $departmentId === $actor->department_id;
  }

  public function submit()
  {
    $this->validate([
      'user_id' => 'required|exists:users,id',
      'domain' => 'required|in:' . implode(',', SppdDomain::values()),
    ]);

    if (! $this->isComplete) {
      return;
    }

    return $this->redirect(
      route('sppd.create.details', [
        'user_id' => $this->user_id,
        'domain' => $this->domain,
      ]),
      navigate: true
    );
  }

  public function render()
  {
    $query = User::where('is_active', true)
      // Pegawai tanpa unit kerja tidak bisa jadi pelaksana (tak punya anggaran/alur).
      ->whereNotNull('department_id')
      // Akun administratif (super_admin & admin_opd) bukan pelaksana perjalanan.
      ->whereDoesntHave('roles', function ($q) {
        $q->whereIn('name', ['super_admin', 'admin_opd']);
      });

    // Filter: Admin OPD bisa melihat pegawai di instansinya + sub-department
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $query->whereIn('department_id', $dept->getScopedRelatedIds());
      } else {
        $query->where('department_id', Auth::user()->department_id);
      }
    }

    // Pencarian server-side: hanya muat sebagian data (bukan seluruh pegawai).
    if (trim($this->searchUser) !== '') {
      $term = trim($this->searchUser);
      $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('nip', 'like', "%{$term}%");
      });
    }

    $limit = 25;
    $users = $query->with('department.parent.parent.parent')->orderBy('name')->limit($limit + 1)->get();
    $usersHasMore = $users->count() > $limit;
    $users = $users->take($limit);

    // Pegawai terpilih untuk ditampilkan di trigger (tetap tampil walau di luar hasil pencarian).
    $selectedUser = $this->user_id ? User::with('department.parent.parent.parent')->find($this->user_id) : null;

    $workflowService = app(SppdWorkflowService::class);
    $isSuperAdmin = Auth::user()->hasRole('super_admin');

    // Super Admin melihat pegawai seluruh instansi: tampilkan asal instansi induk & unit kerja.
    $userOrigins = $isSuperAdmin
      ? $users->mapWithKeys(fn (User $u) => [$u->id => $this->originOf($u)])->filter()->all()
      : [];
    $pelaksanaOrigin = $isSuperAdmin && $selectedUser ? $this->originOf($selectedUser) : null;

    // Data wizard Lengkapi Pejabat untuk langkah yang sedang tampil (hanya saat modal terbuka).
    $official = null;
    $wizardStep = $this->officialReplaceId
      ? collect($this->steps)->where('status', 'found')->where('role', $this->officialRole)->firstWhere('approver_id', $this->officialReplaceId)
      : collect($this->steps)->where('status', 'not_found')->firstWhere('role', $this->officialRole);
    if ($this->showOfficialModal && $selectedUser && $wizardStep) {
      $role = $wizardStep['role'];
      $candidate = $this->officialCandidateId
        ? $workflowService->officialCandidate($role, $selectedUser, $this->officialCandidateId)
        : null;
      $positions = $candidate && $this->officialStep >= 2
        ? $workflowService->officialPositions($role, $selectedUser, $candidate['id'], $this->officialReplaceId)
        : [];
      $outgoing = $this->officialReplaceId ? User::with(['position', 'department'])->find($this->officialReplaceId) : null;

      $official = [
        'role'       => $role,
        'label'      => $wizardStep['role_label'],
        'cityWide'   => in_array($role, SppdWorkflowService::CITY_WIDE_ROLES, true),
        'step'       => $candidate ? $this->officialStep : 1,
        'candidate'  => $candidate,
        'candidates' => $this->officialStep === 1 || ! $candidate
          ? $workflowService->officialCandidates($role, $selectedUser, $this->officialSearch)
          : [],
        'positions'  => $positions,
        'position'   => collect($positions)->firstWhere('id', $this->officialPositionId),
        // Pejabat lama: kembali menjadi Staf; jabatannya dilepas bila jabatan itu yang memberi role-nya.
        'outgoing'   => $outgoing ? [
          'name'     => $outgoing->name,
          'position' => $outgoing->position?->name,
          'released' => $workflowService->releasedPosition($outgoing, $role, $this->officialPositionId),
          'newRole'  => $workflowService->roleLabel($workflowService->releasedRole($role)),
          'pending'  => SppdApproval::where('approver_id', $outgoing->id)->where('status', ApprovalStatus::PENDING)->count(),
        ] : null,
      ];
    }

    // Tahap kosong yang bisa dilengkapi pengguna ini (selain itu tampil keterangan siapa yang mengatur).
    $completable = $selectedUser
      ? collect($this->steps)->where('status', 'not_found')->pluck('role')->unique()
        ->mapWithKeys(fn (string $role) => [$role => $this->canCompleteOfficial($role)])->all()
      : [];

    // Tahap terisi yang pejabatnya bisa diganti pengguna ini (ikon edit di node).
    $replaceable = $selectedUser
      ? collect($this->steps)->where('status', 'found')
        ->mapWithKeys(fn (array $step) => [$step['role'] => $this->canReplaceOfficial($step['role'], (int) $step['approver_id'])])->all()
      : [];

    return view('livewire.sppd.create', compact(
      'users', 'usersHasMore', 'selectedUser', 'isSuperAdmin', 'pelaksanaOrigin', 'userOrigins', 'official', 'completable', 'replaceable'
    ))->title('Buat SPPD Baru');
  }

  /**
   * Asal pegawai: instansi teratas (OPD, Setda, Kecamatan, Dinkes, ...) dan unit
   * kerjanya bila berbeda, mis. Dinas Kominfo › Bidang Penyelenggaraan e-Government.
   *
   * @return array{instansi:string, unit:?string}|null
   */
  private function originOf(User $user): ?array
  {
    $unit = $user->department;
    if (! $unit) {
      return null;
    }

    $top = $unit;
    while ($top->parent) {
      $top = $top->parent;
    }

    return ['instansi' => $top->name, 'unit' => $top->id !== $unit->id ? $unit->name : null];
  }
}
