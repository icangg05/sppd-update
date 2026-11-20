<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\DprdJabatan;
use App\Enums\DprdPartai;
use App\Livewire\Concerns\InteractsWithToast;
use App\Livewire\Concerns\InteractsWithTelegramLink;
use App\Enums\PositionScope;
use App\Helpers\SmartTitle;
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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
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

  // Jabatan yang boleh dipangku Tenaga Mitra: jabatan Tenaga Mitra sendiri dan jabatan staf
  // (non-struktural), agar nama jabatan yang sudah ada (mis. Bidan) tetap bisa dipakai.
  private const TENAGA_MITRA_POSITION_ROLES = [User::TENAGA_MITRA_ROLE, 'staf'];

  public ?User $user = null;
  public bool $isEdit = false;

  // Mode Tenaga Mitra: orang dari luar OPD (mis. akademisi) yang hanya menjadi pengikut perjalanan,
  // tanpa akun login. Hanya diubah lewat setTenagaMitra() saat menambah pegawai; saat edit mengikuti
  // role tersimpan.
  #[Locked]
  public bool $tenagaMitra = false;

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

  // Mode Tenaga Mitra: nama jabatan diketik bebas. Bila sama dengan jabatan yang sudah ada,
  // jabatan itu yang dipakai; bila belum ada, jabatan baru dibuat saat disimpan.
  public string $jabatanTenagaMitra = '';

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
      $this->tenagaMitra = $user->isTenagaMitra();
      $this->jabatanTenagaMitra = $this->tenagaMitra ? ($user->position?->name ?? '') : '';
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
      // Username dipakai untuk login. Username lama yang bersimbol dibiarkan; aturan huruf
      // kecil, angka, dan titik hanya ditegakkan saat akun baru atau username diubah.
      'username' => array_filter([
        'required', 'string', 'max:255',
        $this->usernameChanged() ? 'regex:' . UsernameGenerator::PATTERN : null,
        'unique:users,username,' . $userId,
      ]),
      'email' => 'nullable|email|unique:users,email,' . $userId,
      'password' => 'nullable|string|min:6',
      // NIP/NIK hanya angka dengan panjang baku (NIP 18, NIK 16). NIP boleh sama di instansi berbeda
      // (pegawai bisa berakun di dua instansi), tapi tidak boleh ganda dalam satu instansi (induk + unit di bawahnya).
      // NIK tidak dicek keunikannya: orang yang sama bisa berakun di dua OPD atau lebih, dan data NIK
      // lama banyak yang kotor; orang yang sama dikenali dari NIP.
      'nip' => ['nullable', 'digits:18', 'not_regex:/^0+$/', $this->uniqueIdentityRule('nip', 'NIP')],
      'nik' => ['nullable', 'digits:16', 'not_regex:/^0+$/'],
      'department_id' => 'required|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      // Role Tenaga Mitra hanya diberikan lewat mode Tenaga Mitra, tidak lewat pilihan role.
      'role' => array_filter([
        'required', 'string',
        $this->tenagaMitra ? null : 'not_in:' . User::TENAGA_MITRA_ROLE,
        $this->selectableRoleRule(), $this->uniqueRoleRule(),
      ]),
    ];

    // Validasi: jabatan struktural yang dibatasi (Walikota, Sekda, Kepala OPD, dst.)
    // hanya boleh dipangku satu orang — sesuai scope-nya (sistem / per OPD).
    $rules['position_id'] = ['nullable', 'exists:positions,id', $this->uniquePositionRule()];

    // Jabatan Walikota & Wakil hanya diisi lewat menunya sendiri, dan menu itu hanya untuk keduanya.
    if (! $this->tenagaMitra) {
      $rules['position_id'][] = $this->kepalaDaerahPositionRule();
    }

    // Tenaga Mitra: tanpa akun login (username, email, password tidak disimpan), dan wajib berjabatan
    // (dokumen SPT mencetak nama jabatannya). Jabatan diketik; position_id diturunkan dari namanya
    // (lihat resolveJabatanTenagaMitra).
    if ($this->tenagaMitra) {
      unset($rules['username'], $rules['email'], $rules['password']);
      $rules['jabatanTenagaMitra'] = ['required', 'string', 'max:255', $this->tenagaMitraJabatanNameRule()];
      $rules['position_id'] = ['nullable', 'exists:positions,id', $this->tenagaMitraPositionRule()];
    }

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
   * Aturan: jabatan Walikota & Wakil Walikota hanya dipilih di menu Walikota & Wakil Walikota,
   * dan menu itu hanya untuk kedua jabatan tersebut. Daftar jabatan di form menampilkan semua
   * jabatan, jadi batas ini ditegakkan di sini.
   */
  protected function kepalaDaerahPositionRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      $position = $value ? Position::find($value) : null;
      if ($position && ($reason = $this->positionUnavailableReason($position))) {
        $fail("Jabatan {$position->name}: {$reason}.");
      }
    };
  }

  /** Aturan: Tenaga Mitra tidak memangku jabatan struktural (pimpinan, sekretaris, kabid, dst.). */
  protected function tenagaMitraPositionRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      $position = $value ? Position::find($value) : null;
      if ($position && ! in_array($position->default_role, self::TENAGA_MITRA_POSITION_ROLES, true)) {
        $fail("Jabatan {$position->name} adalah jabatan struktural. Gunakan nama jabatan lain.");
      }
    };
  }

  /** Aturan: nama jabatan Tenaga Mitra tidak boleh sama dengan jabatan struktural yang sudah terdaftar. */
  protected function tenagaMitraJabatanNameRule(): \Closure
  {
    return function (string $attribute, $value, \Closure $fail) {
      $existing = $this->matchingJabatanTenagaMitra();
      if ($existing && ! $this->isTenagaMitraPosition($existing)) {
        $fail("Jabatan {$existing->name} sudah terdaftar sebagai jabatan struktural sehingga tidak bisa dipakai Tenaga Mitra. Gunakan nama jabatan lain.");
      }
    };
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
        $label = ($this->department_id ? Department::find($this->department_id)?->roleTitle($value) : null)
          ?? Role::where('name', $value)->value('label') ?? $value;
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

  protected function messages()
  {
    return [
      'username.regex' => UsernameGenerator::INVALID_MESSAGE,
      'role.not_in' => 'Role Tenaga Mitra hanya diberikan lewat mode Tenaga Mitra.',
      'jabatanTenagaMitra.required' => 'Jabatan wajib diisi untuk Tenaga Mitra.',
    ];
  }

  /** Akun baru, atau username akun lama diganti (bukan sekadar disimpan ulang). */
  protected function usernameChanged(): bool
  {
    return ! $this->isEdit || $this->username !== $this->user->username;
  }

  protected function validationAttributes()
  {
    return [
      'nip' => 'NIP',
      'nik' => 'NIK',
      'department_id' => 'instansi / unit kerja',
      'position_id' => 'jabatan',
      'jabatanTenagaMitra' => 'jabatan',
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

  /** Mode Tenaga Mitra tersedia di form Pegawai biasa (semua unit kerja), bukan di menu DPRD / Walikota. */
  public function canUseTenagaMitraMode(): bool
  {
    return ! $this->isDprdContext() && ! $this->isKepalaDaerahContext();
  }

  /**
   * Alasan jabatan tidak bisa dipilih di form ini (null = bisa dipilih). Daftar jabatan tetap
   * menampilkannya (diredupkan beserta alasannya) agar jelas jabatan itu ada di sistem.
   */
  private function positionUnavailableReason(Position $position): ?string
  {
    $kepalaDaerah = in_array($position->default_role, User::KEPALA_DAERAH_ROLES, true);

    return match (true) {
      $this->tenagaMitra => $this->isTenagaMitraPosition($position) ? null : 'jabatan struktural, tidak untuk Tenaga Mitra',
      $this->isKepalaDaerahContext() => $kepalaDaerah ? null : 'menu ini hanya untuk Walikota dan Wakil Walikota',
      default => $kepalaDaerah ? 'diatur di menu Walikota & Wakil Walikota' : null,
    };
  }

  /** Nama jabatan untuk pencarian: huruf kecil tanpa spasi, agar "walikota" menemukan "Wali Kota". */
  private static function compactName(string $name): string
  {
    return str_replace(' ', '', mb_strtolower(trim($name)));
  }

  /** Jabatan yang namanya memuat $term (tanpa membedakan huruf besar/kecil dan spasi). */
  private static function wherePositionNameLike($query, string $term)
  {
    return $query->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", ['%' . self::compactName($term) . '%']);
  }

  /**
   * Pilih mode pegawai (Pegawai / Tenaga Mitra). Hanya saat menambah: pegawai yang sudah
   * tersimpan tidak berpindah mode, karena riwayat SPPD & jabatannya mengikuti mode lama.
   */
  public function setTenagaMitra(bool $on): void
  {
    if ($this->isEdit) {
      return;
    }

    $on = $on && $this->canUseTenagaMitraMode();
    if ($on === $this->tenagaMitra) {
      return;
    }

    $this->tenagaMitra = $on;

    // Daftar jabatan tiap mode berbeda, jadi pilihan sebelumnya dikosongkan.
    $this->position_id = '';
    $this->searchPosition = '';
    $this->jabatanTenagaMitra = '';
    $this->role = match (true) {
      $on => User::TENAGA_MITRA_ROLE,
      auth()->user()->hasRole('super_admin') => '',
      default => $this->roleFollowingPosition(),
    };
    $this->resetValidation();
  }

  /** Nama jabatan Tenaga Mitra dirapikan spasinya (spasi ganda dan di awal/akhir dibuang). */
  private function normalizedJabatanTenagaMitra(): string
  {
    return trim(preg_replace('/\s+/u', ' ', $this->jabatanTenagaMitra));
  }

  /** Jabatan terdaftar yang namanya sama (tanpa membedakan huruf besar/kecil dan spasi), bila ada. */
  private function matchingJabatanTenagaMitra(): ?Position
  {
    $name = $this->normalizedJabatanTenagaMitra();
    if ($name === '') {
      return null;
    }

    return Position::findByName($name)
      ?? Position::whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [self::compactName($name)])->first();
  }

  private function isTenagaMitraPosition(Position $position): bool
  {
    return in_array($position->default_role, self::TENAGA_MITRA_POSITION_ROLES, true);
  }

  /** position_id mengikuti nama yang diketik: jabatan Tenaga Mitra/staf bernama sama, atau kosong (jabatan baru). */
  private function resolveJabatanTenagaMitra(): void
  {
    $match = $this->matchingJabatanTenagaMitra();
    $this->position_id = $match && $this->isTenagaMitraPosition($match) ? $match->id : '';
  }

  public function updatedJabatanTenagaMitra(): void
  {
    $this->resolveJabatanTenagaMitra();
    $this->resetErrorBag(['jabatanTenagaMitra', 'position_id']);
  }

  /** Saran jabatan dipilih: namanya mengisi field jabatan. */
  public function pickJabatanTenagaMitra(int $id): void
  {
    $position = Position::whereIn('default_role', self::TENAGA_MITRA_POSITION_ROLES)->find($id);
    if (! $position) {
      return;
    }

    $this->jabatanTenagaMitra = $position->name;
    $this->position_id = $position->id;
    $this->resetErrorBag(['jabatanTenagaMitra', 'position_id']);
  }

  /**
   * Role tidak dipilih manual: Tenaga Mitra selalu ber-role Tenaga Mitra, bila field terkunci
   * role mengikuti jabatan, dan Anggota DPRD selalu mengikuti Jabatan DPRD (Ketua DPRD ⟹
   * Pimpinan DPRD, selain itu Anggota DPRD).
   */
  private function roleIsAutomatic(): bool
  {
    return $this->tenagaMitra || ! $this->canEditRole() || $this->isDprdContext();
  }

  public function selectPosition($id): void
  {
    // Jabatan yang tampil redup (lihat positionUnavailableReason) tidak bisa dipilih.
    $position = Position::find($id);
    if (! $position || $this->positionUnavailableReason($position)) {
      return;
    }

    $this->position_id = $position->id;
    $this->applyPositionRole();
  }

  /**
   * Role jabatan bergantung pada tipe unit (Pimpinan di Puskesmas = Kepala Puskesmas, di Dinas =
   * Kepala OPD), jadi dihitung ulang saat unit kerja diganti setelah jabatan dipilih.
   */
  public function updatedDepartmentId(): void
  {
    $this->applyPositionRole();
  }

  /**
   * Samakan role dengan role default jabatan terpilih. Role operasional
   * seperti Admin OPD tidak ditimpa.
   */
  private function applyPositionRole(): void
  {
    // Bila field terkunci atau mode Tenaga Mitra, role diturunkan otomatis (lihat roleFollowingPosition()).
    if ($this->tenagaMitra || ! $this->canEditRole()) {
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

  /**
   * Role yang tidak ditawarkan di unit kerja terpilih: di Kecamatan, Kelurahan, dan Puskesmas
   * Kepala/Sekretaris OPD diganti role khususnya (Camat, Sekcam, Lurah, Seklur, Kepala Puskesmas),
   * dan role khusus itu hanya ditawarkan di tipe unitnya.
   *
   * @return list<string>
   */
  private function rolesHiddenForUnit(): array
  {
    $type = $this->department_id ? Department::find($this->department_id)?->type : null;
    $replaced = $type?->replacedGenericRoles() ?? [];
    $ownSpecific = array_map(fn (string $role) => $type->specificRole($role), $replaced);

    return array_values(array_merge($replaced, array_diff(DepartmentType::specificRoles(), $ownSpecific)));
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
      ->when(! ($this->isEdit && $this->user->hasRole('super_admin')), fn ($c) => $c->where('name', '!=', 'super_admin'))
      // Tenaga Mitra hanya diberikan lewat mode Tenaga Mitra.
      ->where('name', '!=', User::TENAGA_MITRA_ROLE)
      // Pimpinan/Sekretaris memakai role khusus tipe unitnya (DepartmentType::specificRole).
      ->whereNotIn('name', array_diff($this->rolesHiddenForUnit(), [$this->originalRole()]));

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
    if ($this->tenagaMitra) {
      return User::TENAGA_MITRA_ROLE;
    }

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

    // Tenaga Mitra hanya menyimpan nama, NIP, NIK, dan jabatan, tanpa akun login. Isian lain
    // yang mungkin tersisa dari mode Pegawai dibuang.
    if ($this->tenagaMitra) {
      $this->username = '';
      $this->email = '';
      $this->password = '';
      $this->rank_id = '';

      // Jabatan ditentukan dari nama yang diketik, bukan dari position_id kiriman form.
      $this->jabatanTenagaMitra = $this->normalizedJabatanTenagaMitra();
      $this->resolveJabatanTenagaMitra();
    }

    // Spasi di awal/akhir (sering ikut saat menempel teks) dibuang sebelum divalidasi; input
    // Livewire tidak melewati middleware TrimStrings. Password sengaja tidak di-trim.
    $this->name = trim((string) $this->name);
    $this->username = trim((string) $this->username);
    $this->email = trim((string) $this->email);

    // NIP sering ditempel berformat "19810807 200502 2 004"; spasinya dibuang sebelum divalidasi.
    $this->nip = preg_replace('/\s+/', '', (string) $this->nip);
    $this->nik = preg_replace('/\s+/', '', (string) $this->nik);

    // Huruf besar dipaksa kecil; spasi/simbol tetap ditolak validasi.
    if ($this->usernameChanged()) {
      $this->username = mb_strtolower((string) $this->username);
    }

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

    // Tenaga Mitra dengan nama jabatan yang belum terdaftar: jabatan baru dibuat (tanpa tingkat).
    $newPosition = null;
    if ($this->tenagaMitra && ! $this->position_id) {
      $newPosition = Position::create([
        'name' => SmartTitle::convert($this->jabatanTenagaMitra),
        'default_role' => User::TENAGA_MITRA_ROLE,
        'uniqueness_scope' => PositionScope::NONE->value,
        'level' => Position::levelForRole(User::TENAGA_MITRA_ROLE),
        'created_by' => $currentUser->id,
      ]);
      $this->position_id = $newPosition->id;
      $this->jabatanTenagaMitra = $newPosition->name;
    }
    $newPositionNote = $newPosition ? " Jabatan baru \"{$newPosition->name}\" ikut ditambahkan." : '';

    $data = [
      'name' => $this->name,
      'username' => $this->tenagaMitra ? null : $this->username,
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

    if ($this->tenagaMitra) {
      // Tanpa akun login: kolom password wajib terisi, jadi diisi acak yang tidak pernah diberikan.
      if (! $this->isEdit) {
        $data['password'] = Hash::make(Str::random(40));
      }
    } elseif (!empty($this->password)) {
      $data['password'] = Hash::make($this->password);
    } elseif (!$this->isEdit) {
      // Pegawai baru tanpa password memakai password default dari config.
      $data['password'] = Hash::make(config('users.default_password'));
    }

    $typeParam = array_filter(['type' => $this->listType]);
    $sebutan = $this->tenagaMitra ? 'Tenaga Mitra' : 'Pegawai';

    if ($this->isEdit) {
      $this->user->update($data);
      $this->user->syncRoles([$this->role]);

      $this->toastSuccess("{$sebutan} {$this->user->name} berhasil diperbarui.{$newPositionNote}");
      return;
    } else {
      $data['is_active'] = true;
      if ($this->telegramLinked && ! $this->tenagaMitra) {
        $data['telegram_chat_id'] = $this->telegramChatId;
        $data['telegram_username'] = $this->telegramUsername ?: null;
        $data['telegram_linked_at'] = now();
        $data['telegram_notify'] = true;
      }
      $newUser = User::create($data);
      $newUser->assignRole($this->role);
      return redirect()->route('master.users.edit', [...$typeParam, 'user' => $newUser])->with('success', $this->tenagaMitra
        ? "Tenaga Mitra {$newUser->name} berhasil ditambahkan.{$newPositionNote}"
        : "Pegawai {$newUser->name} berhasil ditambahkan. Silakan lengkapi data jika diperlukan.");
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
    if ($this->isKepalaDaerahContext()) {
      // Menu Walikota & Wakil Walikota hanya untuk kedua jabatan itu.
      $positionQuery->whereIn('default_role', User::KEPALA_DAERAH_ROLES);
    } else {
      // Form Pegawai: semua jabatan bisa ditemukan. Walikota & Wakil (diatur di menunya sendiri)
      // tampil redup di urutan terakhir beserta alasannya, dan ditolak bila tetap dikirim.
      $positionQuery->orderByRaw('CASE WHEN default_role IN (?, ?) THEN 1 ELSE 0 END', User::KEPALA_DAERAH_ROLES);
    }
    if (trim($this->searchPosition) !== '') {
      self::wherePositionNameLike($positionQuery, $this->searchPosition);
    }

    $limit = 25;
    $positions = $positionQuery->orderBy('level')->orderBy('name')->limit($limit + 1)->get()
      ->each(fn (Position $p) => $p->unavailableReason = $this->positionUnavailableReason($p));
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

    // Mode Tenaga Mitra: saran jabatan yang cocok dengan nama yang diketik, dan keterangan apakah
    // namanya jabatan lama atau baru. Semua jabatan ditampilkan: jabatan Tenaga Mitra dulu, lalu
    // staf, lalu jabatan struktural yang tampil redup karena tidak bisa dipakai Tenaga Mitra.
    $jabatanSuggestions = collect();
    $jabatanSuggestionsHasMore = false;
    $jabatanStatus = null;
    if ($this->tenagaMitra) {
      $term = $this->normalizedJabatanTenagaMitra();
      $suggestionLimit = 10;
      $jabatanSuggestions = Position::query()
        ->when($term !== '', fn ($q) => self::wherePositionNameLike($q, $term))
        ->orderByRaw(
          'CASE WHEN default_role = ? THEN 0 WHEN default_role = ? THEN 1 ELSE 2 END',
          self::TENAGA_MITRA_POSITION_ROLES,
        )
        ->orderBy('level')
        ->orderBy('name')
        ->limit($suggestionLimit + 1)
        ->get(['id', 'name', 'default_role'])
        ->each(fn (Position $p) => $p->unavailableReason = $this->positionUnavailableReason($p));
      $jabatanSuggestionsHasMore = $jabatanSuggestions->count() > $suggestionLimit;
      $jabatanSuggestions = $jabatanSuggestions->take($suggestionLimit);
      $jabatanStatus = match (true) {
        $term === '' => null,
        (bool) $this->position_id => 'existing',
        $this->matchingJabatanTenagaMitra() !== null => 'struktural',
        default => 'new',
      };
    }

    return view('livewire.user-form', [
      'jabatanSuggestions' => $jabatanSuggestions,
      'jabatanSuggestionsHasMore' => $jabatanSuggestionsHasMore,
      'jabatanStatus' => $jabatanStatus,
      'jabatanBaru' => $jabatanStatus === 'new' ? SmartTitle::convert($this->normalizedJabatanTenagaMitra()) : null,
      'departmentTree' => $departmentTree,
      'showDepartmentPicker' => ! $this->hasSingleDepartment($departmentTree),
      // Pemilih mode hanya saat menambah pegawai (bukan di menu DPRD / Walikota).
      'showModeSwitch' => ! $this->isEdit && $this->canUseTenagaMitraMode(),
      // Unit yang dipimpin pegawai ini (diatur di halaman struktur unit kerja).
      'ranks' => Rank::orderBy('group')->get(),
      'positions' => $positions,
      'positionsHasMore' => $positionsHasMore,
      'selectedPosition' => $selectedPosition,
      'positionRole' => $positionRole,
      'positionRoleAdjusted' => $positionRoleAdjusted,
      'positionRequiredType' => $positionRequiredType,
      // Sebutan role mengikuti unit kerja terpilih (Rumah Sakit: Kepala OPD = Direktur).
      'selectedDepartment' => $selectedDepartment,
      'kepalaLabel' => $selectedDepartment?->roleLabel('kepala_opd', 'Kepala OPD') ?? 'Kepala OPD',
      'roleOptions' => $this->selectableRoles()->map(fn ($r) => ['value' => $r->name, 'label' => $selectedDepartment?->roleTitle($r->name) ?? $r->label])->values()->all(),
      'dprdJabatans' => DprdJabatan::cases(),
      'dprdPartais' => DprdPartai::cases(),
    ]);
  }
}
