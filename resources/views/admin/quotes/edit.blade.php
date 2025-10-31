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
    [
        'name' => 'Editar',
    ],
]">
    <x-wire-card>
        @livewire('admin.quotes.quote-edit', ['quote' => $quote])
    </x-wire-card>
</x-admin-layout>
