<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Support\Collection;
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

  /**
   * Hasil seret-lepas (wire:sort): tahap bernomor $item (mulai 1, karena wire:sort
   * menganggap kunci 0 kosong) dipindah ke indeks $position (mulai 0).
   */
  public function sortStep(int $item, int $position): void
  {
    $from = $item - 1;
    if (! isset($this->steps[$from])) {
      return;
    }

    $moved = array_splice($this->steps, $from, 1);
    array_splice($this->steps, max(0, min($position, count($this->steps))), 0, $moved);
  }

  /** Pindah satu langkah lewat keyboard (panah atas/bawah pada pegangan seret). */
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
        'applicant_role.*'  => [Rule::exists('roles', 'name'), Rule::notIn([User::TENAGA_MITRA_ROLE])],
        'destination'       => ['required', 'array', 'min:1'],
        'destination.*'     => [Rule::enum(SppdDomain::class)],
        'steps'             => ['required', 'array', 'min:1'],
        // Tingkat persetujuan terendah Kabid/Irban/Kabag: Staf & Kasubag tidak menyetujui.
        // Termasuk role tahap eksternal (mis. Kepala Dinas Kesehatan), lihat EXTERNAL_STEP_ROLES.
        'steps.*.role'      => ['required', 'string', Rule::in([...SppdWorkflowService::APPROVER_ROLES, ...array_keys(SppdWorkflowService::EXTERNAL_STEP_ROLES)])],
      ], [
        'department_type.required' => 'Centang minimal satu tipe instansi.',
        'applicant_role.required'  => 'Centang minimal satu peran pemohon.',
        'destination.required'     => 'Centang minimal satu wilayah tujuan.',
        'steps.*.role.in'          => 'Role ini tidak dapat menjadi tahap persetujuan.',
      ], [
        'steps.*.role' => 'role tahapan',
      ]);
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    // Dua alur aktif dengan cakupan beririsan: hanya yang dibuat lebih dulu yang terpakai.
    if (($count = $this->overlaps()->count()) > 0) {
      $this->addError('scope', "Cakupan alur ini bentrok dengan {$count} alur aktif lain.");
      $this->toastError('Cakupan alur bentrok dengan alur aktif lain. Lihat rinciannya di formulir.');
      return;
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

  /**
   * Alur aktif lain yang cakupannya beririsan dengan isian form. Alur nonaktif tidak
   * dicek, jadi alur pengganti bisa disiapkan dulu lalu diaktifkan setelah yang lama
   * dinonaktifkan.
   */
  private function overlaps(): Collection
  {
    if (! $this->is_active || ! $this->department_type || ! $this->applicant_role || ! $this->destination) {
      return collect();
    }

    return SppdWorkflow::overlapping(
      array_values($this->department_type), array_values($this->applicant_role), array_values($this->destination), $this->workflow?->id,
    );
  }

  public function render()
  {
    // Alur khusus satu tipe instansi memakai sebutan role tipe itu (Rumah Sakit: Kepala OPD = Direktur).
    $onlyType = count($this->department_type) === 1 ? DepartmentType::tryFrom((string) array_values($this->department_type)[0]) : null;
    $roleLabelOf = fn ($role) => $onlyType?->roleTitle($role->name) ?? $role->label ?? $role->name;
    // Tenaga Mitra tidak pernah menjadi pelaksana, jadi bukan role pemohon alur.
    $roles = Role::where('name', '!=', User::TENAGA_MITRA_ROLE)->orderBy('name')->get();

    // Irisan ditampilkan langsung saat centang diubah; null (data lama) = semua.
    $labels = fn (?array $values, \Closure $label) => $values === null ? 'Semua' : collect($values)->map($label)->implode(', ');
    $conflicts = $this->overlaps()->map(fn (array $o) => [
      'id'              => $o['workflow']->id,
      'name'            => $o['workflow']->name,
      'department_type' => $labels($o['department_type'], fn ($v) => DepartmentType::tryFrom($v)?->label() ?? $v),
      'applicant_role'  => $labels($o['applicant_role'], fn ($v) => ($r = $roles->firstWhere('name', $v)) ? $roleLabelOf($r) : $v),
      'destination'     => $labels($o['destination'], fn ($v) => SppdDomain::tryFrom($v)?->shortLabel() ?? $v),
    ])->all();

    return view('livewire.workflows.form', [
      'roleLabelOf'     => $roleLabelOf,
      'departmentTypes' => DepartmentType::cases(),
      'domains'         => SppdDomain::cases(),
      'roles'           => $roles,
      'conflicts'       => $conflicts,
      'approverRoles'   => Role::whereIn('name', SppdWorkflowService::APPROVER_ROLES)->orderBy('name')->get(),
      'externalStepRoles' => SppdWorkflowService::EXTERNAL_STEP_ROLES,
    ])->title($this->isEdit ? 'Edit Workflow SPPD' : 'Tambah Workflow SPPD');
  }
}
