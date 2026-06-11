<?php

namespace App\Livewire\Sppd;

use App\Enums\SppdStatus;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class SppdCreate extends Component
{
  public ?int $user_id = null;
  public string $domain = 'dalam_daerah';
  public array $steps = [];
  public bool $hasHeader = false;
  public bool $isComplete = false;
  public string $errorMessage = '';
  public string $errorMessageName = '';
  public string $errorMessageRole = '';
  public array $userInfo = [];

  public function mount(): void
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Hanya Admin OPD atau Super Admin yang dapat membuat SPPD.');
  }

  public function updatedUserId(): void
  {
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
    $hasActiveTravel = SppdRequest::where('user_id', $user->id)
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
              ->where('end_date', '>=', today());
          });
      })
      ->exists();

    if ($hasActiveTravel) {
      $this->errorMessageName = $user->name;
      $this->errorMessage = 'masih memiliki SPPD aktif (sedang dalam proses approval atau masih dalam periode perjalanan).';
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
    $allStepsFound = true;
    foreach ($this->steps as $step) {
      if ($step['status'] !== 'found') {
        $allStepsFound = false;
      }
    }

    if (! $allStepsFound) {
      $this->errorMessage = 'Ada pejabat struktural yang belum ditentukan dalam alur ini. Harap lengkapi struktur organisasi di menu Unit Kerja.';
      return;
    }

    if (! $this->hasHeader) {
      $this->errorMessage = 'Unit kerja ' . $this->userInfo['department'] . ' belum mengunggah Kop Surat Resmi. Harap hubungi Admin OPD Anda untuk melengkapi data dokumen.';
      return;
    }

    $this->isComplete = true;
  }

  public function submit()
  {
    $this->validate([
      'user_id' => 'required|exists:users,id',
      'domain' => 'required|in:dalam_daerah,lddp,ldlp',
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
    $query = User::where('is_active', true);

    // Filter: Admin OPD bisa melihat pegawai di instansinya + sub-department
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $query->whereIn('department_id', $dept->getAllRelatedIds());
      } else {
        $query->where('department_id', Auth::user()->department_id);
      }
    }

    $users = $query->orderBy('name')->get();

    return view('livewire.sppd.create', compact('users'))
      ->title('Buat SPPD Baru');
  }
}
