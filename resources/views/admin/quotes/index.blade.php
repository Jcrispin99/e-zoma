<x-admin-layout title="Cotizaciones" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Cotizaciones',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.quotes.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.quotes.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.quote-table')

</x-admin-layout>
