<div class="flex items-center space-x-2">
    <!-- Abrir/Continuar caja -->
    <x-wire-button xs primary wire:click="openSession({{ $posconfig->id }})">
        {{ ($hasOpen ?? false) ? 'Continuar venta' : 'Abrir caja' }}
    </x-wire-button>

    <x-wire-button href="{{ route('admin.posconfig.edit', $posconfig->id) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('admin.posconfig.destroy', $posconfig->id) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>
