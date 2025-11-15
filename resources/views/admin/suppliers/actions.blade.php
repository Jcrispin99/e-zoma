<div class="flex items-center space-x-2">
    @can('update_suppliers', $supplier)
    <x-wire-button href="{{ route('admin.suppliers.edit', $supplier->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_suppliers', $supplier)
    <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>
