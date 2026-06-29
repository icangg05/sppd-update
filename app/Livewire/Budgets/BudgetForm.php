<?php

namespace App\Livewire\Budgets;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Budget;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BudgetForm extends Component
{
  use InteractsWithToast;

  public ?Budget $budget = null;
  public bool $isEdit = false;
  public bool $isSuperAdmin = false;

  // Form fields
  public $department_id = '';
  public $year = '';
  public string $account_code = '';
  public string $type = '';
  public string $source = '';
  public string $program = '';
  public string $activity = '';
  public string $total_amount = '';
  public string $description = '';

  public function mount(?Budget $budget = null): void
  {
    $this->isSuperAdmin = $this->currentUser()->hasRole('super_admin');

    if ($budget && $budget->exists) {
      $this->isEdit = true;
      $this->budget = $budget;

      $this->department_id = $budget->department_id;
      $this->year         = (string) $budget->year;
      $this->account_code = $budget->account_code ?? '';
      $this->type         = $budget->type ?? '';
      $this->source       = $budget->source ?? '';
      $this->program      = $budget->program ?? '';
      $this->activity     = $budget->activity ?? '';
      $this->total_amount = (string) $budget->total_amount;
      $this->description  = $budget->description ?? '';

      return;
    }

    // Default untuk data baru.
    $this->year = (string) now()->year;

    if (! $this->isSuperAdmin) {
      $this->department_id = $this->currentUser()->department_id;
    }
  }

  /**
   * Daftar tahun: 5 tahun ke belakang termasuk tahun berjalan (mis. 2026–2022).
   *
   * @return list<int>
   */
  public function yearOptions(): array
  {
    $current = (int) now()->year;

    return range($current, $current - 4);
  }

  private function currentUser(): User
  {
    /** @var User $user */
    $user = Auth::user();

    return $user;
  }

  protected function rules(): array
  {
    return [
      'department_id' => ['required', 'exists:departments,id'],
      'year'          => ['required', 'integer', 'min:2000', 'max:2100'],
      'program'       => ['required', 'string', 'max:255'],
      'activity'      => ['required', 'string', 'max:255'],
      'account_code'  => ['required', 'string', 'max:255'],
      'description'   => ['required', 'string'],
      'type'          => ['required', 'string'],
      'source'        => ['required', 'string', 'in:APBD,APBD-P,APBN'],
      'total_amount'  => ['required', 'numeric', 'min:0'],
    ];
  }

  protected function validationAttributes(): array
  {
    return [
      'department_id' => 'SKPD / unit kerja',
      'year'          => 'tahun anggaran',
      'program'       => 'nama program',
      'activity'      => 'nama kegiatan',
      'account_code'  => 'kode rekening',
      'description'   => 'uraian',
      'type'          => 'jenis anggaran',
      'source'        => 'mata anggaran',
      'total_amount'  => 'pagu total',
    ];
  }

  public function save()
  {
    // Admin OPD hanya boleh memakai instansinya sendiri.
    if (! $this->isSuperAdmin) {
      $this->department_id = $this->currentUser()->department_id;
    }

    try {
      $data = $this->validate();
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    if ($this->isEdit) {
      $this->budget->update($data);

      return redirect()->route('master.budgets.index')
        ->with('success', 'Data anggaran berhasil diperbarui.');
    }

    Budget::create($data);

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil ditambahkan.');
  }

  /**
   * Daftar unit kerja hierarkis dengan indentasi (display_name),
   * mengikuti pola pada form Pegawai.
   */
  private function getHierarchicalDepartments(): array
  {
    if ($this->isSuperAdmin) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      $dept  = $this->currentUser()->department;
      $roots = $dept ? Department::where('id', $dept->id)->get() : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list);
    }

    return $list;
  }

  private function flattenDepartment(Department $dept, int $level, array &$list): void
  {
    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[]             = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function render()
  {
    $departments = $this->getHierarchicalDepartments();

    /** @var \Illuminate\View\View $view */
    $view = view('livewire.budgets.form', [
      'departments' => $departments,
      'years'       => $this->yearOptions(),
    ]);

    return $view->title($this->isEdit ? 'Edit Anggaran' : 'Tambah Anggaran');
  }
}
