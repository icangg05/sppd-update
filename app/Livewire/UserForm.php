<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\DprdJabatan;
use App\Enums\DprdPartai;
use App\Livewire\Concerns\InteractsWithToast;
use App\Livewire\Concerns\InteractsWithTelegramLink;
use App\Enums\PositionScope;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use App\Services\SppdWorkflowService;
use App\Rules\UniqueIdentityInInstansi;
use App\Services\UsernameGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
  use InteractsWithToast;
  use InteractsWithTelegramLink;

  // Role operasional yang tidak terkait jabatan — tidak ditimpa otomatis saat
  // jabatan diganti (agar super admin tak kehilangan akses & admin OPD tak turun jadi staf).
  private const ROLES_NOT_FROM_POSITION = ['super_admin', 'admin_opd'];

  // Role di atas lingkup OPD (atau khusus DPRD): Admin OPD tidak bisa memilihnya bebas,
  // hanya mengikuti jabatan, dan pegawai pemegangnya hanya bisa diubah Super Admin.
  private const ROLES_SUPER_ADMIN_ONLY = [
    'super_admin',
    'walikota',
    'wakil_walikota',
    'sekda',
    'asisten',
    'sekwan',
    'pimpinan_dprd',
    'anggota_dprd',
  ];

  public ?User $user = null;
  public bool $isEdit = false;

  // Konteks asal: 'dprd' (menu Anggota DPRD) atau 'kepala_daerah' (menu Walikota & Wakil Walikota).
  // Dipakai agar menu sidebar & tombol kembali tetap mengarah ke daftar yang benar.
  public string $listType = '';

  // Form fields
  public $name = '';
  public $username = '';
  public $email = '';
  public $password = '';
  public $nip = '';
  public $nik = '';
  public $department_id = '';
  public $rank_id = '';
  public $position_id = '';
  public $role = '';

  // Pencarian server-side untuk jabatan (agar tidak memuat seluruh data jabatan sekaligus).
  public string $searchPosition = '';

  // Data khusus Anggota DPRD
  public $dprd_jabatan = '';
  public $partai = '';

  // State tautan Telegram disediakan oleh InteractsWithTelegramLink.

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
      $this->department_id = $user->department_id;
      $this->rank_id = $user->rank_id;
      $this->position_id = $user->position_id;
      $this->role = $user->roles->first()?->name ?? '';
      $this->dprd_jabatan = $user->dprd_jabatan ?? '';
      $this->partai = $user->partai ?? '';
      $this->fillTelegramState($user->telegram_chat_id, $user->telegram_username);

      // Edit pegawai DPRD / Walikota & Wakil tetap dianggap konteks menunya walau parameter URL hilang.
      if ($user->isDprdMember()) {
        $this->listType = 'dprd';
      } elseif ($user->isKepalaDaerah()) {
        $this->listType = 'kepala_daerah';
      }
    }

    // Konteks dari halaman asal (mis. dibuka dari menu Anggota DPRD / Walikota & Wakil Walikota).
    if (in_array(request('type'), ['dprd', 'kepala_daerah'], true)) {
      $this->listType = request('type');
    }

    // Unit kerja hanya satu pilihan: isi otomatis (pemilihnya tidak ditampilkan).
    $tree = $this->getDepartmentTree();
    if ($this->hasSingleDepartment($tree)) {
      $this->department_id = $tree[0]['value'];
    } elseif ($this->isDprdContext() && ! $this->isEdit && ! $this->department_id) {
      // Anggota DPRD baru: unit kerja langsung Sekretariat DPRD (tetap bisa diganti).
      $this->department_id = $this->departmentIdOfType($tree, DepartmentType::DPRD) ?? '';
    } elseif ($this->isKepalaDaerahContext() && ! $this->isEdit && ! $this->department_id) {
      // Walikota / Wakil baru: unit kerja langsung Sekretariat Daerah (tetap bisa diganti).
      $this->department_id = $this->departmentIdOfType($tree, DepartmentType::SETDA) ?? '';
    }

    // Admin OPD tetap mendapat isian awal (Staf / role jabatan) agar tidak wajib memilih role dari kosong.
    if ($this->role === '' && ! auth()->user()->hasRole('super_admin')) {
      $this->role = $this->roleFollowingPosition();
    }

    // Memulihkan polling jika halaman di-refresh saat menunggu pegawai menekan Start.
    $this->restoreTelegramLinkState();
  }

  protected function linkUserId(): ?int
  {
    return $this->isEdit ? $this->user->id : null;
  }

  // Pegawai baru: chat_id hasil polling ikut disimpan saat tombol Simpan ditekan.
  protected function onTelegramLinked(string $chatId, ?string $username): void
  {
    if ($this->isEdit && $this->user) {
      $this->user->update([
        'telegram_chat_id' => $chatId,
        'telegram_username' => $username,
        'telegram_linked_at' => now(),
        'telegram_notify' => true,
      ]);
    }
  }

  protected function onTelegramUnlinked(): void
  {
    if ($this->isEdit && $this->user) {
      $this->user->update([
        'telegram_chat_id' => null,
        'telegram_username' => null,
        'telegram_linked_at' => null,
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
      // NIP/NIK hanya angka dengan panjang baku (NIP 18, NIK 16). Boleh sama di instansi berbeda
      // (pegawai bisa berakun di dua instansi), tapi tidak boleh ganda dalam satu instansi (induk + unit di bawahnya).
      'nip' => ['nullable', 'digits:18', 'not_regex:/^0+$/', $this->uniqueIdentityRule('nip', 'NIP')],
      'nik' => ['nullable', 'digits:16', 'not_regex:/^0+$/', $this->uniqueIdentityRule('nik', 'NIK')],
      'department_id' => 'required|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      'role' => ['required', 'string', $this->selectableRoleRule(), $this->uniqueRoleRule()],
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

  protected function uniqueIdentityRule(string $column, string $label): UniqueIdentityInInstansi
  {
    return new UniqueIdentityInInstansi(
      $column,
      $label,
      $this->department_id ? (int) $this->department_id : null,
      $this->isEdit ? $this->user->id : null,
    );
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

      $holder = User::roleConflictHolder(
        $value,
        $this->department_id ? (int) $this->department_id : null,
        $this->isEdit ? $this->user->id : null,
      );

      if ($holder) {
        $label = Role::where('name', $value)->value('label') ?? $value;
        $cakupan = in_array($value, config('role_uniqueness.department', []), true)
          ? 'pada unit kerja ini'
          : 'di lingkup pemerintah daerah';
        $fail("Role {$label} sudah dipegang oleh {$holder->name} {$cakupan}. Nonaktifkan pejabat lama terlebih dahulu.");
      }
    };
  }

  /**
   * Aturan: Admin OPD hanya boleh menyimpan role yang memang ditawarkan kepadanya.
   * Dicek di server karena nilai role dari formulir bisa dimanipulasi.
   */
  protected function selectableRoleRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      if ($this->roleIsAutomatic() || auth()->user()->hasRole('super_admin')) {
        return;
      }

      if (! $this->selectableRoles()->contains('name', $value)) {
        $fail('Role ini hanya dapat diatur oleh Super Admin.');
      }
    };
  }

  /**
   * Aturan: jabatan pimpinan DPRD (Ketua & Wakil Ketua) hanya boleh satu orang.
   */
  protected function uniqueDprdJabatanRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      // Semua jabatan bersifat tunggal kecuali anggota (komisi maupun tanpa komisi).
      $shared = [
        DprdJabatan::KOMISI_1->value,
        DprdJabatan::KOMISI_2->value,
        DprdJabatan::KOMISI_3->value,
        DprdJabatan::ANGGOTA->value,
      ];

      if (in_array($value, $shared, true)) {
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
      'nip' => 'NIP',
      'nik' => 'NIK',
      'department_id' => 'instansi / unit kerja',
      'position_id' => 'jabatan',
      'dprd_jabatan' => 'jabatan DPRD',
      'partai' => 'partai / fraksi',
    ];
  }

  /**
   * Menentukan apakah pegawai yang sedang diisi merupakan Anggota DPRD:
   * formulir dibuka dari menu Anggota DPRD, atau role DPRD dipilih.
   */
  public function isDprdMember(): bool
  {
    return $this->isDprdContext() || in_array($this->role, User::DPRD_ROLES, true);
  }

  /** Unit kerja pertama berjenis $type (mis. Sekretariat DPRD / Setda) di antara unit yang bisa dipilih pengguna ini. */
  private function departmentIdOfType(array $tree, DepartmentType $type): ?int
  {
    $selectable = [];
    $collect = function (array $nodes) use (&$collect, &$selectable) {
      foreach ($nodes as $node) {
        $selectable[] = (int) $node['value'];
        $collect($node['children'] ?? []);
      }
    };
    $collect($tree);

    return Department::where('type', $type)->whereIn('id', $selectable)->orderBy('id')->value('id');
  }

  /** Formulir dibuka dari menu Walikota & Wakil Walikota (pilihan jabatan dibatasi ke keduanya). */
  public function isKepalaDaerahContext(): bool
  {
    return $this->listType === 'kepala_daerah';
  }

  /**
   * Formulir sedang dalam konteks Anggota DPRD (dibuka dari menu Anggota DPRD).
   * Pada konteks ini field kepegawaian umum
   * (NIP, pangkat, jabatan struktural) disembunyikan.
   */
  public function isDprdContext(): bool
  {
    return $this->listType === 'dprd';
  }

  /**
   * Role tidak dipilih manual: bila field terkunci role mengikuti jabatan, dan Anggota DPRD
   * selalu mengikuti Jabatan DPRD (Ketua DPRD ⟹ Pimpinan DPRD, selain itu Anggota DPRD).
   */
  private function roleIsAutomatic(): bool
  {
    return ! $this->canEditRole() || $this->isDprdContext();
  }

  public function selectPosition($id): void
  {
    $this->position_id = (int) $id;
    $this->applyPositionRole();
  }

  /**
   * Samakan role dengan role default jabatan terpilih. Role operasional
   * seperti Admin OPD tidak ditimpa.
   */
  private function applyPositionRole(): void
  {
    // Bila field terkunci, role diturunkan otomatis (lihat roleFollowingPosition()).
    if (! $this->canEditRole()) {
      return;
    }

    $roleName = $this->positionDefaultRole();
    if (! $roleName || in_array($this->role, self::ROLES_NOT_FROM_POSITION, true)) {
      return;
    }

    $this->role = $roleName;
    $this->resetErrorBag('role');
  }

  /**
   * Super Admin bebas memilih role. Admin OPD boleh mengubah role pegawai di
   * lingkup OPD-nya, kecuali akunnya sendiri, data di menu Walikota & Wakil,
   * dan pegawai pemegang role khusus Super Admin.
   */
  public function canEditRole(): bool
  {
    $actor = auth()->user();

    if ($actor->hasRole('super_admin')) {
      return true;
    }

    if (! $actor->hasRole('admin_opd') || $this->isKepalaDaerahContext()) {
      return false;
    }

    if (! $this->isEdit) {
      return true;
    }

    return $this->user->id !== $actor->id
      && ! in_array($this->originalRole(), self::ROLES_SUPER_ADMIN_ONLY, true)
      && (bool) $actor->department?->getScopedRelatedIds()->contains($this->user->department_id);
  }

  /** Role pegawai yang tersimpan saat ini (null untuk pegawai baru). */
  private function originalRole(): ?string
  {
    return $this->isEdit ? $this->user->roles->first()?->name : null;
  }

  /**
   * Role yang ditawarkan di formulir ini. Pilihan Admin OPD divalidasi ulang
   * terhadap daftar yang sama di server (lihat selectableRoleRule()).
   */
  private function selectableRoles(): Collection
  {
    $roles = Role::all()
      // Role DPRD hanya tersedia bila pegawai bertipe DPRD.
      ->when(! $this->isDprdMember(), fn ($c) => $c->whereNotIn('name', User::DPRD_ROLES))
      // Walikota & Wakil dikelola di menunya sendiri: di sana hanya kedua role itu, di form Pegawai disembunyikan.
      ->when(
        $this->isKepalaDaerahContext(),
        fn ($c) => $c->whereIn('name', User::KEPALA_DAERAH_ROLES),
        fn ($c) => $c->whereNotIn('name', User::KEPALA_DAERAH_ROLES),
      )
      // Super Admin hanya satu: tidak ditawarkan kecuali untuk pemegangnya sendiri.
      ->when(! ($this->isEdit && $this->user->hasRole('super_admin')), fn ($c) => $c->where('name', '!=', 'super_admin'));

    if (auth()->user()->hasRole('super_admin')) {
      return $roles;
    }

    // Admin OPD: role khusus Super Admin hanya muncul bila sudah dipegang atau berasal dari jabatan.
    $kept = [$this->originalRole(), $this->positionDefaultRole()];

    return $roles->reject(
      fn (Role $role) => in_array($role->name, self::ROLES_SUPER_ADMIN_ONLY, true) && ! in_array($role->name, $kept, true)
    );
  }

  /**
   * Role jabatan terpilih yang berlaku di unit kerja pegawai (lihat
   * Position::roleForDepartment), bila ada dan role-nya terdaftar.
   */
  private function positionDefaultRole(): ?string
  {
    $position = $this->position_id ? Position::find($this->position_id) : null;
    $roleName = $position?->roleForDepartment($this->department_id ? Department::find($this->department_id) : null);

    return $roleName && Role::where('name', $roleName)->exists() ? $roleName : null;
  }

  /**
   * Role yang berlaku bila field role terkunci. Dihitung di server (bukan dari
   * input) agar role tidak bisa dimanipulasi.
   */
  private function roleFollowingPosition(): string
  {
    $original = $this->originalRole();

    // Kewenangan operasional (Super Admin / Admin OPD) tidak berubah mengikuti jabatan.
    if (in_array($original, self::ROLES_NOT_FROM_POSITION, true)) {
      return $original;
    }

    // Anggota DPRD: Ketua DPRD => Pimpinan DPRD, selain itu Anggota DPRD.
    if ($this->isDprdMember()) {
      return $this->dprd_jabatan === DprdJabatan::KETUA->value ? 'pimpinan_dprd' : 'anggota_dprd';
    }

    if ($positionRole = $this->positionDefaultRole()) {
      return $positionRole;
    }

    // Jabatan tanpa role default: pertahankan role lama (kecuali role DPRD), atau staf.
    return $original && ! in_array($original, ['pimpinan_dprd', 'anggota_dprd'], true) ? $original : 'staf';
  }

  /**
   * Jabatan baru dari modal tambah jabatan langsung dipilih (role ikut terisi).
   */
  #[On('position-saved')]
  public function onPositionSaved(int $id, bool $created = false): void
  {
    if ($created) {
      $this->searchPosition = '';
      $this->selectPosition($id);
    }
  }

  public function clearPosition(): void
  {
    $this->position_id = '';
  }

  // Metode tautan Telegram (openLinkModal, checkLinkStatus, dll.)
  // disediakan oleh InteractsWithTelegramLink.

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
    if ($this->roleIsAutomatic()) {
      $this->role = $this->roleFollowingPosition();
    }

    // NIP sering ditempel berformat "19810807 200502 2 004"; spasinya dibuang sebelum divalidasi.
    $this->nip = preg_replace('/\s+/', '', (string) $this->nip);
    $this->nik = preg_replace('/\s+/', '', (string) $this->nik);

    try {
      $this->validate();
    } catch (ValidationException $e) {
      $this->toastError('Periksa kembali isian formulir.');
      throw $e;
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
      'department_id' => $this->department_id ?: null,
      'rank_id' => $this->rank_id ?: null,
      'position_id' => $this->position_id ?: null,
    ];

    // Atasan langsung tidak diatur di sini, tetapi dipilih saat membuat SPPD. Bila unit kerja/role
    // berubah sehingga atasan lama tidak lagi sah, kosongkan agar dipilih ulang saat SPPD dibuat.
    if ($this->isEdit && $this->user->atasan_id) {
      $placementChanged = (int) $this->department_id !== (int) $this->user->department_id
        || $this->role !== $this->user->roles->first()?->name;
      if ($placementChanged && $this->supervisorError($this->user->atasan_id)) {
        $data['atasan_id'] = null;
      }
    }

    // Simpan data DPRD hanya untuk Anggota DPRD; selain itu kosongkan agar tidak ada data basi.
    if ($this->isDprdMember()) {
      $data['dprd_jabatan'] = $this->dprd_jabatan ?: null;
      $data['partai'] = $this->partai ?: null;
    } else {
      $data['dprd_jabatan'] = null;
      $data['partai'] = null;
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
      if ($this->telegramLinked) {
        $data['telegram_chat_id'] = $this->telegramChatId;
        $data['telegram_username'] = $this->telegramUsername ?: null;
        $data['telegram_linked_at'] = now();
        $data['telegram_notify'] = true;
      }
      $newUser = User::create($data);
      $newUser->assignRole($this->role);
      return redirect()->route('master.users.edit', [...$typeParam, 'user' => $newUser])->with('success', "Pegawai {$newUser->name} berhasil ditambahkan. Silakan lengkapi data jika diperlukan.");
    }
  }

  /** Pegawai yang sedang diisi, dengan unit kerja dari form (untuk calon & validasi atasan). */
  private function supervisorSubject(): User
  {
    $subject = $this->isEdit ? clone $this->user : new User();
    $subject->name = $this->name ?: 'Pegawai';
    $subject->department_id = $this->department_id ?: null;
    $subject->setRelation('department', $this->department_id ? Department::find($this->department_id) : null);

    return $subject;
  }

  /** Alasan atasan ini tidak sah untuk unit & role di form (null = sah). */
  private function supervisorError(int $atasanId): ?string
  {
    $service = app(SppdWorkflowService::class);
    $rank = Position::levelForRole($this->role);
    $subject = $this->supervisorSubject();

    $allowed = array_column($service->supervisorCandidates($subject, '', $rank, 500), 'id');
    if (! in_array($atasanId, $allowed, true)) {
      return 'Pilih atasan dari pejabat di atas pegawai ini pada instansinya.';
    }

    return $service->supervisorError($subject, User::with('roles')->find($atasanId), $rank);
  }

  /**
   * Pohon unit kerja untuk pemilih berjenjang (x-form.tree-select).
   * Satu query lalu dirakit di memori — bukan satu query per simpul.
   */
  private function getDepartmentTree(): array
  {
    $user = auth()->user();

    $all      = Department::treeOrder()->get(['id', 'parent_id', 'name', 'type']);
    $byParent = $all->groupBy(fn($d) => $d->parent_id ?? 0);

    $roots = $user->hasRole('super_admin')
      ? $byParent->get(0, collect())
      : $all->where('id', $user->department_id)->values();

    $build = function ($dept) use (&$build, $byParent) {
      return [
        'value'    => $dept->id,
        'label'    => $dept->name,
        'meta'     => $dept->type?->label(),
        'children' => $byParent->get($dept->id, collect())->map($build)->values()->all(),
      ];
    };

    return $roots->map($build)->values()->all();
  }

  /**
   * Admin OPD yang instansinya tanpa sub-unit hanya punya satu unit kerja —
   * tidak perlu dipilih. Super Admin tetap melihat pemilih (dan tautan tambah unit).
   */
  private function hasSingleDepartment(array $tree): bool
  {
    return ! auth()->user()->hasRole('super_admin')
      && count($tree) === 1
      && empty($tree[0]['children']);
  }

  public function render()
  {
    // Bila field role terkunci atau Anggota DPRD (selalu mengikuti Jabatan DPRD),
    // tampilkan role yang benar-benar akan disimpan.
    if ($this->roleIsAutomatic()) {
      $this->role = $this->roleFollowingPosition();
    }

    // Pencarian jabatan server-side: hanya memuat sebagian data (bukan seluruh jabatan).
    $positionQuery = Position::query();
    // Menu Walikota & Wakil Walikota: hanya jabatan Walikota / Wakil Walikota. Di form Pegawai
    // jabatan itu disembunyikan, karena datanya dikelola di menu tersendiri.
    if ($this->isKepalaDaerahContext()) {
      $positionQuery->whereIn('default_role', User::KEPALA_DAERAH_ROLES);
    } else {
      $positionQuery->where(fn ($q) => $q->whereNull('default_role')->orWhereNotIn('default_role', User::KEPALA_DAERAH_ROLES));
    }
    if (trim($this->searchPosition) !== '') {
      $positionQuery->where('name', 'like', '%' . trim($this->searchPosition) . '%');
    }

    $limit = 25;
    $positions = $positionQuery->orderBy('level')->limit($limit + 1)->get();
    $positionsHasMore = $positions->count() > $limit;
    $positions = $positions->take($limit);

    // Jabatan terpilih tetap tampil di trigger walau di luar hasil pencarian.
    $selectedPosition = $this->position_id ? Position::find($this->position_id) : null;

    // Role jabatan terpilih di unit kerja ini — untuk petunjuk sinkron jabatan ↔ role.
    $positionRole = $this->positionDefaultRole();
    // true bila role default jabatan diturunkan ke Staf karena tak dikenal alur unit ini.
    $positionRoleAdjusted = $positionRole && $positionRole !== $selectedPosition?->default_role;

    // Jabatan kewilayahan (Camat/Lurah/Kapus) yang dipasang di jenis unit lain.
    $selectedDepartment = $this->department_id ? Department::find($this->department_id) : null;
    $positionRequiredType = $selectedPosition && ! $selectedPosition->fitsDepartment($selectedDepartment)
      ? $selectedPosition->requiredDepartmentType()
      : null;

    $departmentTree = $this->getDepartmentTree();

    return view('livewire.user-form', [
      'departmentTree' => $departmentTree,
      'showDepartmentPicker' => ! $this->hasSingleDepartment($departmentTree),
      // Unit yang dipimpin pegawai ini (diatur di halaman struktur unit kerja).
      'ranks' => Rank::orderBy('group')->get(),
      'positions' => $positions,
      'positionsHasMore' => $positionsHasMore,
      'selectedPosition' => $selectedPosition,
      'positionRole' => $positionRole,
      'positionRoleAdjusted' => $positionRoleAdjusted,
      'positionRequiredType' => $positionRequiredType,
      'roleOptions' => $this->selectableRoles()->map(fn ($r) => ['value' => $r->name, 'label' => $r->label])->values()->all(),
      'dprdJabatans' => DprdJabatan::cases(),
      'dprdPartais' => DprdPartai::cases(),
    ]);
  }
}
