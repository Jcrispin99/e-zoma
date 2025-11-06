<x-admin-layout
    title="Reportes"
    :breadcrumbs="[
        ['name' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'fa-solid fa-gauge'],
        ['name' => 'Reportes', 'href' => route('admin.reports.sales.top-products'), 'icon' => 'fa-solid fa-chart-line'],
        ['name' => 'Ventas: Top productos', 'href' => '#', 'icon' => 'fa-solid fa-ranking-star'],
    ]"
>
    <div>
        <h1 class="text-3xl font-bold">Ventas: Top productos</h1>
        <div class="mt-6">
            @livewire('admin.dashboard.sales-top-products')
        </div>
    </div>
</x-admin-layout>