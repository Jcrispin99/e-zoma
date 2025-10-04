<x-admin-layout title="Configuracion" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Compañias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.companies.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.companies.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.company-table')

</x-admin-layout>
