<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DepartmentForm extends Component
{
  use InteractsWithToast;
  use WithFileUploads;

  public ?Department $department = null;
  public bool $isEdit = false;

  // Form fields
  public string $name = '';
  public string $code = '';
  public string $type = '';
  public $parent_id = '';
  public $head_id = '';
  public $letterhead = null;
  public $letterhead_second = null;

  // Pencarian pimpinan (server-side) agar tidak memuat seluruh pegawai sekaligus.
  public string $searchHead = '';

  public bool $isSuperAdmin = false;

  public function mount(?Department $department = null): void
  {
    $this->isSuperAdmin = $this->currentUser()->hasRole('super_admin');

    if ($department && $department->exists) {
      $this->isEdit    = true;
      $this->department = $department;

      $this->name      = $department->name;
      $this->code      = $department->code ?? '';
      $this->type      = $department->type->value;
      $this->parent_id = $department->parent_id ?? '';
      $this->head_id   = $department->head_id ?? '';

      $this->authorizeEdit();

      return;
    }

    if ($this->isSuperAdmin) {
      // Default: buat OPD induk baru (tanpa induk). Tipe default OPD.
      $this->type = DepartmentType::OPD->value;
    } else {
      // Admin OPD hanya boleh membuat sub-unit di bawah instansinya sendiri.
      $this->parent_id = $this->currentUser()->department_id;
      $this->syncTypeFromParent();
    }
  }

  /**
   * Admin OPD hanya boleh mengedit instansinya sendiri atau unit di bawahnya.
   */
  private function authorizeEdit(): void
  {
    if ($this->isSuperAdmin) {
      return;
    }

    $dept = $this->currentUser()->department;
    $allowedIds = $dept ? $dept->getAllRelatedIds() : collect([$this->currentUser()->department_id]);

    abort_unless($allowedIds->contains($this->department->id), 403, 'Anda tidak memiliki akses ke unit ini.');
  }

  /**
   * Induk OPD top-level (root) tidak boleh dipindah oleh admin OPD — itu akan
   * menggeser seluruh instansinya. Sub-unit (non-root) tetap boleh dipindah
   * di dalam lingkup organisasinya. Super admin selalu bebas mengubah.
   */
  public function isParentLocked(): bool
  {
    return $this->isEdit
      && ! $this->isSuperAdmin
      && $this->department->parent_id === null;
  }

  public function updatedParentId(): void
  {
    if ($this->isParentLocked()) {
      $this->parent_id = $this->department->parent_id ?? '';
      return;
    }

    if ($this->isRoot()) {
      if ($this->type === '') {
        $this->type = DepartmentType::OPD->value;
      }
    } else {
      $this->syncTypeFromParent();
    }
  }

  private function syncTypeFromParent(): void
  {
    $parent = $this->parent_id ? Department::find($this->parent_id) : null;
    if ($parent) {
      $this->type = $parent->type->value;
    }
  }

  /**
   * True bila ini OPD induk (tanpa instansi pengampu).
   */
  public function isRoot(): bool
  {
    return empty($this->parent_id);
  }

  public function selectHead($id): void
  {
    $this->head_id = (int) $id;
  }

  public function clearHead(): void
  {
    $this->head_id = '';
  }

  /**
   * Pengguna login sebagai App\Models\User konkret — agar method domain
   * (hasRole, department, dll.) dikenali analisis statis & autocomplete.
   */
  private function currentUser(): User
  {
    /** @var User $user */
    $user = Auth::user();

    return $user;
  }

  protected function rules(): array
  {
    $codeUnique = 'unique:departments,code' . ($this->isEdit ? ',' . $this->department->id : '');

    $rules = [
      'name'      => ['required', 'string', 'max:255', $this->uniqueNameAtSameLevelRule()],
      'type'      => ['required', 'in:' . implode(',', array_column(DepartmentType::cases(), 'value'))],
      'head_id'   => ['nullable', 'exists:users,id'],
      'parent_id' => [$this->isSuperAdmin ? 'nullable' : 'required', 'exists:departments,id'],
    ];

    // Kode & kop surat hanya relevan di tingkat induk (OPD).
    if ($this->isRoot()) {
      $rules['code'] = ['nullable', 'string', 'max:30', $codeUnique];
      $rules['letterhead'] = ['nullable', 'image', 'max:2048'];
      $rules['letterhead_second'] = ['nullable', 'image', 'max:2048'];
    }

    return $rules;
  }

  protected function validationAttributes(): array
  {
    return [
      'name'      => 'nama unit kerja',
      'parent_id' => 'instansi induk',
      'head_id'   => 'kepala / pimpinan',
      'type'      => 'tipe entitas',
    ];
  }

  /**
   * Cegah nama unit kerja duplikat pada tingkat/induk yang sama
   * (parent_id sama), tidak peduli huruf besar/kecil.
   */
  protected function uniqueNameAtSameLevelRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      $parentId = $this->parent_id ? (int) $this->parent_id : null;

      $exists = Department::query()
        ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'))
        ->when($parentId !== null, fn ($q) => $q->where('parent_id', $parentId))
        ->when($this->isEdit, fn ($q) => $q->where('id', '!=', $this->department->id))
        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($value))])
        ->exists();

      if ($exists) {
        $tingkat = $parentId ? 'di bawah instansi induk yang sama' : 'pada tingkat induk (OPD)';
        $fail("Nama unit kerja \"{$value}\" sudah ada {$tingkat}.");
      }
    };
  }

  public function save()
  {
    try {
      $this->validate();
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    // Batasi induk pada lingkup instansi sendiri untuk admin OPD (saat create).
    if (! $this->isSuperAdmin && ! $this->isParentLocked()) {
      $dept = $this->currentUser()->department;
      $allowedIds = $dept ? $dept->getAllRelatedIds() : collect([$this->currentUser()->department_id]);
      if (! $allowedIds->contains((int) $this->parent_id)) {
        $this->addError('parent_id', 'Anda hanya dapat menempatkan unit di bawah instansi Anda sendiri.');
        return;
      }
    }

    $data = [
      'name'      => trim($this->name),
      'type'      => $this->type,
      'parent_id' => $this->parent_id ?: null,
      'head_id'   => $this->head_id ?: null,
    ];

    // Hitung level & warisi atribut dari induk.
    if ($data['parent_id']) {
      $parent = Department::find($data['parent_id']);
      $data['level'] = ($parent->level ?? 1) + 1;
      $data['type']  = $parent->type->value; // sub-unit selalu ikut tipe induk
    } else {
      $data['level'] = 1;
      $data['code']  = $this->code ?: null;

      $cleanName = $this->cleanName($data['name']);

      if ($this->letterhead) {
        $this->deleteOldFile($this->department?->letterhead);
        $ext = $this->letterhead->getClientOriginalExtension();
        $data['letterhead'] = $this->letterhead->storeAs('kop_surat', "{$cleanName}_primary.{$ext}", 'public');
      }
      if ($this->letterhead_second) {
        $this->deleteOldFile($this->department?->letterhead_second);
        $ext = $this->letterhead_second->getClientOriginalExtension();
        $data['letterhead_second'] = $this->letterhead_second->storeAs('kop_surat', "{$cleanName}_secondary.{$ext}", 'public');
      }
    }

    if ($this->isEdit) {
      $this->department->update($data);

      return redirect()->route('master.departments.index')
        ->with('success', "Instansi/OPD {$this->department->name} berhasil diperbarui.");
    }

    Department::create($data);

    return redirect()->route('master.departments.index')
      ->with('success', "Instansi {$data['name']} berhasil ditambahkan.");
  }

  private function deleteOldFile(?string $path): void
  {
    if ($path && Str::contains($path, '/')) {
      Storage::disk('public')->delete($path);
    }
  }

  private function cleanName(string $name): string
  {
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '_', $name));
    $clean = preg_replace('/_+/', '_', $clean);

    return trim($clean, '_');
  }

  /**
   * Daftar instansi (hierarkis) yang boleh dipilih sebagai induk.
   * Saat edit, instansi ini sendiri & seluruh keturunannya dikecualikan
   * agar tidak terjadi siklus.
   */
  private function getHierarchicalDepartments(): array
  {
    $user = $this->currentUser();

    $excludeIds = collect();
    if ($this->isEdit) {
      $excludeIds = $this->department->getAllRelatedIds();
    }

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      $roots = $user->department_id
        ? Department::where('id', $user->department_id)->get()
        : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list, $excludeIds);
    }

    return $list;
  }

  private function flattenDepartment($dept, int $level, array &$list, $excludeIds): void
  {
    if ($excludeIds->contains($dept->id)) {
      return;
    }

    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[] = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list, $excludeIds);
    }
  }

  public function render()
  {
    // Pilihan induk (hierarkis) — jumlah departemen terbatas, aman dimuat penuh.
    $parents = $this->getHierarchicalDepartments();

    // Pimpinan diambil dari pegawai pada instansi INDUK (parent) yang dipilih.
    // Untuk OPD root (tanpa induk) saat edit, jatuh ke pegawai instansi itu sendiri.
    $scopeDeptId = $this->parent_id
      ? (int) $this->parent_id
      : ($this->isEdit ? $this->department->id : null);

    // Pegawai yang sudah menjadi kepala di instansi LAIN dikecualikan.
    $assignedElsewhere = Department::whereNotNull('head_id')
      ->when($this->isEdit, fn ($q) => $q->where('id', '!=', $this->department->id))
      ->pluck('head_id')
      ->all();

    $headQuery = User::where('is_active', true)
      ->whereNotIn('id', $assignedElsewhere)
      ->when(
        $scopeDeptId !== null,
        fn ($q) => $q->where('department_id', $scopeDeptId),
        fn ($q) => $q->whereRaw('1 = 0') // tanpa scope (OPD induk baru) → belum ada kandidat
      );

    if (trim($this->searchHead) !== '') {
      $term = trim($this->searchHead);
      $headQuery->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('nip', 'like', "%{$term}%");
      });
    }

    $limit = 25;
    $heads = $headQuery->orderBy('name')->limit($limit + 1)->get();
    $headsHasMore = $heads->count() > $limit;
    $heads = $heads->take($limit);

    $selectedHead = $this->head_id ? User::find($this->head_id) : null;

    /** @var \Illuminate\View\View $view */
    $view = view('livewire.departments.form', [
      'parents'      => $parents,
      'types'        => DepartmentType::cases(),
      'heads'        => $heads,
      'headsHasMore' => $headsHasMore,
      'selectedHead' => $selectedHead,
      'isRoot'       => $this->isRoot(),
      'parentLocked' => $this->isParentLocked(),
    ]);

    // title() = macro Livewire pada Illuminate\View\View (dikenali via _ide_helper.php).
    return $view->title($this->isEdit ? 'Edit Instansi' : 'Tambah Instansi');
  }
}
