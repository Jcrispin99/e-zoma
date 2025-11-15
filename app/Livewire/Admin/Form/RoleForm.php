<?php

namespace App\Livewire\Admin\Form;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleForm extends Component
{
    public ?int $roleId = null;
    public bool $isEditing = false;
    public bool $redirectAfterSave = true;

    public string $name = '';
    public array $permissionOptions = [];
    public array $selectedPermissions = [];

    public function mount(?int $roleId = null): void
    {
        $this->roleId = $roleId;
        $this->isEditing = (bool) $roleId;

        $this->permissionOptions = Permission::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => ['id' => (int) $p->id, 'name' => (string) $p->name])
            ->toArray();

        if ($this->isEditing) {
            $role = Role::findOrFail($roleId);
            $this->name = (string) $role->name;
            $this->selectedPermissions = $role->permissions->pluck('id')->map(fn($id) => (int) $id)->toArray();
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->roleId)],
            'selectedPermissions' => ['array'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->isEditing) {
            $role = Role::findOrFail($this->roleId);
            $role->update(['name' => $data['name']]);
        } else {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        }

        $role->syncPermissions($this->selectedPermissions);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Rol ' . $role->name . ($this->isEditing ? ' ha sido actualizado' : ' ha sido creado'),
        ]);

        if ($this->redirectAfterSave) {
            return redirect()->route('admin.roles.edit', $role);
        }

        $this->dispatch('role:saved', $role->id);
    }

    public function render()
    {
        return view('livewire.admin.form.role-form');
    }
}