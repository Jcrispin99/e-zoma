<x-wire-modal-card wire:model="form.open" width="lg">
        <x-slot name="title">
            <p class="text-xl text-center mb-2">Enviar email</p>
            <p class="text-lg text-center uppercase font-bold mb-2">{{ $form['document'] }}</p>
            <p class="text-lg text-center mb-2">{{ $form['client'] }}</p>
        </x-slot>

        <form wire:submit="sendEmail">
            <x-wire-input label="Correo" wire:model="form.email" class="mb-4" value="{{ $form['email'] }}" />
            <x-wire-button type="submit" class="w-full">
                Enviar
            </x-wire-button>
        </form>
</x-wire-modal-card>