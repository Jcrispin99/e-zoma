<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <x-container class="py-6">
        <x-wire-card>
            <form action="{{ route('tenants.store') }}" method="POST" class="space-y-4">
                @csrf
                <x-wire-input wire:model="id" label="{{ __('Nombre') }}"
                    placeholder="{{ __('Ingrese el nombre del cliente') }}" />

                {{--
                <x-wire-input wire:model="email" type="email" label="{{ __('Email') }}"
                    placeholder="{{ __('Ingrese el email del cliente') }}" />

                <x-wire-input wire:model="password" type="password" label="{{ __('Contraseña') }}"
                    placeholder="{{ __('Ingrese la contraseña del cliente') }}" /> --}}

                <x-wire-button type="submit" primary spinner="createTenant">Guardar</x-wire-button>
            </form>
        </x-wire-card>
    </x-container>
</x-app-layout>