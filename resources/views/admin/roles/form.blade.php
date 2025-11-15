<x-admin-layout title="Roles" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Roles',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.roles.index'),
    ],
    [
        'name' => isset($role) ? 'Editar' : 'Nuevo',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.role-form :roleId="isset($role) ? $role->id : null" />
    </x-wire-card>

</x-admin-layout>