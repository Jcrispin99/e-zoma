<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Cliente') }}
        </h2>
    </x-slot>

    <x-container class="py-6">
        <x-wire-card>
            <form action="{{ route('tenants.update', $tenant) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <x-wire-input name="id" label="{{ __('Nombre') }}"
                    placeholder="{{ __('Ingrese el nombre del cliente') }}" value="{{ old('id', $tenant->id) }}" />

                <div class="flex justify-end space-x-2">
                    <x-wire-button href="{{ route('tenants.index') }}" flat>Cancelar</x-wire-button>
                    <x-wire-button type="submit" primary>Actualizar Cliente</x-wire-button>
                </div>
            </form>
        </x-wire-card>
    </x-container>
</x-app-layout>