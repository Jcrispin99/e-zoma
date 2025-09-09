<div class="flex items-center space-x-2"> 
    <x-wire-button href="{{ route('admin.categories.edit', $category->id) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('admin.categories.destroy', $category->id) }}" method="post">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>