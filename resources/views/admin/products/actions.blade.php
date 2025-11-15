<div class="flex items-center space-x-2">
    @can('update_products', $product)
    <x-wire-button href="{{ route('admin.products.edit', $product) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_products', $product)
    <form action="{{ route('admin.products.destroy', $product) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>