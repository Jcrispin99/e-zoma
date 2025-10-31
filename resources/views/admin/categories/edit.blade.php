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

    <x-wire-card>

        <form action="{{ route('admin.categories.update', $category) }}" method="post" class="space-y-4">

            @csrf
            @method('put')
            <x-wire-input label="Nombre" name="name" placeholder="Nombre de la categoría" value="{{ old('name', $category->name) }}" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción de la categoría">{{ old('description', $category->description) }}</x-wire-textarea>


            <x-wire-select label="Categoría padre" placeholder="Seleccione una opción" :options="$categories" option-label="name" option-value="id" name="parent_id" value="{{ $category->parent_id }}" :clearable="true" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
