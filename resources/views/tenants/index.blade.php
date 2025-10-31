<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <x-container class="py-6">
        <div class="flex justify-end">
            <x-wire-button href="{{ route('tenants.create') }}" primary>
                Nuevo Cliente
            </x-wire-button>
        </div>

        <x-wire-card class="mt-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">ID</th>
                            <th scope="col" class="px-6 py-3">Dominio</th>
                            <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $tenant->id }}</td>
                            <td class="px-6 py-4">
                                @if ($domain = $tenant->domains->first())
                                {{ $domain->domain }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center space-x-4">
                                    <a href="http://{{ $domain->domain ?? '' }}" target="_blank"
                                        class="font-medium text-blue-600 hover:underline">Visitar</a>
                                    <a href="{{ route('tenants.edit', $tenant) }}"
                                        class="font-medium text-indigo-600 hover:underline">Editar</a>
                                    <form action="{{ route('tenants.destroy', $tenant) }}" method="POST"
                                        onsubmit="return confirm('¿Estás seguro de que quieres eliminar este cliente? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="font-medium text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center">No hay clientes registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-wire-card>
    </x-container>

</x-app-layout>