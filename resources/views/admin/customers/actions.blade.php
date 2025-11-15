<div class="flex items-center space-x-2">
    @can('update_customers', $customer)
    <x-wire-button href="{{ route('admin.customers.edit', $customer->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_customers', $customer)
    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>