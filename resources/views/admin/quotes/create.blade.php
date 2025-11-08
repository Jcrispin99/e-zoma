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
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.quotes.quote-form', ['mode' => 'create'])

</x-admin-layout>
