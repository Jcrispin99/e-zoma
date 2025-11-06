<x-admin-layout
    title="Reportes"
    :breadcrumbs="[
        ['name' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'fa-solid fa-gauge'],
        ['name' => 'Reportes', 'href' => route('admin.reports.sunat.status'), 'icon' => 'fa-solid fa-chart-line'],
        ['name' => 'SUNAT: Estados', 'href' => '#', 'icon' => 'fa-solid fa-file-invoice'],
    ]"
>
    <div>
        <h1 class="text-3xl font-bold">SUNAT: Estados</h1>
        <div class="mt-6">
            <x-wire-card>
                <p class="text-gray-600">Este reporte está en construcción. Aquí mostraremos el estado de los comprobantes electrónicos y posibles errores/reintentos.</p>
            </x-wire-card>
        </div>
    </div>
</x-admin-layout>