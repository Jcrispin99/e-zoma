<x-admin-layout title="Dashboard" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Contenido',
        'href' => '#',
        'icon' => 'fa-regular fa-file-lines',
    ],
]">

    <div>
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <div class="mt-6 space-y-6">
            {{-- Resumen de ventas --}}
            @livewire('admin.dashboard.sales-summary')

            {{-- Cuadrícula para Top Productos y Top POS --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @livewire('admin.dashboard.sales-top-products')
                @livewire('admin.dashboard.top-selling-pos')
            </div>
        </div>
    </div>
</x-admin-layout>