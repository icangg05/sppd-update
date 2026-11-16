<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Support\BadgeColor;
use App\Support\PermissionCatalog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class RoleForm extends Component
{
    use InteractsWithToast;

    public ?Role $role = null;

    public bool $isEdit = false;

    public string $name = '';

    public string $label = '';

    public string $color = BadgeColor::DEFAULT;

    /** Catatan untuk pengguna, mis. sebutan role di unit kerja tertentu. */
    public string $note = '';

    /** @var array<string> */
    public array $selectedPermissions = [];

    public function mount(?Role $role = null): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        if ($role && $role->exists) {
            $this->role = $role;
            $this->isEdit = true;

            $this->name = $role->name;
            $this->label = $role->label ?? '';
            $this->color = $role->color ?? BadgeColor::DEFAULT;
            $this->note = $role->note ?? '';
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        $uniqueRule = $this->isEdit
            ? 'unique:roles,name,' . $this->role->id
            : 'unique:roles,name';

        return [
            'name'                  => $this->isEdit ? 'required|string|max:100' : "required|string|max:100|{$uniqueRule}",
            'label'                 => 'required|string|max:100',
            'color'                 => 'required|string|in:' . implode(',', BadgeColor::PALETTE),
            'note'                  => 'nullable|string|max:500',
            'selectedPermissions'   => 'nullable|array',
            'selectedPermissions.*' => 'exists:permissions,name',
        ];
    }

    /**
     * Pilih/hapus semua permission yang ditampilkan. Permission tersembunyi (di luar
     * PermissionCatalog) yang sudah dimiliki role tetap seperti semula.
     */
    public function toggleAll(bool $check): void
    {
        $visible = PermissionCatalog::names();
        $hidden = array_values(array_diff($this->selectedPermissions, $visible));

        $this->selectedPermissions = $check
            ? [...$hidden, ...Permission::whereIn('name', $visible)->pluck('name')->all()]
            : $hidden;
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $this->validate();

        if ($this->isEdit) {
            $this->role->update(['label' => $this->label, 'color' => $this->color, 'note' => trim($this->note) ?: null]);
            $this->role->syncPermissions($this->selectedPermissions);

            // Tetap di halaman ini agar hak akses bisa diatur lagi tanpa bolak-balik.
            $this->toastSuccess("Role \"{$this->role->label}\" berhasil diperbarui.");

            return null;
        }

        $role = Role::create([
            'name'       => $this->name,
            'label'      => $this->label,
            'color'      => $this->color,
            'note'       => trim($this->note) ?: null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->selectedPermissions);

        // Role baru dilanjutkan di halaman edit-nya (form yang sama), bukan kembali ke daftar,
        // supaya simpan berikutnya tidak membuat role ganda.
        return redirect()->route('master.roles.edit', $role)
            ->with('success', "Role \"{$role->label}\" berhasil ditambahkan.");
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.role-form', [
            'permissionGroups' => PermissionCatalog::grouped(Permission::pluck('name')),
        ])->title($this->isEdit ? 'Edit Role' : 'Tambah Role');
    }
}
