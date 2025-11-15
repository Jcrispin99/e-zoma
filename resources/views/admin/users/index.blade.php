<x-admin-layout title="Usuarios" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Usuarios',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.users.index'),
    ],
    [
        'name' => 'Usuarios',
    ],
]">
    @can('create_users')
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.users.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @endcan
   
    @livewire('admin.datatables.user-table')


</x-admin-layout>
