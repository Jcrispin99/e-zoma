<x-admin-layout title="CAtegorias" :breadcrumbs="[
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
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.categories.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" />
            <x-wire-textarea label="Descripción" name="description" />

            <x-wire-native-select label="Categoría" name="category_id">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                        {{ $category->full_name }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
