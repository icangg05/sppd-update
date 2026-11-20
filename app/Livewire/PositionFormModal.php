<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Enums\PositionScope;
use App\Enums\PositionTier;
use App\Helpers\SmartTitle;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Modal tambah / edit jabatan — dipakai bersama oleh halaman Data Jabatan dan
 * formulir pegawai. Semua pengguna memilih Tingkat Jabatan (ditebak dari nama)
 * yang menurunkan role default, cakupan, dan level; Super Admin juga bisa
 * mengubah level. Nilai otomatis selalu diisi lebih dulu dan tetap bisa diubah.
 *
 * Dibuka lewat event `open-position-form` dan mengirim `position-saved`
 * setelah berhasil disimpan. Dari wizard Lengkapi/Ganti Pejabat, event membawa
 * `role` tahap: tingkat jabatan dikunci ke role itu agar jabatan barunya sah
 * untuk tahap tersebut (termasuk role khusus seperti Sekda/Walikota). Jabatan Tenaga Mitra
 * (role tenaga_mitra, dibuat otomatis dari form pegawai) cukup diedit namanya, tanpa tingkat.
 */
class PositionFormModal extends Component
{
  use InteractsWithToast;

  public bool $show = false;
  public ?int $editingId = null;

  public string $name = '';
  public string $tier = PositionTier::STAF->value;
  public int $level = Position::DEFAULT_LEVEL;

  // Saat edit: tingkat, role, dan cakupan tersimpan. Bila tingkat tidak diganti,
  // role & cakupan lama dipertahankan (mis. role khusus Walikota/Sekwan).
  public ?string $originalTier = null;
  public ?string $storedRole = null;
  public ?string $storedScope = null;

  // Role khusus unit untuk tingkat Pimpinan/Sekretaris (camat, lurah, kapus, sekcam, seklur);
  // '' = role umum yang menyesuaikan unit kerja (DepartmentType::specificRole).
  public string $unitRole = '';
  public string $originalUnitRole = '';

  // Tebakan tingkat dari nama berhenti setelah pengguna memilih tingkat sendiri.
  public bool $tierTouched = false;

  // Level mengikuti tingkat sampai Super Admin mengisinya sendiri.
  public bool $levelTouched = false;

  // Role tahap alur yang menjadi role default jabatan baru (tingkat tidak bisa diganti).
  #[Locked]
  public ?string $fixedRole = null;

  // Unit kerja pegawai di form pemanggil. Dari form pegawai, role khusus ditebak dari tipe unit ini
  // tanpa pilihan Role (pilihan itu hanya di halaman Data Jabatan); lihat defaultUnitRole.
  #[Locked]
  public ?int $departmentId = null;

  #[On('open-position-form')]
  public function open(?int $id = null, string $name = '', ?string $role = null, $departmentId = null): void
  {
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $this->reset(['editingId', 'name', 'tier', 'level', 'originalTier', 'storedRole', 'storedScope', 'unitRole', 'originalUnitRole', 'tierTouched', 'levelTouched', 'fixedRole', 'departmentId']);
    $this->resetValidation();
    $this->departmentId = is_numeric($departmentId) ? (int) $departmentId : null;

    if (! $id && $role && Role::where('name', $role)->exists()) {
      $this->fixedRole   = $role;
      $this->name        = trim($name);
      $this->tier        = PositionTier::fromRole($role)->value;
      $this->tierTouched = true;
      $this->level       = Position::levelForRole($role);
      $this->show        = true;

      return;
    }

    if ($id) {
      $position = Position::with('creator')->findOrFail($id);
      abort_unless($position->isManageableBy(auth()->user()), 403);

      $this->editingId    = $position->id;
      $this->name         = $position->name;
      $this->level        = (int) $position->level;
      $this->tier         = PositionTier::fromRole($position->default_role)->value;
      $this->originalTier = $this->tier;
      $this->storedRole   = $position->default_role;
      $this->storedScope  = ($position->uniqueness_scope ?? PositionScope::NONE)->value;
      $this->unitRole     = in_array($position->default_role, DepartmentType::specificRoles(), true) ? $position->default_role : '';
      $this->originalUnitRole = $this->unitRole;
      $this->tierTouched  = true;
    } else {
      $this->name = trim($name);
      $this->guessFromName();
    }

    $this->show = true;
  }

  /**
   * Tipe unit untuk ringkasan role: unit kerja di form pegawai pemanggil, atau unit Admin OPD
   * sendiri. Null bila tidak diketahui (Super Admin membuka dari Data Jabatan).
   */
  private function summaryUnitType(): ?DepartmentType
  {
    if ($this->departmentId) {
      return Department::find($this->departmentId)?->type;
    }

    return $this->isSuperAdmin() ? null : auth()->user()->department?->type;
  }

  public function isSuperAdmin(): bool
  {
    return auth()->user()->hasRole('super_admin');
  }

  /**
   * Jabatan Tenaga Mitra — baru dari mode Tenaga Mitra di form pegawai, atau yang sedang
   * diedit: cukup nama jabatan, tanpa pilihan tingkat & level, dan tingkatnya tidak berganti.
   */
  public function isTenagaMitraPosition(): bool
  {
    return $this->fixedRole === User::TENAGA_MITRA_ROLE
      || ($this->editingId && $this->originalTier === PositionTier::TENAGA_MITRA->value);
  }

  public function updatedName(): void
  {
    if (! $this->editingId && ! $this->tierTouched) {
      $this->guessFromName();
    } elseif ($this->departmentId) {
      $this->unitRole = $this->defaultUnitRole();
    }
  }

  public function updatedTier(): void
  {
    if ($this->fixedRole) {
      $this->tier = PositionTier::fromRole($this->fixedRole)->value;

      return;
    }

    if ($this->isTenagaMitraPosition()) {
      $this->tier = PositionTier::TENAGA_MITRA->value;

      return;
    }

    $this->tierTouched = true;
    // Dari form pegawai role khusus selalu mengikuti unit; di Data Jabatan pilihan yang masih sah dipertahankan.
    if ($this->departmentId || ! in_array($this->unitRole, $this->unitRoleChoices(), true)) {
      $this->unitRole = $this->defaultUnitRole();
    }
    $this->applyTierLevel();
  }

  public function updatedLevel(): void
  {
    $this->levelTouched = true;
  }

  private function guessFromName(): void
  {
    if (trim($this->name) === '') {
      return;
    }

    // Nama yang tidak dikenali dimulai sebagai Staf (Lainnya tidak bisa dipilih).
    $guess = PositionTier::guessFromName($this->name);
    $this->tier = ($guess === PositionTier::LAINNYA ? PositionTier::STAF : $guess)->value;
    $this->unitRole = $this->defaultUnitRole();
    $this->applyTierLevel();
  }

  /** Role khusus yang bisa dipilih untuk tingkat terpilih: Pimpinan → camat, lurah, kapus. */
  private function unitRoleChoices(): array
  {
    $role = $this->fixedRole ? null : $this->currentTier()->role();

    return $role ? DepartmentType::specificRolesFor($role) : [];
  }

  /**
   * Role khusus bawaan: dari nama jabatan kewilayahan (Camat → camat), atau bila dibuka dari form
   * pegawai, dari tipe unit kerjanya (Pimpinan di Puskesmas → kapus). '' = role umum.
   */
  private function defaultUnitRole(): string
  {
    $role = $this->currentTier()->role();
    $type = (new Position(['name' => $this->name]))->requiredDepartmentType()
      ?? ($this->departmentId ? Department::find($this->departmentId)?->type : null);
    $specific = $role && $type ? $type->specificRole($role) : '';

    return in_array($specific, $this->unitRoleChoices(), true) ? $specific : '';
  }

  /** Role atau role khusus berubah dari yang tersimpan (jabatan baru selalu dianggap berubah). */
  private function roleChanged(): bool
  {
    return $this->tierChanged() || $this->unitRole !== $this->originalUnitRole;
  }

  /**
   * Role default yang akan disimpan: role terkunci dari pemanggil, role tersimpan bila tidak
   * diganti (mis. role khusus Walikota/Sekwan), selain itu role khusus terpilih atau role tingkat.
   * Role khusus di luar pilihan tingkat (manipulasi klien) kembali ke role umum.
   */
  private function selectedRole(): ?string
  {
    if ($this->fixedRole) {
      return $this->fixedRole;
    }

    if (! $this->roleChanged()) {
      return $this->storedRole;
    }

    return in_array($this->unitRole, $this->unitRoleChoices(), true) ? $this->unitRole : $this->currentTier()->role();
  }

  private function applyTierLevel(): void
  {
    if (! $this->levelTouched) {
      $this->level = Position::levelForRole($this->currentTier()->role());
    }
  }

  private function currentTier(): PositionTier
  {
    return PositionTier::tryFrom($this->tier) ?? PositionTier::LAINNYA;
  }

  /**
   * Jabatan baru, atau tingkatnya diganti saat edit — role & cakupan ikut tingkat.
   */
  private function tierChanged(): bool
  {
    return ! $this->editingId || $this->tier !== $this->originalTier;
  }

  public function save(): void
  {
    $user = auth()->user();
    abort_unless($user->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $isSuperAdmin = $this->isSuperAdmin();

    // Jabatan Tenaga Mitra yang diedit tetap bertingkat Tenaga Mitra (pilihannya tidak ditampilkan).
    if ($this->isTenagaMitraPosition() && ! $this->fixedRole) {
      $this->tier = PositionTier::TENAGA_MITRA->value;
    }

    // Lainnya & Tenaga Mitra hanya sah untuk jabatan yang sudah ada (tingkat tidak diganti)
    // atau tingkat terkunci dari pemanggil (wizard alur, mode Tenaga Mitra di form pegawai);
    // jabatan baru wajib memakai salah satu tingkat yang ditawarkan.
    $tierRule = Rule::enum(PositionTier::class);
    if (! $this->fixedRole && $this->tierChanged()) {
      $tierRule->only(PositionTier::selectable());
    }

    $rules = [
      'name' => ['required', 'string', 'max:255'],
      'tier' => ['required', $tierRule],
    ];
    if ($isSuperAdmin) {
      $rules['level'] = ['required', 'integer', 'min:0', 'max:9999'];
    }

    $this->validate($rules, [], [
      'name' => 'nama jabatan',
      'tier' => 'tingkat jabatan',
    ]);

    $position = $this->editingId ? Position::with('creator')->findOrFail($this->editingId) : null;
    if ($position) {
      abort_unless($position->isManageableBy($user), 403);
    }

    // Jabatan dipakai bersama semua OPD — cegah duplikasi nama (case-insensitive).
    $existing = Position::findByName($this->name);
    if ($existing && $existing->id !== $this->editingId) {
      $this->addError('name', "Jabatan \"{$existing->name}\" sudah terdaftar. Silakan pilih dari daftar jabatan.");
      return;
    }

    $tier = $this->currentTier();
    $data = ['name' => SmartTitle::convert(trim($this->name))];

    if ($this->fixedRole) {
      $data['uniqueness_scope'] = Position::scopeForRole($this->fixedRole)->value;
      $data['default_role']     = $this->fixedRole;
    } elseif ($this->roleChanged()) {
      $data['uniqueness_scope'] = $tier->scope()->value;
      $data['default_role']     = $this->selectedRole();
    }

    // Super Admin menyimpan level dari form (otomatis dari tingkat, bisa diubah);
    // Admin OPD selalu memakai level otomatis tingkat.
    if ($isSuperAdmin) {
      $data['level'] = $this->level;
    } elseif ($this->roleChanged()) {
      $data['level'] = Position::levelForRole($this->selectedRole());
    }

    if ($position) {
      $position->update($data);
      $created = false;
    } else {
      $data['created_by'] = $user->id;
      $position = Position::create($data);
      $created = true;
    }

    $this->show = false;
    $this->dispatch('position-saved', id: $position->id, created: $created);
    $this->toastSuccess($created
      ? "Jabatan \"{$position->name}\" berhasil ditambahkan."
      : "Jabatan \"{$position->name}\" berhasil diperbarui.");
  }

  public function render()
  {
    // Ringkasan role & cakupan yang akan berlaku: nilai tersimpan bila tingkat
    // tidak diganti saat edit, selain itu turunan dari tingkat terpilih.
    $tier = $this->currentTier();
    $roleName = $this->selectedRole();
    $scope = match (true) {
      $this->fixedRole !== null => Position::scopeForRole($this->fixedRole),
      $this->tierChanged()      => $tier->scope(),
      default                   => PositionScope::tryFrom($this->storedScope ?? '') ?? PositionScope::NONE,
    };
    // Role yang benar-benar diterima pegawai di unitnya: Pimpinan di Puskesmas = Kepala Puskesmas, dst.
    $unitType = $this->summaryUnitType();
    $unitRole = $roleName ? (new Position(['name' => $this->name, 'default_role' => $roleName]))->roleInUnitType($unitType) : null;
    $roleLabels = $this->show ? Role::pluck('label', 'name') : collect();
    $summaryRole = $this->show && $unitRole ? ($roleLabels[$unitRole] ?? $unitRole) : null;

    // Variasi role umum per tipe unit: "Camat di Kecamatan, Lurah di Kelurahan dan Kepala Puskesmas di Puskesmas".
    $variantsOf = fn (string $generic) => collect(DepartmentType::specificRolesFor($generic))
      ->map(fn (string $specific) => ($roleLabels[$specific] ?? $specific) . ' di ' . DepartmentType::forSpecificRole($specific)->label())
      ->join(', ', ' dan ');

    // Unit belum diketahui (mis. Super Admin di Data Jabatan): sebutkan role khusus per tipe unit.
    $roleVariants = $this->show && $roleName && ! $unitType && $unitRole === $roleName ? $variantsOf($roleName) : '';

    // Pilihan role khusus untuk tingkat Pimpinan/Sekretaris: role umum (menyesuaikan unit) atau khusus satu tipe unit.
    // Hanya di halaman Data Jabatan; dari form pegawai role khusus otomatis mengikuti unit kerja.
    $choices = $this->show && ! $this->isTenagaMitraPosition() && ! $this->departmentId ? $this->unitRoleChoices() : [];
    $generic = $this->currentTier()->role();
    $unitRoleOptions = $choices ? [
      ['value' => '', 'label' => $roleLabels[$generic] ?? $generic, 'description' => 'Menyesuaikan unit kerja: ' . $variantsOf($generic) . '.'],
      ...array_map(fn (string $specific) => [
        'value'       => $specific,
        'label'       => $roleLabels[$specific] ?? $specific,
        'description' => 'Khusus unit ' . DepartmentType::forSpecificRole($specific)->label() . '.',
      ], $choices),
    ] : [];

    // Tingkat terkunci: pakai label tingkat bila role-nya punya tingkat (mis. Pimpinan OPD),
    // selain itu label role-nya (mis. Sekda).
    $fixedTier = $this->fixedRole ? PositionTier::fromRole($this->fixedRole) : null;

    return view('livewire.position-form-modal', [
      'isTenagaMitra' => $this->isTenagaMitraPosition(),
      'tiers'        => PositionTier::selectable(),
      'summaryRole'  => $summaryRole,
      'roleVariants' => $roleVariants,
      'unitRoleOptions' => $unitRoleOptions,
      'summaryScope' => $scope,
      'fixedTier'    => $fixedTier && $fixedTier->role() === $this->fixedRole
        ? ['label' => $fixedTier->label(), 'description' => $fixedTier->description()]
        : ($this->fixedRole ? ['label' => $summaryRole, 'description' => null] : null),
    ]);
  }
}
