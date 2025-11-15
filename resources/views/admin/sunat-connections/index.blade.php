<x-admin-layout title="Sunat" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Sunat',
        'icon' => 'fa-solid fa-cloud',
        'href' => route('admin.sunat-connections.index'),
    ],
]">
    <x-wire-card>
        @can($connection ? 'update_sunat-connections' : 'create_sunat-connections')
        <form action="{{ route('admin.sunat-connections.' . ($connection ? 'update' : 'store')) }}" method="POST" class="space-y-8">
            @csrf
            @if($connection)
            @method('PUT')
            @endif

            <div>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    {{ $connection ? 'Editar Conexión SUNAT' : 'Configurar Conexión SUNAT' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Guarda aquí tus tokens de ApiPeru e Ikoodev para las conexiones con Sunat.</p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                    <x-wire-input label="Token ApiPeru" name="token_apiperu" type="text" placeholder="Pega tu token de ApiPeru" value="{{ old('token_apiperu', $connection->token_apiperu ?? '') }}" />
                    <x-wire-input label="Token Ikoodev" name="token_ikoodev" type="text" placeholder="Pega tu token de Ikoodev" value="{{ old('token_ikoodev', $connection->token_ikoodev ?? '') }}" />
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <x-wire-button type="submit" green>
                    Guardar
                </x-wire-button>
            </div>
        </form>
        @else
            <div class="p-4 text-sm text-gray-600 dark:text-gray-300">No tienes permisos para configurar las conexiones SUNAT.</div>
        @endcan
    </x-wire-card>
</x-admin-layout>
