<x-admin-layout
    title="Reportes"
    :breadcrumbs="[
        ['name' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'fa-solid fa-gauge'],
        ['name' => 'Reportes', 'href' => route('admin.reports.sales.payment-methods'), 'icon' => 'fa-solid fa-chart-line'],
        ['name' => 'Ventas: Métodos de pago', 'href' => '#', 'icon' => 'fa-solid fa-money-bill-wave'],
    ]"
>
    <div>
        <h1 class="text-3xl font-bold">Ventas: Métodos de pago</h1>
        <div class="mt-6">
            @livewire('admin.reports.sales-by-payment-method')
        </div>
    </div>
</x-admin-layout>