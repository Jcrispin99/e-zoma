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
]">
    <x-slot name="action">
        @can('create_roles')
        <x-wire-button href="{{ route('admin.roles.create') }}" green>
            Nuevo
        </x-wire-button>
        @endcan
    </x-slot>
    @livewire('admin.datatables.role-table')

</x-admin-layout>