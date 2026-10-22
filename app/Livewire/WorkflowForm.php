<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdWorkflow;
use App\Services\SppdWorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class WorkflowForm extends Component
{
  use InteractsWithToast;

  public ?SppdWorkflow $workflow = null;
  public bool $isEdit = false;

  public string $name = '';
  public array $department_type = [];
  public array $applicant_role = [];
  public array $destination = [];
  public bool $is_active = true;

  /**
   * Atasan langsung pemohon: 'any' = tidak dibedakan (dipilih eksplisit),
   * 'specific' = hanya bila atasan langsungnya salah satu role di $supervisor_role.
   */
  public string $supervisor_mode = 'any';
  public array $supervisor_role = [];

  /** @var array<int, array{role:string, signs_spt:bool, signs_sppd:bool}> */
  public array $steps = [];

  public function mount(?SppdWorkflow $workflow = null): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    if ($workflow && $workflow->exists) {
      $this->isEdit          = true;
      $this->workflow        = $workflow;
      $this->name            = $workflow->name;
      $this->department_type = $workflow->department_type ?? [];
      $this->applicant_role  = $workflow->applicant_role ?? [];
      $this->destination     = $workflow->destination ?? [];
      $this->supervisor_mode = empty($workflow->supervisor_role) ? 'any' : 'specific';
      $this->supervisor_role = $workflow->supervisor_role ?? [];
      $this->is_active       = (bool) $workflow->is_active;
      $this->steps           = $this->normalizeSteps($workflow->steps ?? []);
    }

    if (empty($this->steps)) {
      $this->steps = [$this->emptyStep()];
    }
  }

  private function emptyStep(): array
  {
    return ['role' => '', 'signs_spt' => false, 'signs_sppd' => false];
  }

  /**
   * Normalisasi steps lama (bisa berupa array string) ke bentuk baku.
   */
  private function normalizeSteps(array $steps): array
  {
    return collect($steps)->map(function ($step) {
      if (is_array($step)) {
        return [
          'role'       => $step['role'] ?? '',
          'signs_spt'  => (bool) ($step['signs_spt'] ?? false),
          'signs_sppd' => (bool) ($step['signs_sppd'] ?? false),
        ];
      }

      return ['role' => (string) $step, 'signs_spt' => false, 'signs_sppd' => false];
    })->values()->all();
  }

  public function addStep(): void
  {
    $this->steps[] = $this->emptyStep();
  }

  public function removeStep(int $index): void
  {
    if (count($this->steps) <= 1) {
      $this->toastError('Workflow wajib memiliki sekurang-kurangnya 1 tahapan.');
      return;
    }

    unset($this->steps[$index]);
    $this->steps = array_values($this->steps);
  }

  public function moveStep(int $index, int $direction): void
  {
    $target = $index + $direction;
    if ($target < 0 || $target >= count($this->steps)) {
      return;
    }

    [$this->steps[$index], $this->steps[$target]] = [$this->steps[$target], $this->steps[$index]];
    $this->steps = array_values($this->steps);
  }

  /**
   * Pastikan hanya satu tahapan yang menandatangani SPT, dan satu untuk SPPD.
   * Saat satu dicentang, kosongkan yang lain secara otomatis.
   */
  public function updated(string $name, $value): void
  {
    if ($value && preg_match('/^steps\.(\d+)\.(signs_spt|signs_sppd)$/', $name, $m)) {
      $idx   = (int) $m[1];
      $field = $m[2];

      foreach ($this->steps as $i => $step) {
        if ($i !== $idx) {
          $this->steps[$i][$field] = false;
        }
      }
    }
  }

  public function save()
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);

    try {
      // Cakupan wajib dicentang eksplisit: tidak ada makna "kosong = berlaku untuk semua".
      $this->validate([
        'name'              => ['required', 'string', 'max:255'],
        'department_type'   => ['required', 'array', 'min:1'],
        'department_type.*' => [Rule::enum(DepartmentType::class)],
        'applicant_role'    => ['required', 'array', 'min:1'],
        'applicant_role.*'  => [Rule::exists('roles', 'name')],
        'destination'       => ['required', 'array', 'min:1'],
        'destination.*'     => [Rule::enum(SppdDomain::class)],
        // Atasan langsung: "tidak dibedakan" dipilih eksplisit; bila dibedakan, centang minimal satu.
        'supervisor_mode'   => ['required', Rule::in(['any', 'specific'])],
        'supervisor_role'   => ['exclude_unless:supervisor_mode,specific', 'required', 'array', 'min:1'],
        'supervisor_role.*' => ['exclude_unless:supervisor_mode,specific', Rule::in(SppdWorkflowService::APPROVER_ROLES)],
        'steps'             => ['required', 'array', 'min:1'],
        // Tingkat persetujuan terendah Kabid/Irban/Kabag: Staf & Kasubag tidak menyetujui.
        'steps.*.role'      => ['required', 'string', Rule::in(SppdWorkflowService::APPROVER_ROLES)],
      ], [
        'department_type.required' => 'Centang minimal satu tipe instansi.',
        'applicant_role.required'  => 'Centang minimal satu peran pemohon.',
        'destination.required'     => 'Centang minimal satu wilayah tujuan.',
        'supervisor_role.required' => 'Centang minimal satu jabatan atasan langsung.',
        'steps.*.role.in'          => 'Role ini tidak dapat menjadi tahap persetujuan.',
      ], [
        'steps.*.role' => 'role tahapan',
      ]);
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    $sptCount = collect($this->steps)->where('signs_spt', true)->count();
    if ($sptCount !== 1) {
      $this->toastError('Harus ada tepat satu tahapan yang ditugaskan menandatangani SPT.');
      return;
    }

    $sppdCount = collect($this->steps)->where('signs_sppd', true)->count();
    if ($sppdCount !== 1) {
      $this->toastError('Harus ada tepat satu tahapan yang ditugaskan menandatangani SPPD.');
      return;
    }

    $data = [
      'name'            => trim($this->name),
      'department_type' => array_values($this->department_type),
      'applicant_role'  => array_values($this->applicant_role),
      'destination'     => array_values($this->destination),
      'supervisor_role' => $this->supervisor_mode === 'specific' ? array_values($this->supervisor_role) : null,
      'is_active'       => $this->is_active,
      'steps'           => array_map(fn ($s) => [
        'role'       => $s['role'],
        'signs_spt'  => (bool) $s['signs_spt'],
        'signs_sppd' => (bool) $s['signs_sppd'],
      ], array_values($this->steps)),
    ];

    if ($this->isEdit) {
      $this->workflow->update($data);

      return redirect()->route('master.workflows.index')
        ->with('success', 'Workflow SPPD berhasil diperbarui.');
    }

    SppdWorkflow::create($data);

    return redirect()->route('master.workflows.index')
      ->with('success', 'Workflow SPPD berhasil ditambahkan.');
  }

  public function render()
  {
    return view('livewire.workflows.form', [
      'departmentTypes' => DepartmentType::cases(),
      'domains'         => SppdDomain::cases(),
      'roles'           => Role::orderBy('name')->get(),
      'approverRoles'   => Role::whereIn('name', SppdWorkflowService::APPROVER_ROLES)->orderBy('name')->get(),
    ])->title($this->isEdit ? 'Edit Workflow SPPD' : 'Tambah Workflow SPPD');
  }
}
