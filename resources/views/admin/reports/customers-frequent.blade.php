<x-admin-layout
    title="Reportes"
    :breadcrumbs="[
        ['name' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'fa-solid fa-gauge'],
        ['name' => 'Reportes', 'href' => route('admin.reports.customers.frequent'), 'icon' => 'fa-solid fa-chart-line'],
        ['name' => 'Clientes: Frecuentes', 'href' => '#', 'icon' => 'fa-solid fa-user-clock'],
    ]"
>
    <div>
        <h1 class="text-3xl font-bold">Clientes: Frecuentes</h1>
        <div class="mt-6">
            @livewire('admin.reports.frequent-customers')
        </div>
    </div>
</x-admin-layout>