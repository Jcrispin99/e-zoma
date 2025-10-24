<div class="flex items-center space-x-2">
    <x-wire-button href="{{ route('admin.loyalty-programs.edit', $program->id) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('admin.loyalty-programs.destroy', $program->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>
