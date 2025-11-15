<x-admin-layout title="Programas de Lealtad" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Programas de Lealtad',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.loyalty-programs.index'),
    ],
]">
    <x-slot name="action">
        @can('create_loyalty-programs')
        <x-wire-button href="{{ route('admin.loyalty-programs.create') }}" green>
            Nuevo
        </x-wire-button>
        @endcan
    </x-slot>
    @livewire('admin.datatables.loyalty-program-table')

</x-admin-layout>