<div class="flex items-center space-x-2">
    @can('update_attributes', $attribute)
    <x-wire-button href="{{ route('admin.attributes.edit', $attribute->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_attributes', $attribute)
    <form action="{{ route('admin.attributes.destroy', $attribute->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>