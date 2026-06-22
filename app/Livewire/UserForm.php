<?php

namespace App\Livewire;

use App\Enums\DprdJabatan;
use App\Enums\DprdPartai;
use App\Enums\EmployeeType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
  use InteractsWithToast;

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

  // Data khusus Anggota DPRD
  public $dprd_jabatan = '';
  public $partai = '';

  // WhatsApp Verification state
  public bool $phoneVerified = false;
  public bool $showVerifyModal = false;
  public bool $isPolling = false;
  public string $verificationNumber = '';
  public string $verificationTemplate = '';
  public string $deeplinkUrl = '';
  public string $verificationToken = '';
  public bool $isVerified = false;
  public bool $isFailed = false;
  public bool $isTimedOut = false;
  public string $failedMessage = '';
  public int $pollingCount = 0;

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

    // Memulihkan state verifikasi jika halaman di-refresh
    $token = session()->get('wa_verification_token');
    if ($token && !$this->phoneVerified) {
      $cached = Cache::get("wa_verification:{$token}");
      if ($cached) {
        $this->verificationToken = $token;
        // Hanya memulihkan phone jika saat ini kosong agar input user tidak tertimpa tanpa alasan
        if (empty($this->phone)) {
          $this->phone = $cached['phone'];
        }
        $this->isPolling = true;
      } else {
        session()->forget('wa_verification_token');
      }
    }
  }

  protected function rules()
  {
    $userId = $this->isEdit ? $this->user->id : 'NULL';
    $rules = [
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username,' . $userId,
      'email' => 'nullable|email|unique:users,email,' . $userId,
      'password' => $this->isEdit ? 'nullable|string|min:6' : 'required|string|min:6',
      'nip' => 'nullable|string|max:20',
      'nik' => 'nullable|string|max:20|unique:users,nik,' . $userId,
      'phone' => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'required|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      'role' => 'required|string',
    ];

    if ($this->isDprdMember()) {
      $jabatanValues = implode(',', array_column(DprdJabatan::cases(), 'value'));
      $partaiValues = implode(',', array_column(DprdPartai::cases(), 'value'));
      $rules['dprd_jabatan'] = 'required|in:' . $jabatanValues;
      $rules['partai'] = 'nullable|in:' . $partaiValues;
    }

    return $rules;
  }

  protected function validationAttributes()
  {
    return [
      'department_id' => 'instansi / unit kerja',
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

  public function openVerifyModal()
  {
    $this->resetErrorBag('phone');

    if (empty(trim($this->phone))) {
      $this->addError('phone', 'Masukkan nomor telepon terlebih dahulu.');
      return;
    }

    $this->verificationNumber = config('kirimchat.verification_number', '6281376111919');
    $this->verificationToken = 'V-' . rand(10000, 99999);
    $normalizedPhone = $this->normalizePhone($this->phone);

    $userId = $this->isEdit ? $this->user->id : null;

    if ($userId) {
      $prevToken = Cache::get("wa_verification_user:{$userId}");
      if ($prevToken) {
        $prevCached = Cache::get("wa_verification:{$prevToken}");
        if ($prevCached) {
          $prevNormalized = $this->normalizePhone($prevCached['phone']);
          Cache::forget("wa_verification_phone:{$prevNormalized}");
        }
        Cache::forget("wa_verification:{$prevToken}");
        Cache::forget("wa_verified_status:{$prevToken}");
        Cache::forget("wa_verification_failed:{$prevToken}");
      }
      Cache::put("wa_verification_user:{$userId}", $this->verificationToken, now()->addMinutes(15));
    }

    Cache::put("wa_verification:{$this->verificationToken}", [
      'phone'   => $this->phone,
      'user_id' => $userId,
      'name'    => $this->name ?: 'Pegawai',
      'email'   => $this->email ?: '-',
    ], now()->addMinutes(15));

    Cache::put("wa_verification_phone:{$normalizedPhone}", $this->verificationToken, now()->addMinutes(15));

    $this->verificationTemplate = "Verifikasi WhatsApp SPPD Kendari\n" .
      "📱 *Nomor:* {$normalizedPhone}\n\n" .
      "Kirim pesan ini untuk memverifikasi nomor WhatsApp Anda.\n\n" .
      "_Sistem akan mencocokkan nomor pengirim secara otomatis. Mohon periksa status verifikasi pada browser Anda secara berkala jika belum menerima balasan._";

    $this->deeplinkUrl = 'https://wa.me/' . $this->verificationNumber . '?text=' . urlencode($this->verificationTemplate);

    session()->put('wa_verification_token', $this->verificationToken);

    $this->isVerified = false;
    $this->isFailed = false;
    $this->isTimedOut = false;
    $this->failedMessage = '';
    $this->pollingCount = 0;

    $this->showVerifyModal = true;
    $this->isPolling = true;
  }

  public function checkVerification()
  {
    if (!$this->isPolling) return;

    $this->pollingCount++;
    if ($this->pollingCount > 300) { // 5 mins with 1s interval
      $this->isTimedOut = true;
      $this->isPolling = false;
      return;
    }

    $verified = Cache::get("wa_verified_status:{$this->verificationToken}");
    if ($verified && !empty($verified['verified'])) {
      $this->isVerified = true;
      $this->phone = $verified['phone'];
      $this->phoneVerified = true;
      $this->isPolling = false;
      session()->forget('wa_verification_token');

      // Auto update user verification status if edit mode
      if ($this->isEdit && $this->user) {
        $this->user->update([
          'phone' => $this->phone,
          'phone_verified' => true,
        ]);
      }
      return;
    }

    $failed = Cache::get("wa_verification_failed:{$this->verificationToken}");
    if ($failed) {
      $this->isFailed = true;
      $this->failedMessage = $failed['message'] ?? 'Verifikasi gagal.';
      $this->isPolling = false;
      session()->forget('wa_verification_token');
      return;
    }
  }

  public function closeVerifyModal()
  {
    $this->showVerifyModal = false;
  }

  public function retryVerification()
  {
    $this->closeVerifyModal();
    $this->openVerifyModal();
  }

  public function resetPhoneVerification()
  {
    $this->phoneVerified = false;
    $this->phone = '';
    $this->isVerified = false;
    $this->isPolling = false;
    session()->forget('wa_verification_token');

    if ($this->isEdit && $this->user) {
      $this->user->update([
        'phone' => null,
        'phone_verified' => false,
      ]);
    }
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
        $allowedIds = $dept->getAllRelatedIds();
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
      'email' => $this->email,
      'nip' => $this->nip,
      'nik' => $this->nik,
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
    }

    $typeParam = array_filter(['type' => $this->listType]);

    if ($this->isEdit) {
      $this->user->update($data);
      $this->user->syncRoles([$this->role]);
      return redirect()->route('master.users.index', $typeParam)->with('success', "Pegawai {$this->user->name} berhasil diperbarui.");
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

  private function normalizePhone(string $phone): string
  {
    if (str_contains($phone, '@')) {
      [$phone] = explode('@', $phone, 2);
    }
    $phone = preg_replace('/\D/', '', $phone);
    if (str_starts_with($phone, '0')) {
      $phone = '62' . substr($phone, 1);
    } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
      $phone = '62' . $phone;
    }
    return $phone;
  }

  public function render()
  {
    return view('livewire.user-form', [
      'departments' => $this->getHierarchicalDepartments(),
      'ranks' => Rank::orderBy('group')->get(),
      'positions' => Position::orderBy('level')->get(),
      'roles' => Role::all(),
      'employeeTypes' => EmployeeType::cases(),
      'dprdJabatans' => DprdJabatan::cases(),
      'dprdPartais' => DprdPartai::cases(),
    ]);
  }
}
