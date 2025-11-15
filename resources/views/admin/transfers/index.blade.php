<x-admin-layout title="Transferencias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Transferencias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.transfers.index'),
    ],

]">
    @can('create_transfers')
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.transfers.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @endcan
    @livewire('admin.datatables.transfer-table')

</x-admin-layout>