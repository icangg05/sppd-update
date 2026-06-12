<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm extends Component
{
    public ?Role $role = null;

    public bool $isEdit = false;

    public string $name = '';

    public string $label = '';

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
            'selectedPermissions'   => 'nullable|array',
            'selectedPermissions.*' => 'exists:permissions,name',
        ];
    }

    public function toggleAll(bool $check): void
    {
        if ($check) {
            $this->selectedPermissions = Permission::pluck('name')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $this->validate();

        if ($this->isEdit) {
            $this->role->update(['label' => $this->label]);
            $this->role->syncPermissions($this->selectedPermissions);

            return redirect()->route('master.roles.index')
                ->with('success', "Role \"{$this->role->label}\" berhasil diperbarui.");
        }

        $role = Role::create([
            'name'       => $this->name,
            'label'      => $this->label,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->selectedPermissions);

        return redirect()->route('master.roles.index')
            ->with('success', "Role \"{$role->label}\" berhasil ditambahkan.");
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.role-form', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }
}
