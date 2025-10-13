<x-admin-layout title="Diarios" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Diarios',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.journals.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.journals.update', $journal) }}" method="post" class="space-y-4">

            @csrf
            @method('put')
            <x-wire-input label="Nombre" name="name" value="{{ old('name', $journal->name) }}" />
            <x-wire-input label="Código" name="code" value="{{ old('code', $journal->code) }}" />
            <x-wire-native-select label="Tipo" name="type">
                <option value="sale" @selected($journal->type == 'sale')>Venta</option>
                <option value="purchase" @selected($journal->type == 'purchase')>Compra</option>
                <option value="cash" @selected($journal->type == 'cash')>Efectivo</option>
                <option value="bank" @selected($journal->type == 'bank')>Banco</option>
                <option value="general" @selected($journal->type == 'general')>General</option>
            </x-wire-native-select>

            <x-wire-native-select label="Compañía" name="company_id">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected($journal->company_id == $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <x-wire-native-select label="Secuencia" name="sequence_id">
                @foreach ($sequences as $sequence)
                    <option value="{{ $sequence->id }}" @selected($journal->sequence_id == $sequence->id)>
                        {{ $sequence->id }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
