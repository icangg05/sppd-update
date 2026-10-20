<?php

namespace App\Livewire;

use App\Enums\PositionScope;
use App\Enums\PositionTier;
use App\Helpers\SmartTitle;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Position;
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
 * untuk tahap tersebut (termasuk role khusus seperti Sekda/Walikota).
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

  // Tebakan tingkat dari nama berhenti setelah pengguna memilih tingkat sendiri.
  public bool $tierTouched = false;

  // Level mengikuti tingkat sampai Super Admin mengisinya sendiri.
  public bool $levelTouched = false;

  // Role tahap alur yang menjadi role default jabatan baru (tingkat tidak bisa diganti).
  #[Locked]
  public ?string $fixedRole = null;

  #[On('open-position-form')]
  public function open(?int $id = null, string $name = '', ?string $role = null): void
  {
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

    $this->reset(['editingId', 'name', 'tier', 'level', 'originalTier', 'storedRole', 'storedScope', 'tierTouched', 'levelTouched', 'fixedRole']);
    $this->resetValidation();

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
      $this->tierTouched  = true;
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
    if (! $this->editingId && ! $this->tierTouched) {
      $this->guessFromName();
    }
  }

  public function updatedTier(): void
  {
    if ($this->fixedRole) {
      $this->tier = PositionTier::fromRole($this->fixedRole)->value;

      return;
    }

    $this->tierTouched = true;
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

    $this->tier = PositionTier::guessFromName($this->name)->value;
    $this->applyTierLevel();
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

    $rules = [
      'name' => ['required', 'string', 'max:255'],
      'tier' => ['required', Rule::enum(PositionTier::class)],
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
    } elseif ($this->tierChanged()) {
      $data['uniqueness_scope'] = $tier->scope()->value;
      $data['default_role']     = $tier->role();
    }

    // Super Admin menyimpan level dari form (otomatis dari tingkat, bisa diubah);
    // Admin OPD selalu memakai level otomatis tingkat.
    if ($isSuperAdmin) {
      $data['level'] = $this->level;
    } elseif ($this->tierChanged()) {
      $data['level'] = Position::levelForRole($this->fixedRole ?? $tier->role());
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
    $roleName = $this->fixedRole ?? ($this->tierChanged() ? $tier->role() : $this->storedRole);
    $scope = match (true) {
      $this->fixedRole !== null => Position::scopeForRole($this->fixedRole),
      $this->tierChanged()      => $tier->scope(),
      default                   => PositionScope::tryFrom($this->storedScope ?? '') ?? PositionScope::NONE,
    };
    $summaryRole = $this->show && $roleName ? (Role::where('name', $roleName)->value('label') ?? $roleName) : null;

    // Tingkat terkunci: pakai label tingkat bila role-nya punya tingkat (mis. Pimpinan OPD),
    // selain itu label role-nya (mis. Sekda).
    $fixedTier = $this->fixedRole ? PositionTier::fromRole($this->fixedRole) : null;

    return view('livewire.position-form-modal', [
      'tiers'        => PositionTier::cases(),
      'summaryRole'  => $summaryRole,
      'summaryScope' => $scope,
      'fixedTier'    => $fixedTier && $fixedTier->role() === $this->fixedRole
        ? ['label' => $fixedTier->label(), 'description' => $fixedTier->description()]
        : ($this->fixedRole ? ['label' => $summaryRole, 'description' => null] : null),
    ]);
  }
}
