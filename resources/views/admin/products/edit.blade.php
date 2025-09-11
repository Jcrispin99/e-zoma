<x-admin-layout title="Productos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Productos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.products.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.products.update', $product) }}" method="post" class="space-y-4">

            @csrf
            @method('put')

            <x-wire-input label="Nombre" name="name" placeholder="Nombre del producto"
                value="{{ old('name', $product->name) }}" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción del producto">
                {{ old('description', $product->description) }}
            </x-wire-textarea>

            <x-wire-input type="number" label="Precio" name="price" placeholder="Precio del producto"
                value="{{ old('price', $product->price) }}" />

            <x-wire-native-select label="Categoría" name="category_id">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                        {{ $category->full_name }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
