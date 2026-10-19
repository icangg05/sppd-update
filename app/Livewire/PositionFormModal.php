<?php

namespace App\Livewire;

use App\Enums\PositionScope;
use App\Enums\PositionTier;
use App\Helpers\SmartTitle;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Modal tambah / edit jabatan — dipakai bersama oleh halaman Data Jabatan dan
 * formulir pegawai. Super Admin mengisi form lengkap (level, cakupan, role
 * default); Admin OPD cukup memilih Tingkat Jabatan dalam bahasa awam.
 *
 * Dibuka lewat event `open-position-form` dan mengirim `position-saved`
 * setelah berhasil disimpan.
 */
class PositionFormModal extends Component
{
  use InteractsWithToast;

  // Role kewenangan yang tidak terkait jabatan, jadi tak boleh jadi role default.
  public const EXCLUDED_ROLES = ['super_admin', 'admin_opd', 'pimpinan_dprd', 'anggota_dprd'];

  // Role tingkat Pemkot yang pemangkunya hanya satu di seluruh sistem.
  private const SYSTEM_ROLES = ['walikota', 'wakil_walikota', 'sekda', 'asisten'];

  public bool $show = false;
  public ?int $editingId = null;

  public string $name = '';
  public int $level = 1000;
  public string $scope = PositionScope::NONE->value;
  public string $defaultRole = '';
  public string $tier = PositionTier::STAF->value;

  // Tingkat saat modal edit dibuka — bila tidak diubah, role & cakupan lama dipertahankan.
  public ?string $originalTier = null;

  // Tebakan dari nama berhenti setelah pengguna memilih role/tingkat/cakupan sendiri.
  public bool $roleTouched = false;

  #[On('open-position-form')]
  public function open(?int $id = null, string $name = ''): void
  {
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $this->reset(['editingId', 'name', 'level', 'scope', 'defaultRole', 'tier', 'originalTier', 'roleTouched']);
    $this->resetValidation();

    if ($id) {
      $position = Position::with('creator')->findOrFail($id);
      abort_unless($position->isManageableBy(auth()->user()), 403);

      $this->editingId    = $position->id;
      $this->name         = $position->name;
      $this->level        = $position->level;
      $this->scope        = ($position->uniqueness_scope ?? PositionScope::NONE)->value;
      $this->defaultRole  = $position->default_role ?? '';
      $this->tier         = PositionTier::fromRole($position->default_role)->value;
      $this->originalTier = $this->tier;
      $this->roleTouched  = true;
    } else {
      $this->name = trim($name);
      $this->guessFromName();
    }

    $this->show = true;
  }

  public function isSuperAdmin(): bool
  {
    return auth()->user()->hasRole('super_admin');
  }

  public function updatedName(): void
  {
    if (! $this->editingId && ! $this->roleTouched) {
      $this->guessFromName();
    }
  }

  public function updatedTier(): void
  {
    $this->roleTouched = true;
  }

  public function updatedDefaultRole(): void
  {
    $this->roleTouched = true;
  }

  public function updatedScope(): void
  {
    $this->roleTouched = true;
  }

  /**
   * Isi tingkat (Admin OPD) atau role default + cakupan (Super Admin) dari nama.
   */
  private function guessFromName(): void
  {
    if (trim($this->name) === '') {
      return;
    }

    $role = Position::guessDefaultRole($this->name);

    $this->tier        = PositionTier::fromRole($role)->value;
    $this->defaultRole = $role ?? '';
    $this->scope       = match (true) {
      in_array($role, self::SYSTEM_ROLES, true) => PositionScope::SYSTEM->value,
      $role === null || $role === 'staf'        => PositionScope::NONE->value,
      default                                   => PositionScope::DEPARTMENT->value,
    };
  }

  public function save(): void
  {
    $user = auth()->user();
    abort_unless($user->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $isSuperAdmin = $this->isSuperAdmin();

    $rules = ['name' => ['required', 'string', 'max:255']];
    if ($isSuperAdmin) {
      $rules['level']       = ['required', 'integer', 'min:0', 'max:9999'];
      $rules['scope']       = ['required', Rule::enum(PositionScope::class)];
      $rules['defaultRole'] = ['nullable', 'string', Rule::exists('roles', 'name'), Rule::notIn(self::EXCLUDED_ROLES)];
    } else {
      $rules['tier'] = ['required', Rule::enum(PositionTier::class)];
    }

    $this->validate($rules, [], [
      'name'        => 'nama jabatan',
      'scope'       => 'cakupan keunikan',
      'defaultRole' => 'role default',
      'tier'        => 'tingkat jabatan',
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

    $data = ['name' => SmartTitle::convert(trim($this->name))];

    if ($isSuperAdmin) {
      $data['level']            = $this->level;
      $data['uniqueness_scope'] = $this->scope;
      $data['default_role']     = $this->defaultRole !== '' ? $this->defaultRole : null;
    } elseif (! $position || $this->tier !== $this->originalTier) {
      // Admin OPD: role & cakupan diturunkan dari tingkat. Saat edit tanpa
      // mengganti tingkat, pengaturan yang mungkin sudah disesuaikan Super Admin dipertahankan.
      $tier = PositionTier::from($this->tier);
      $data['uniqueness_scope'] = $tier->scope()->value;
      $data['default_role']     = $tier->role();
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
    $roles = $this->show
      ? Role::whereNotIn('name', self::EXCLUDED_ROLES)->orderBy('label')->get(['name', 'label'])
      : collect();

    $tier = PositionTier::tryFrom($this->tier) ?? PositionTier::LAINNYA;

    return view('livewire.position-form-modal', [
      'roles'         => $roles,
      'scopes'        => PositionScope::cases(),
      'tiers'         => PositionTier::cases(),
      'tierRoleLabel' => $tier->role() ? $roles->firstWhere('name', $tier->role())?->label : null,
      'tierScope'     => $tier->scope(),
    ]);
  }
}
