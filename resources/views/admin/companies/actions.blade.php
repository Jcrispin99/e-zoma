<div class="flex items-center space-x-2">
    <x-wire-button href="{{ route('admin.companies.edit', $company->id) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('admin.companies.destroy', $company->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>
