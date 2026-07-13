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
  public $letterhead = null;
  public $letterhead_second = null;
  public bool $can_view_children_data = true;

  // Penandatangan "Setuju Bayar" pada dokumen cetak (kuitansi, pengeluaran
  // riil, rincian biaya). Kosong = fallback ke kepala_opd + label bawaan.
  public $setuju_bayar_user_id = '';
  public string $setuju_bayar_label = '';

  // Pencarian penandatangan Setuju Bayar (server-side).
  public string $searchSetujuBayar = '';

  public bool $isSuperAdmin = false;

  // Konfirmasi hapus kop surat (modal). Target: 'primary' | 'secondary'.
  public bool $showDeleteKopModal = false;
  public string $deleteKopTarget = '';

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
      $this->setuju_bayar_user_id = $department->setuju_bayar_user_id ?? '';
      $this->setuju_bayar_label   = $department->setuju_bayar_label ?? '';
      $this->can_view_children_data = (bool) $department->can_view_children_data;

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
    $allowedIds = $dept ? $dept->getScopedRelatedIds() : collect([$this->currentUser()->department_id]);

    abort_unless($allowedIds->contains($this->department->id), 403, 'Anda tidak memiliki akses ke unit ini.');
  }

  /**
   * Instansi milik admin sendiri (puncak lingkupnya) tidak boleh dipindah/di-rename
   * oleh admin OPD — induknya berada di luar kewenangannya. Sub-unit di bawah instansi
   * itu (yang ia buat) tetap boleh dipindah di dalam lingkupnya. Super admin bebas.
   */
  public function isParentLocked(): bool
  {
    return $this->isEdit
      && ! $this->isSuperAdmin
      && (int) $this->department->id === (int) $this->currentUser()->department_id;
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

  public function selectSetujuBayar($id): void
  {
    $this->setuju_bayar_user_id = (int) $id;
  }

  public function clearSetujuBayar(): void
  {
    $this->setuju_bayar_user_id = '';
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
      'setuju_bayar_user_id' => ['nullable', 'exists:users,id', $this->setujuBayarInScopeRule()],
      'setuju_bayar_label'   => ['nullable', 'string', 'max:150'],
      // OPD induk (root) tidak punya parent_id — jangan wajibkan, termasuk untuk admin OPD
      // yang mengedit instansinya sendiri. Sub-unit tetap wajib memilih induk.
      'parent_id' => [$this->isSuperAdmin || $this->isRoot() ? 'nullable' : 'required', 'exists:departments,id'],
      'can_view_children_data' => ['boolean'],
      // Kop utama boleh diunggah semua unit (sub-unit yang kosong akan mewarisi induk).
      'letterhead' => ['nullable', 'image', 'max:2048'],
    ];

    // Kode hanya relevan di tingkat induk (OPD).
    if ($this->isRoot()) {
      $rules['code'] = ['nullable', 'string', 'max:30', $codeUnique];
    }

    // Kop kedua (SPT) khusus DPRD.
    if ($this->type === DepartmentType::DPRD->value) {
      $rules['letterhead_second'] = ['nullable', 'image', 'max:2048'];
    }

    return $rules;
  }

  protected function validationAttributes(): array
  {
    return [
      'name'      => 'nama unit kerja',
      'parent_id' => 'instansi induk',
      'setuju_bayar_user_id' => 'penandatangan setuju bayar',
      'setuju_bayar_label'   => 'label penandatangan setuju bayar',
      'type'      => 'tipe entitas',
    ];
  }

  /**
   * Penandatangan Setuju Bayar harus pegawai dalam zona data unit ini
   * (sesuai daftar kandidat yang ditampilkan pada dropdown).
   */
  protected function setujuBayarInScopeRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      if (! $value || ! $this->isEdit) {
        return;
      }

      $allowedDeptIds = $this->department->getScopeRootDepartment()->getScopedRelatedIds();
      $signer = User::find($value);

      if (! $signer || ! $allowedDeptIds->contains($signer->department_id)) {
        $fail('Penandatangan Setuju Bayar harus pegawai di lingkup instansi ini.');
      }
    };
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

    // Nama OPD induk terkunci untuk admin OPD — jaga agar tidak berubah walau field di-bypass.
    if ($this->isParentLocked()) {
      $this->name = $this->department->name;
    }

    // Batasi induk pada lingkup instansi sendiri untuk admin OPD (saat create).
    if (! $this->isSuperAdmin && ! $this->isParentLocked()) {
      $dept = $this->currentUser()->department;
      $allowedIds = $dept ? $dept->getScopedRelatedIds() : collect([$this->currentUser()->department_id]);
      if (! $allowedIds->contains((int) $this->parent_id)) {
        $this->addError('parent_id', 'Anda hanya dapat menempatkan unit di bawah instansi Anda sendiri.');
        return;
      }
    }

    // Hanya super admin yang boleh mengubah kebijakan zona data —
    // paksa nilai lama (edit) / bawaan aktif (create) untuk admin OPD.
    if (! $this->isSuperAdmin) {
      $this->can_view_children_data = $this->isEdit
        ? (bool) $this->department->can_view_children_data
        : true;
    }

    $data = [
      'name'      => trim($this->name),
      'type'      => $this->type,
      'parent_id' => $this->parent_id ?: null,
      'setuju_bayar_user_id' => $this->setuju_bayar_user_id ?: null,
      'setuju_bayar_label'   => trim($this->setuju_bayar_label) ?: null,
      'can_view_children_data' => $this->can_view_children_data,
    ];

    // Hitung level & warisi atribut dari induk.
    if ($data['parent_id']) {
      $parent = Department::find($data['parent_id']);
      $data['level'] = ($parent->level ?? 1) + 1;
      // Sub-unit BARU ikut tipe induk; saat edit, tipe unit yang sudah ada
      // dipertahankan agar tidak tertimpa (mis. kelurahan tidak berubah jadi kecamatan).
      // Super admin boleh memilih tipe sendiri (mis. kelurahan di bawah kecamatan),
      // jadi pilihannya tidak ditimpa tipe induk.
      if (! $this->isEdit && ! $this->isSuperAdmin) {
        $data['type'] = $parent->type->value;
      }
    } else {
      $data['level'] = 1;
      $data['code']  = $this->code ?: null;
    }

    // Persist dulu agar berkas kop bisa dinamai berdasarkan id (cegah tabrakan
    // nama antar unit yang namanya sama di induk berbeda).
    $dept = $this->isEdit
      ? tap($this->department)->update($data)
      : Department::create($data);

    $this->storeLetterheads($dept);

    if ($this->isEdit) {
      // Bersihkan berkas upload sementara agar preview memakai kop yang baru
      // tersimpan dan indikator "Mengunggah" tidak tertinggal.
      $this->letterhead = null;
      $this->letterhead_second = null;

      $this->toastSuccess("Instansi/OPD {$dept->name} berhasil diperbarui.");

      return;
    }

    return redirect()->route('master.departments.index')
      ->with('success', "Instansi {$dept->name} berhasil ditambahkan.");
  }

  /**
   * Simpan berkas kop surat untuk unit apa pun. Kop utama berlaku untuk semua
   * unit (yang kosong akan mewarisi induk saat render); kop kedua khusus DPRD.
   */
  private function storeLetterheads(Department $dept): void
  {
    $files = [];

    if ($this->letterhead) {
      $this->deleteOldFile($dept->letterhead);
      $ext = $this->letterhead->getClientOriginalExtension();
      $files['letterhead'] = $this->letterhead->storeAs('kop_surat', "{$dept->id}_primary.{$ext}", 'public');
    }

    if ($this->letterhead_second && $dept->type === DepartmentType::DPRD) {
      $this->deleteOldFile($dept->letterhead_second);
      $ext = $this->letterhead_second->getClientOriginalExtension();
      $files['letterhead_second'] = $this->letterhead_second->storeAs('kop_surat', "{$dept->id}_secondary.{$ext}", 'public');
    }

    if ($files) {
      $dept->update($files);
    }
  }

  private function deleteOldFile(?string $path): void
  {
    if ($path && Str::contains($path, '/')) {
      Storage::disk('public')->delete($path);
    }
  }

  /**
   * Buka modal konfirmasi hapus kop surat untuk target tertentu.
   */
  public function confirmDeleteKop(string $target): void
  {
    $this->deleteKopTarget = in_array($target, ['primary', 'secondary'], true) ? $target : 'primary';
    $this->showDeleteKopModal = true;
  }

  public function closeDeleteKopModal(): void
  {
    $this->showDeleteKopModal = false;
    $this->deleteKopTarget = '';
  }

  /**
   * Hapus berkas kop surat milik instansi ini (bukan yang diwarisi dari induk).
   * Setelah dihapus, unit kembali mewarisi kop induk (bila ada).
   */
  public function deleteKop(): void
  {
    $this->showDeleteKopModal = false;

    if (! $this->isEdit) {
      return;
    }

    $column = $this->deleteKopTarget === 'secondary' ? 'letterhead_second' : 'letterhead';

    $this->deleteOldFile($this->department->{$column});
    $this->department->update([$column => null]);

    $label = $this->deleteKopTarget === 'secondary' ? 'Kop surat kedua (SPT)' : 'Kop surat utama';
    $this->deleteKopTarget = '';

    $this->toastSuccess("{$label} berhasil dihapus.");
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

    // Admin OPD: opsi induk hanya dalam zona datanya (unit di luar zona disembunyikan).
    $allowedIds = null;

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      $roots = $user->department_id
        ? Department::where('id', $user->department_id)->get()
        : collect();

      $allowedIds = $user->department?->getScopedRelatedIds();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list, $excludeIds, $allowedIds);
    }

    return $list;
  }

  private function flattenDepartment($dept, int $level, array &$list, $excludeIds, $allowedIds = null): void
  {
    if ($excludeIds->contains($dept->id)) {
      return;
    }

    if ($allowedIds !== null && ! $allowedIds->contains($dept->id)) {
      return;
    }

    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[] = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list, $excludeIds, $allowedIds);
    }
  }

  public function render()
  {
    // Pilihan induk (hierarkis) — jumlah departemen terbatas, aman dimuat penuh.
    $parents = $this->getHierarchicalDepartments();

    $limit = 25;

    // Setuju Bayar hanya diatur di root zona data (mis. Dinas, atau kelurahan/
    // puskesmas yang mandiri) — sub-unit di bawahnya otomatis mewarisi, jadi
    // form-nya tidak ditampilkan di sana (lihat Department::resolveSetujuBayar).
    $isScopeRoot = $this->isEdit
      && $this->department->getScopeRootDepartment()->id === $this->department->id;

    // Kandidat penandatangan Setuju Bayar: pegawai aktif dalam zona data unit
    // ini (boleh sama dengan penandatangan unit lain). Hanya relevan di root zona.
    $sbCandidates = collect();
    $sbHasMore = false;
    if ($isScopeRoot) {
      $sbDeptIds = $this->department->getScopeRootDepartment()->getScopedRelatedIds();

      $sbQuery = User::where('is_active', true)
        ->whereIn('department_id', $sbDeptIds)
        // Akun administratif bukan kandidat penandatangan.
        ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin_opd', 'super_admin']));

      if (trim($this->searchSetujuBayar) !== '') {
        $term = trim($this->searchSetujuBayar);
        $sbQuery->where(function ($q) use ($term) {
          $q->where('name', 'like', "%{$term}%")
            ->orWhere('nip', 'like', "%{$term}%");
        });
      }

      $sbCandidates = $sbQuery->orderBy('name')->limit($limit + 1)->get();
      $sbHasMore = $sbCandidates->count() > $limit;
      $sbCandidates = $sbCandidates->take($limit);
    }

    $selectedSetujuBayar = $this->setuju_bayar_user_id ? User::find($this->setuju_bayar_user_id) : null;

    // Kop yang sedang diwarisi dari induk (untuk preview di unit yang belum
    // punya kop sendiri). Hanya relevan saat edit.
    $inheritedLetterhead = ($this->isEdit && empty($this->department->letterhead))
      ? $this->department->getInheritedLetterhead()
      : null;

    /** @var \Illuminate\View\View $view */
    $view = view('livewire.departments.form', [
      'parents'      => $parents,
      'types'        => DepartmentType::cases(),
      'sbCandidates' => $sbCandidates,
      'sbHasMore'    => $sbHasMore,
      'selectedSetujuBayar' => $selectedSetujuBayar,
      'isScopeRoot'  => $isScopeRoot,
      'isRoot'       => $this->isRoot(),
      'parentLocked' => $this->isParentLocked(),
      'inheritedLetterhead' => $inheritedLetterhead,
    ]);

    // title() = macro Livewire pada Illuminate\View\View (dikenali via _ide_helper.php).
    return $view->title($this->isEdit ? 'Edit Instansi' : 'Tambah Instansi');
  }
}
