<div class="flex items-center space-x-2">
    <!-- Estado IGV -->
    <span class="text-xs text-gray-600">
        IGV: {{ ($posconfig->apply_tax ?? false) ? number_format(($posconfig->tax_rate ?? 0) * 100, 0) . '%' : 'No aplicado' }}
        · {{ ($posconfig->prices_include_tax ?? false) ? 'Precios c/IGV' : 'Precios s/IGV' }}
    </span>

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
