<div class="flex items-center space-x-2">
    @can('update_sequences', $sequence)
    <x-wire-button href="{{ route('admin.sequences.edit', $sequence->id) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('delete_sequences', $sequence)
    <form action="{{ route('admin.sequences.destroy', $sequence->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    @endcan
</div>
