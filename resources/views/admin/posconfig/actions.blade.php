<div class="flex items-center space-x-2" x-data>


    <!-- Abrir/Continuar caja -->
    <x-wire-button xs primary wire:click="openSession({{ $posconfig->id }})">
        {{ ($hasOpen ?? false) ? 'Continuar venta' : 'Abrir caja' }}
    </x-wire-button>

    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button type="button"
                class="inline-flex items-center justify-center px-2 py-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 focus:outline-none">
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('admin.posconfig.edit', $posconfig->id)">Editar</x-dropdown-link>
            <x-dropdown-link :href="route('admin.posconfig.sessions', $posconfig->id)">Sesiones</x-dropdown-link>

            <form action="{{ route('admin.posconfig.destroy', $posconfig->id) }}" method="post" x-data>
                @csrf
                @method('delete')
                <x-dropdown-link href="{{ route('admin.posconfig.destroy', $posconfig->id) }}"
                    @click.prevent="$root.submit()">Eliminar</x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</div>