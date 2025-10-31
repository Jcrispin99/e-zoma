<x-admin-layout title="Secuencias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Secuencias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.sequences.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.sequences.update', $sequence) }}" method="post" class="space-y-4">

            @csrf
            @method('put')

            <x-wire-input label="Tamaño de la secuencia" name="sequence_size" placeholder="e.g. 5" type="number" min="1" value="{{ old('sequence_size', $sequence->sequence_size) }}" />
            <x-wire-input label="Paso" name="step" type="number" value="{{ old('step', $sequence->step) }}" />
            <x-wire-input label="Siguiente número" name="next_number" type="number" value="{{ old('next_number', $sequence->next_number) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
