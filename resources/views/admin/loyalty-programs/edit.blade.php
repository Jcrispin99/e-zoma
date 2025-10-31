<x-admin-layout title="Categorias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Categorias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.categories.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <div class="container">
        @livewire('admin.loyalty.loyalty-program-edit', ['program' => $program])

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                @livewire('admin.loyalty.earn-rule-table', ['program' => $program])
            </div>
            <div>
                @livewire('admin.loyalty.reward-table', ['program' => $program])
            </div>
        </div>
    </div>

</x-admin-layout>
