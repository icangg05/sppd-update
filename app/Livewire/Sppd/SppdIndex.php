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
      ''            => 'Semua Jabatan',
      'kepala_opd'  => 'Kepala OPD',
      'eselon_staf' => 'Eselon III, IV & Staf',
      'anggota_dprd' => 'Anggota DPRD',
      'staff_dprd'  => 'Staff DPRD',
      'sekwan'      => 'Sekwan',
    ];
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

  public function deleteSppd(int $id): void
  {
    $sppd = SppdRequest::findOrFail($id);

    if ($sppd->status->value === 'in_progress' && Auth::user()->hasAnyRole(['admin_opd', 'super_admin'])) {
      $sppd->delete();
      $this->toastSuccess('Pengajuan SPPD berhasil dibatalkan dan dihapus.');
    } else {
      $this->toastError('Anda tidak memiliki hak untuk membatalkan pengajuan ini atau status SPPD tidak dalam proses.');
    }
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
          $allowedIds = $dept->getAllRelatedIds();
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

    $sppds = $query->latest()->paginate(15);
    $statuses = SppdStatus::cases();
    $domains = SppdDomain::cases();
    $title = $isApprovalMode ? 'Persetujuan' : 'Daftar SPPD';

    $activeFilterLabel = $this->activeFilterLabel();

    return view('livewire.sppd.index', compact('sppds', 'statuses', 'domains', 'isApprovalMode', 'activeFilterLabel'))
      ->title($title);
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
