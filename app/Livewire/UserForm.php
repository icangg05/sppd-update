<?php

namespace App\Livewire;

use App\Enums\DprdJabatan;
use App\Enums\DprdPartai;
use App\Enums\EmployeeType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Livewire\Concerns\InteractsWithPhoneVerification;
use App\Enums\PositionScope;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
  use InteractsWithToast;
  use InteractsWithPhoneVerification;

  public ?User $user = null;
  public bool $isEdit = false;

  // Konteks asal: 'dprd' bila formulir dibuka dari halaman Anggota DPRD.
  // Dipakai agar menu sidebar & tombol kembali tetap mengarah ke daftar yang benar.
  public string $listType = '';

  // Form fields
  public $name = '';
  public $username = '';
  public $email = '';
  public $password = '';
  public $nip = '';
  public $nik = '';
  public $phone = '';
  public $employee_type = '';
  public $department_id = '';
  public $rank_id = '';
  public $position_id = '';
  public $role = '';

  // Pencarian server-side untuk jabatan (agar tidak memuat seluruh data jabatan sekaligus).
  public string $searchPosition = '';

  // Data khusus Anggota DPRD
  public $dprd_jabatan = '';
  public $partai = '';

  // State verifikasi WhatsApp disediakan oleh InteractsWithPhoneVerification.

  public function mount(?User $user = null)
  {
    if ($user && $user->exists) {
      $this->user = $user;
      $this->isEdit = true;

      $this->name = $user->name;
      $this->username = $user->username;
      $this->email = $user->email;
      $this->nip = $user->nip;
      $this->nik = $user->nik;
      $this->phone = $user->phone;
      $this->employee_type = $user->employee_type->value ?? '';
      $this->department_id = $user->department_id;
      $this->rank_id = $user->rank_id;
      $this->position_id = $user->position_id;
      $this->role = $user->roles->first()?->name ?? '';
      $this->dprd_jabatan = $user->dprd_jabatan ?? '';
      $this->partai = $user->partai ?? '';
      $this->phoneVerified = (bool) $user->phone_verified;

      // Edit pegawai DPRD tetap dianggap konteks DPRD walau parameter URL hilang.
      if ($user->isDprdMember()) {
        $this->listType = 'dprd';
      }
    } else {
      $this->employee_type = EmployeeType::cases()[0]->value ?? '';
    }

    // Konteks dari halaman asal (mis. dibuka dari menu Anggota DPRD).
    if (request('type') === 'dprd') {
      $this->listType = 'dprd';

      // Saat menambah dari halaman DPRD, default-kan tipe pegawai ke DPRD
      // agar field khusus DPRD langsung tampil.
      if (! $this->isEdit) {
        $this->employee_type = EmployeeType::DPRD->value;
      }
    }

    // Memulihkan state verifikasi jika halaman di-refresh saat menunggu balasan.
    $this->restorePhoneVerificationState();
  }

  protected function verificationUserId(): ?int
  {
    return $this->isEdit ? $this->user->id : null;
  }

  protected function onPhoneVerified(string $phone): void
  {
    if ($this->isEdit && $this->user) {
      $this->user->update([
        'phone' => $phone,
        'phone_verified' => true,
      ]);
    }
  }

  protected function onPhoneReset(): void
  {
    if ($this->isEdit && $this->user) {
      $this->user->update([
        'phone' => null,
        'phone_verified' => false,
      ]);
    }
  }

  protected function rules()
  {
    $userId = $this->isEdit ? $this->user->id : 'NULL';
    $rules = [
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username,' . $userId,
      'email' => 'nullable|email|unique:users,email,' . $userId,
      'password' => 'nullable|string|min:6',
      'nip' => 'nullable|string|max:20|unique:users,nip,' . $userId,
      'nik' => 'nullable|string|max:20|unique:users,nik,' . $userId,
      'phone' => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'required|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      'role' => ['required', 'string', $this->uniqueRoleRule()],
    ];

    // Validasi: jabatan struktural yang dibatasi (Walikota, Sekda, Kepala OPD, dst.)
    // hanya boleh dipangku satu orang — sesuai scope-nya (sistem / per OPD).
    $rules['position_id'] = ['nullable', 'exists:positions,id', $this->uniquePositionRule()];

    if ($this->isDprdMember()) {
      $jabatanValues = implode(',', array_column(DprdJabatan::cases(), 'value'));
      $partaiValues = implode(',', array_column(DprdPartai::cases(), 'value'));
      $rules['dprd_jabatan'] = ['required', 'in:' . $jabatanValues, $this->uniqueDprdJabatanRule()];
      $rules['partai'] = 'nullable|in:' . $partaiValues;
    }

    return $rules;
  }

  /**
   * Aturan: cegah dua pegawai aktif memangku jabatan struktural yang sama
   * pada lingkup yang dibatasi (satu sistem atau satu OPD).
   */
  protected function uniquePositionRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      if (empty($value)) {
        return;
      }

      $position = Position::find($value);
      if (! $position) {
        return;
      }

      $holder = $position->conflictingHolder(
        $this->department_id ? (int) $this->department_id : null,
        $this->isEdit ? $this->user->id : null,
      );

      if ($holder) {
        $cakupan = $position->uniqueness_scope === PositionScope::DEPARTMENT
          ? 'pada unit kerja ini'
          : 'di lingkup pemerintah daerah';
        $fail("Jabatan {$position->name} sudah dijabat oleh {$holder->name} {$cakupan}. Nonaktifkan pejabat lama terlebih dahulu.");
      }
    };
  }

  /**
   * Aturan: role/kewenangan tunggal (Walikota, Sekda, Kepala OPD, dst.)
   * hanya boleh dipegang satu pegawai aktif sesuai lingkupnya.
   */
  protected function uniqueRoleRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      if (empty($value)) {
        return;
      }

      $perOpd = in_array($value, config('role_uniqueness.department', []), true);
      $perSystem = in_array($value, config('role_uniqueness.system', []), true);

      if (! $perOpd && ! $perSystem) {
        return; // role boleh dipegang banyak pegawai
      }

      $query = User::role($value)->where('is_active', true);

      if ($perOpd) {
        $query->where('department_id', $this->department_id ? (int) $this->department_id : null);
      }

      if ($this->isEdit) {
        $query->where('id', '!=', $this->user->id);
      }

      $holder = $query->first();
      if ($holder) {
        $label = Role::where('name', $value)->value('label') ?? $value;
        $cakupan = $perOpd ? 'pada unit kerja ini' : 'di lingkup pemerintah daerah';
        $fail("Role {$label} sudah dipegang oleh {$holder->name} {$cakupan}. Nonaktifkan pejabat lama terlebih dahulu.");
      }
    };
  }

  /**
   * Aturan: jabatan pimpinan DPRD (Ketua & Wakil Ketua) hanya boleh satu orang.
   */
  protected function uniqueDprdJabatanRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      $singletons = [
        DprdJabatan::KETUA->value,
        DprdJabatan::WAKIL_1->value,
        DprdJabatan::WAKIL_2->value,
        DprdJabatan::WAKIL_3->value,
      ];

      if (! in_array($value, $singletons, true)) {
        return;
      }

      $query = User::where('dprd_jabatan', $value)->where('is_active', true);
      if ($this->isEdit) {
        $query->where('id', '!=', $this->user->id);
      }

      $holder = $query->first();
      if ($holder) {
        $fail("Jabatan {$value} sudah dijabat oleh {$holder->name}. Nonaktifkan pejabat lama terlebih dahulu.");
      }
    };
  }

  protected function validationAttributes()
  {
    return [
      'department_id' => 'instansi / unit kerja',
      'position_id' => 'jabatan',
      'dprd_jabatan' => 'jabatan DPRD',
      'partai' => 'partai / fraksi',
    ];
  }

  /**
   * Menentukan apakah pegawai yang sedang diisi merupakan Anggota DPRD,
   * berdasarkan tipe pegawai atau role yang dipilih pada formulir.
   */
  public function isDprdMember(): bool
  {
    return $this->employee_type === EmployeeType::DPRD->value
      || in_array($this->role, ['anggota_dprd', 'pimpinan_dprd'], true);
  }

  /**
   * Formulir sedang dalam konteks Anggota DPRD (dibuka dari menu Anggota DPRD).
   * Pada konteks ini tipe pegawai dikunci ke DPRD dan field kepegawaian umum
   * (NIP, pangkat, jabatan struktural) disembunyikan.
   */
  public function isDprdContext(): bool
  {
    return $this->listType === 'dprd';
  }

  /**
   * Sinkronkan role <-> jabatan DPRD:
   * Pimpinan DPRD ⟺ Ketua DPRD, selain itu Anggota DPRD.
   */
  public function updatedRole($value)
  {
    if ($value === 'pimpinan_dprd') {
      $this->dprd_jabatan = DprdJabatan::KETUA->value;
    } elseif ($value === 'anggota_dprd' && $this->dprd_jabatan === DprdJabatan::KETUA->value) {
      $this->dprd_jabatan = '';
    }
  }

  public function updatedDprdJabatan($value)
  {
    if ($value === DprdJabatan::KETUA->value) {
      $this->role = 'pimpinan_dprd';
    } elseif (! empty($value)) {
      $this->role = 'anggota_dprd';
    }
  }

  public function selectPosition($id): void
  {
    $this->position_id = (int) $id;
  }

  public function clearPosition(): void
  {
    $this->position_id = '';
  }

  // Metode verifikasi WhatsApp (openVerifyModal, checkVerification, dll.)
  // disediakan oleh InteractsWithPhoneVerification.

  /**
   * Membuat username ringkas & unik dari Nama Lengkap memakai UsernameGenerator
   * (format "depan.belakang", gelar dibuang, sufiks angka bila bentrok).
   */
  public function generateUsername(): void
  {
    $username = app(UsernameGenerator::class)
      ->generate($this->name, $this->isEdit ? $this->user->id : null);

    if ($username === null) {
      $this->toastError('Isi Nama Lengkap terlebih dahulu untuk membuat username.');
      return;
    }

    $this->username = $username;
    $this->resetErrorBag('username');
    $this->toastSuccess('Username dibuat: ' . $this->username);
  }

  public function save()
  {
    try {
      $this->validate();
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
    }

    if (!empty($this->phone) && !$this->phoneVerified) {
      $this->addError('phone', 'Nomor telepon harus diverifikasi terlebih dahulu.');
      $this->toastError('Nomor telepon harus diverifikasi terlebih dahulu.');
      return;
    }

    $currentUser = auth()->user();
    if (!$currentUser->hasRole('super_admin')) {
      $dept = $currentUser->department;
      if ($dept && !empty($this->department_id)) {
        $allowedIds = $dept->getScopedRelatedIds();
        if (!$allowedIds->contains($this->department_id)) {
          $this->department_id = $currentUser->department_id;
        }
      } else {
        $this->department_id = $currentUser->department_id;
      }
    }

    $data = [
      'name' => $this->name,
      'username' => $this->username,
      'email' => $this->email ?: null,
      'nip' => $this->nip ?: null,
      'nik' => $this->nik ?: null,
      'employee_type' => $this->employee_type,
      'department_id' => $this->department_id ?: null,
      'rank_id' => $this->rank_id ?: null,
      'position_id' => $this->position_id ?: null,
    ];

    // Simpan data DPRD hanya untuk Anggota DPRD; selain itu kosongkan agar tidak ada data basi.
    if ($this->isDprdMember()) {
      $data['dprd_jabatan'] = $this->dprd_jabatan ?: null;
      $data['partai'] = $this->partai ?: null;
    } else {
      $data['dprd_jabatan'] = null;
      $data['partai'] = null;
    }

    if ($this->phoneVerified) {
      $data['phone'] = $this->phone;
    }

    if (!empty($this->password)) {
      $data['password'] = Hash::make($this->password);
    } elseif (!$this->isEdit) {
      // Pegawai baru tanpa password memakai password default dari config.
      $data['password'] = Hash::make(config('users.default_password'));
    }

    $typeParam = array_filter(['type' => $this->listType]);

    if ($this->isEdit) {
      $this->user->update($data);
      $this->user->syncRoles([$this->role]);
      $this->toastSuccess("Pegawai {$this->user->name} berhasil diperbarui.");
      return;
    } else {
      $data['is_active'] = true;
      $data['phone_verified'] = $this->phoneVerified;
      $newUser = User::create($data);
      $newUser->assignRole($this->role);
      return redirect()->route('master.users.edit', [...$typeParam, 'user' => $newUser])->with('success', "Pegawai {$newUser->name} berhasil ditambahkan. Silakan lengkapi data jika diperlukan.");
    }
  }

  private function getHierarchicalDepartments()
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      $dept  = $user->department;
      $roots = $dept ? Department::where('id', $dept->id)->get() : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list);
    }

    return $list;
  }

  private function flattenDepartment($dept, $level, &$list)
  {
    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[]             = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function render()
  {
    // Pencarian jabatan server-side: hanya memuat sebagian data (bukan seluruh jabatan).
    $positionQuery = Position::query();
    if (trim($this->searchPosition) !== '') {
      $positionQuery->where('name', 'like', '%' . trim($this->searchPosition) . '%');
    }

    $limit = 25;
    $positions = $positionQuery->orderBy('level')->limit($limit + 1)->get();
    $positionsHasMore = $positions->count() > $limit;
    $positions = $positions->take($limit);

    // Jabatan terpilih tetap tampil di trigger walau di luar hasil pencarian.
    $selectedPosition = $this->position_id ? Position::find($this->position_id) : null;

    return view('livewire.user-form', [
      'departments' => $this->getHierarchicalDepartments(),
      'ranks' => Rank::orderBy('group')->get(),
      'positions' => $positions,
      'positionsHasMore' => $positionsHasMore,
      'selectedPosition' => $selectedPosition,
      'roles' => Role::all(),
      'employeeTypes' => EmployeeType::cases(),
      'dprdJabatans' => DprdJabatan::cases(),
      'dprdPartais' => DprdPartai::cases(),
    ]);
  }
}
