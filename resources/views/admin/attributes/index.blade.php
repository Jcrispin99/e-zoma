<x-admin-layout title="Atributos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Atributos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.attributes.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.attributes.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.attribute-table')

</x-admin-layout>
