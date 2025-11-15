<div class="flex items-center space-x-2">
    @can('update_roles', $role)
    <x-wire-button href="{{ route('admin.roles.edit', $role->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_roles', $role)
    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>