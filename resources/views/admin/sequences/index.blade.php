<x-admin-layout title="Secuencias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Secuencias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.sequences.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.sequences.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.sequence-table')

</x-admin-layout>
