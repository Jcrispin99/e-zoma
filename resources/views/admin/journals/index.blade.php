<x-admin-layout title="Diarios" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Diarios',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.journals.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.journals.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.journal-table')

</x-admin-layout>
