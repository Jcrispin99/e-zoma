<div class="flex items-center space-x-2">
    @can('update_users', $user)
    <x-wire-button href="{{ route('admin.users.edit', $user->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_users', $user)
    <form action="{{ route('admin.users.destroy', $user->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>
