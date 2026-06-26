<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdWorkflow;
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
      $this->validate([
        'name'         => ['required', 'string', 'max:255'],
        'steps'        => ['required', 'array', 'min:1'],
        'steps.*.role' => ['required', 'string'],
      ], [], [
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
    ])->title($this->isEdit ? 'Edit Workflow SPPD' : 'Tambah Workflow SPPD');
  }
}
