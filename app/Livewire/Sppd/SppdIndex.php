<?php

namespace App\Livewire\Sppd;

use App\Enums\DepartmentType;
use App\Enums\EmployeeType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SppdIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  public ?string $errorMessage = null;
  public array $simulatedSteps = [];
  public bool $showWorkflowModal = false;

  // Konfirmasi hapus SPPD
  public bool $showDeleteModal = false;
  public ?int $deleteId = null;
  public ?string $deleteLabel = null;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $status = '';

  #[Url(keep: true)]
  public string $domain = '';

  #[Url(keep: true)]
  public string $jabatan = '';

  #[Url(keep: true)]
  public string $filter = '';

  private const SESSION_KEY = 'sppd.index.filters';

  public static function jabatanLabels(): array
  {
    return [
      ''              => 'Semua Jabatan',
      'kepala_opd'    => 'Kepala OPD',
      'eselon_staf'   => 'Eselon III, IV & Staf',
      'sekda_asisten' => 'Sekda, Asisten & Kabag',
      'staf_setda'    => 'Kasubag & Staf',
      'camat'         => 'Camat & Sekcam',
      'lurah'         => 'Lurah',
      'kapus'         => 'Kepala Puskesmas',
      'anggota_dprd'  => 'Anggota DPRD',
      'staff_dprd'    => 'Staff DPRD',
      'sekwan'        => 'Sekwan',
    ];
  }

  /**
   * Daftar tab filter jabatan yang ditampilkan sesuai jenis OPD (akar),
   * meniru perilaku sistem lama (sppd-2026) yang dibedakan per jenis_skpd.
   *
   * @return list<string>
   */
  public static function jabatanTabsFor(?DepartmentType $type): array
  {
    return match ($type) {
      DepartmentType::DPRD      => ['anggota_dprd', 'staff_dprd', 'sekwan'],
      DepartmentType::SETDA,
      DepartmentType::ASISTEN   => ['sekda_asisten', 'staf_setda'],
      DepartmentType::KECAMATAN => ['camat', 'eselon_staf'],
      DepartmentType::KELURAHAN => ['lurah', 'eselon_staf'],
      DepartmentType::PUSKESMAS => ['kapus', 'eselon_staf'],
      DepartmentType::DINKES    => ['kepala_opd', 'eselon_staf'],
      default                   => ['kepala_opd', 'eselon_staf'],
    };
  }

  /**
   * Jenis OPD akar milik user (telusuri ke parent teratas).
   */
  protected function rootDepartmentType(): ?DepartmentType
  {
    return Auth::user()->department?->getRootDepartment()?->type;
  }

  /**
   * Tab jabatan untuk user saat ini. Puskesmas (anak Dinkes) memakai konteksnya
   * sendiri — filter "Kepala Puskesmas" muncul di puskesmas, bukan di Dinkes induknya.
   */
  protected function currentJabatanTabs(): array
  {
    if (Auth::user()->department?->type === DepartmentType::PUSKESMAS) {
      return self::jabatanTabsFor(DepartmentType::PUSKESMAS);
    }

    return self::jabatanTabsFor($this->rootDepartmentType());
  }

  public static function savedFilters(): array
  {
    return session(self::SESSION_KEY, []);
  }

  public function isApprovalMode(): bool
  {
    return $this->filter === 'approval';
  }

  public function mount(): void
  {
    if ($this->isApprovalMode()) {
      return;
    }

    $saved = self::savedFilters();

    if ($this->jabatan === '' && ! empty($saved['jabatan'])) {
      $this->jabatan = $saved['jabatan'];
    }

    if ($this->status === '' && ! empty($saved['status'])) {
      $this->status = $saved['status'];
    }

    if ($this->domain === '' && ! empty($saved['domain'])) {
      $this->domain = $saved['domain'];
    }

    if ($this->search === '' && ! empty($saved['search'])) {
      $this->search = $saved['search'];
    }
  }

  public function activeFilterLabel(): string
  {
    return self::jabatanLabels()[$this->jabatan] ?? 'Semua Jabatan';
  }

  protected function persistFilters(): void
  {
    if ($this->isApprovalMode()) {
      return;
    }

    session([self::SESSION_KEY => [
      'jabatan' => $this->jabatan,
      'status'  => $this->status,
      'domain'  => $this->domain,
      'search'  => $this->search,
    ]]);
  }

  public function filterByJabatan(string $value): void
  {
    $this->jabatan = $value;
    $this->persistFilters();
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->errorMessage = null;
    $this->simulatedSteps = [];
    $this->showWorkflowModal = false;

    if (! $this->isApprovalMode()) {
      $this->status = '';
      $this->domain = '';
      $this->jabatan = '';
      $this->filter = '';
      session()->forget(self::SESSION_KEY);
    }

    $this->resetPage();
  }

  public function updatedSearch(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  public function updatedStatus(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  public function updatedDomain(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  public function updatedJabatan(): void
  {
    $this->persistFilters();
    $this->resetPage();
  }

  /** Buka modal konfirmasi sebelum menghapus SPPD. */
  public function confirmDelete(int $id): void
  {
    $sppd = SppdRequest::with('user')->find($id);

    if (! $sppd) {
      return;
    }

    $this->deleteId = $id;
    $this->deleteLabel = $sppd->user?->name;
    $this->showDeleteModal = true;
  }

  public function closeDeleteModal(): void
  {
    $this->showDeleteModal = false;
    $this->deleteId = null;
    $this->deleteLabel = null;
  }

  public function deleteSppd(): void
  {
    $id = $this->deleteId;
    $this->closeDeleteModal();

    if (! $id) {
      return;
    }

    $sppd = SppdRequest::findOrFail($id);
    $user = Auth::user();

    // Super admin dapat menghapus SPPD apa pun secara permanen (apa pun statusnya).
    if ($user->hasRole('super_admin')) {
      $sppd->delete();
      $this->toastSuccess('Pengajuan SPPD berhasil dihapus.');

      return;
    }

    // Admin OPD hanya boleh membatalkan pengajuan yang masih berjalan.
    if ($sppd->status->value === 'in_progress' && $user->hasRole('admin_opd')) {
      $sppd->delete();
      $this->toastSuccess('Pengajuan SPPD berhasil dibatalkan dan dihapus.');

      return;
    }

    $this->toastError('Anda tidak memiliki hak untuk membatalkan pengajuan ini atau status SPPD tidak dalam proses.');
  }

  public function render()
  {
    $query = SppdRequest::with(['user.department', 'category', 'budget.department', 'report', 'actualExpenses', 'costDetails']);
    $isApprovalMode = $this->isApprovalMode();

    if ($isApprovalMode) {
      $pendingSppdIds = SppdApproval::readyForApprover(Auth::id())
        ->pluck('sppd_request_id');
      $query->whereIn('id', $pendingSppdIds);
    } else {
      // Batasi data hanya untuk department user (beserta sub-department) — selain super admin.
      if (! Auth::user()->hasRole('super_admin')) {
        $dept = Auth::user()->department;
        if ($dept) {
          $allowedIds = $dept->getScopedRelatedIds();
          $query->whereHas('user', function ($q) use ($allowedIds) {
            $q->whereIn('department_id', $allowedIds);
          });
        } else {
          $query->whereHas('user', function ($q) {
            $q->where('department_id', Auth::user()->department_id);
          });
        }
      }

      if ($this->status !== '') {
        $query->where('status', $this->status);
      }

      if ($this->domain !== '') {
        $query->where('domain', $this->domain);
      }

      if ($this->jabatan !== '') {
        $jabatan = $this->jabatan;
        $query->whereHas('user', function ($q) use ($jabatan) {
          if ($jabatan === 'kepala_opd') {
            $q->role('kepala_opd');
          } elseif ($jabatan === 'eselon_ii') {
            $q->role(['sekda', 'asisten', 'kepala_opd', 'sekwan']);
          } elseif ($jabatan === 'eselon_staf') {
            $q->role(['staf', 'admin_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag']);
          } elseif ($jabatan === 'eselon_iv') {
            $q->role(['kasubid_kasubag', 'sekcam', 'lurah', 'kapus']);
          } elseif ($jabatan === 'sekda_asisten') {
            $q->role(['sekda', 'asisten', 'kabid_irban_kabag']);
          } elseif ($jabatan === 'staf_setda') {
            $q->role(['kasubid_kasubag', 'staf']);
          } elseif ($jabatan === 'camat') {
            $q->role(['camat', 'sekcam']);
          } elseif ($jabatan === 'lurah') {
            $q->role(['lurah']);
          } elseif ($jabatan === 'kapus') {
            $q->role(['kapus']);
          } elseif ($jabatan === 'staf') {
            $q->role('staf');
          } elseif ($jabatan === 'anggota_dprd') {
            $q->where(function ($sub) {
              $sub->role(['anggota_dprd', 'pimpinan_dprd'])
                ->orWhere('employee_type', EmployeeType::DPRD);
            });
          } elseif ($jabatan === 'staff_dprd') {
            $q->role('staf')->whereHas('department', function ($d) {
              $d->where(function ($sub) {
                $sub->where('type', DepartmentType::DPRD)
                  ->orWhereHas('parent', function ($p) {
                    $p->where('type', DepartmentType::DPRD);
                  });
              });
            });
          } elseif ($jabatan === 'sekwan') {
            $q->role('sekwan');
          }
        });
      }
    }

    if ($this->search !== '') {
      $search = $this->search;
      $query->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
      });
    }

    $sppds = $query->latest()->paginate(15)->onEachSide(1);
    $statuses = SppdStatus::cases();
    $domains = SppdDomain::cases();
    $title = $isApprovalMode ? 'Persetujuan' : 'Daftar SPPD';

    $activeFilterLabel = $this->activeFilterLabel();

    // Super admin memakai select-search (semua jabatan), selain itu tab sesuai jenis OPD.
    $isSuperAdmin = Auth::user()->hasRole('super_admin');
    $jabatanLabels = self::jabatanLabels();
    $jabatanTabs = $this->currentJabatanTabs();
    $jabatanOptions = collect($jabatanLabels)
      ->map(fn($label, $value) => ['value' => $value, 'label' => $label])
      ->values()
      ->all();

    $statusOptions = collect([['value' => '', 'label' => 'Semua Status']])
      ->concat(collect($statuses)->map(fn($st) => [
        'value' => $st->value,
        'label' => \App\Helpers\SmartTitle::convert($st->label()),
      ]))
      ->values()
      ->all();

    $domainOptions = collect([['value' => '', 'label' => 'Semua Domain']])
      ->concat(collect($domains)->map(fn($dom) => [
        'value' => $dom->value,
        'label' => $dom->label(),
      ]))
      ->values()
      ->all();

    return view('livewire.sppd.index', compact(
      'sppds',
      'statuses',
      'domains',
      'isApprovalMode',
      'activeFilterLabel',
      'isSuperAdmin',
      'jabatanLabels',
      'jabatanTabs',
      'jabatanOptions',
      'statusOptions',
      'domainOptions',
    ))->title($title);
  }

  public function startSppdLanjutan(int $sppdId): void
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');

    $this->errorMessage = null;
    $this->simulatedSteps = [];
    $this->showWorkflowModal = false;

    $sppd = SppdRequest::with('user.department')->find($sppdId);
    if (! $sppd) {
      $this->errorMessage = 'Data SPPD tidak ditemukan.';
      return;
    }

    $user = $sppd->user;
    if (! $user) {
      $this->errorMessage = 'Pegawai pelaksana tidak ditemukan.';
      return;
    }

    // 1. Cek perjalanan aktif (lainnya)
    $hasActiveTravel = SppdRequest::where('user_id', $user->id)
      ->where('id', '!=', $sppd->id) // Abaikan SPPD saat ini
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
              ->where('end_date', '>=', today());
          });
      })
      ->exists();

    if ($hasActiveTravel) {
      $this->errorMessage = 'Pegawai ' . $user->name . ' masih memiliki SPPD aktif lainnya (sedang dalam proses approval atau masih dalam periode perjalanan).';
      return;
    }

    // 2. Cek Kop Surat
    $hasHeader = (bool) ($user->department?->getInheritedLetterhead() && Str::contains($user->department->getInheritedLetterhead(), '/'));
    if (! $hasHeader) {
      $this->errorMessage = 'Unit kerja ' . ($user->department?->name ?? 'Tanpa Unit Kerja') . ' belum mengunggah Kop Surat Resmi. Harap hubungi Admin OPD Anda untuk melengkapi data dokumen.';
      return;
    }

    // 3. Simulasi Workflow / Pengecekan Workflow & Pejabat
    $workflowService = app(SppdWorkflowService::class);
    $steps = $workflowService->simulateApprovals($user, $sppd->domain->value);

    if (empty($steps)) {
      $roleLabel = $user->roles->first()?->label ?? ($user->getRoleNames()->first() ?? 'Tanpa Role');
      $this->errorMessage = 'Aturan alur untuk kategori ini belum dibuat oleh Administrator SPPD (Super Admin) untuk role: ' . $roleLabel . '.';
      return;
    }

    $allStepsFound = true;
    foreach ($steps as $step) {
      if ($step['status'] !== 'found') {
        $allStepsFound = false;
      }
    }

    if (! $allStepsFound) {
      $this->simulatedSteps = $steps;
      $this->errorMessage = 'Ada pejabat struktural yang belum ditentukan dalam alur ini. Harap lengkapi struktur organisasi di menu Unit Kerja.';
      return;
    }

    // Jika lolos semua validasi, redirect ke create details
    $this->redirect(
      route('sppd.create.details', [
        'user_id' => $user->id,
        'domain' => $sppd->domain->value,
      ]),
      navigate: true
    );
  }
}
