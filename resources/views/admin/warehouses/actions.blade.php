<div class="flex items-center space-x-2">
    @can('update_warehouses', $warehouse)
    <x-wire-button href="{{ route('admin.warehouses.edit', $warehouse->id) }}" wire:navigate blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_warehouses', $warehouse)
    <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>