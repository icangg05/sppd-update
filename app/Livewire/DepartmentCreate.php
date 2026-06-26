<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DepartmentCreate extends Component
{
  use InteractsWithToast;
  use WithFileUploads;

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

  public function mount(): void
  {
    $this->isSuperAdmin = auth()->user()->hasRole('super_admin');

    if ($this->isSuperAdmin) {
      // Default: buat OPD induk baru (tanpa induk). Tipe default OPD.
      $this->type = DepartmentType::OPD->value;
    } else {
      // Admin OPD hanya boleh membuat sub-unit di bawah instansinya sendiri.
      $this->parent_id = auth()->user()->department_id;
      $this->syncTypeFromParent();
    }
  }

  /**
   * Saat induk berubah, tipe & atribut tingkat-induk (kode/kop) menyesuaikan.
   */
  public function updatedParentId(): void
  {
    if ($this->isRoot()) {
      // Kembali ke pembuatan OPD induk: tipe bebas dipilih, default OPD bila kosong.
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
   * True bila sedang membuat OPD induk (tanpa instansi pengampu).
   * Hanya super admin yang boleh; admin OPD selalu punya induk.
   */
  public function isRoot(): bool
  {
    return $this->isSuperAdmin && empty($this->parent_id);
  }

  public function selectHead($id): void
  {
    $this->head_id = (int) $id;
  }

  public function clearHead(): void
  {
    $this->head_id = '';
  }

  protected function rules(): array
  {
    $rules = [
      'name'      => ['required', 'string', 'max:255', $this->uniqueNameAtSameLevelRule()],
      'type'      => ['required', 'in:' . implode(',', array_column(DepartmentType::cases(), 'value'))],
      'head_id'   => ['nullable', 'exists:users,id'],
      'parent_id' => [$this->isSuperAdmin ? 'nullable' : 'required', 'exists:departments,id'],
    ];

    // Kode & kop surat hanya relevan di tingkat induk (OPD baru).
    if ($this->isRoot()) {
      $rules['code'] = ['nullable', 'string', 'max:30', 'unique:departments,code'];
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

    // Batasi induk pada lingkup instansi sendiri untuk admin OPD.
    if (! $this->isSuperAdmin) {
      $dept = auth()->user()->department;
      $allowedIds = $dept ? $dept->getAllRelatedIds() : collect([auth()->user()->department_id]);
      if (! $allowedIds->contains((int) $this->parent_id)) {
        $this->addError('parent_id', 'Anda hanya dapat membuat unit di bawah instansi Anda sendiri.');
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
        $ext = $this->letterhead->getClientOriginalExtension();
        $data['letterhead'] = $this->letterhead->storeAs('kop_surat', "{$cleanName}_primary.{$ext}", 'public');
      }
      if ($this->letterhead_second) {
        $ext = $this->letterhead_second->getClientOriginalExtension();
        $data['letterhead_second'] = $this->letterhead_second->storeAs('kop_surat', "{$cleanName}_secondary.{$ext}", 'public');
      }
    }

    Department::create($data);

    return redirect()->route('master.departments.index')
      ->with('success', "Instansi {$data['name']} berhasil ditambahkan.");
  }

  private function cleanName(string $name): string
  {
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '_', $name));
    $clean = preg_replace('/_+/', '_', $clean);

    return trim($clean, '_');
  }

  /**
   * Daftar instansi (hierarkis) yang boleh dipilih sebagai induk.
   */
  private function getHierarchicalDepartments(): array
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      $roots = $user->department_id
        ? Department::where('id', $user->department_id)->get()
        : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list);
    }

    return $list;
  }

  private function flattenDepartment($dept, int $level, array &$list): void
  {
    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[] = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function render()
  {
    // Pilihan induk (hierarkis) — jumlah departemen terbatas, aman dimuat penuh.
    $parents = $this->getHierarchicalDepartments();

    // Pimpinan: pencarian server-side, hanya user aktif yang belum jadi kepala di manapun.
    $assignedHeadIds = Department::whereNotNull('head_id')->pluck('head_id')->all();

    $headQuery = User::where('is_active', true)
      ->whereNotIn('id', $assignedHeadIds);

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

    return view('livewire.departments.create', [
      'parents'      => $parents,
      'types'        => DepartmentType::cases(),
      'heads'        => $heads,
      'headsHasMore' => $headsHasMore,
      'selectedHead' => $selectedHead,
      'isRoot'       => $this->isRoot(),
    ])->title('Tambah Instansi');
  }
}
