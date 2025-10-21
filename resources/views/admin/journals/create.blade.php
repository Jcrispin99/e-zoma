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
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.journals.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" />
            <x-wire-input label="Código" name="code" />
            <x-wire-native-select label="Tipo" name="type">
                <option value="sale">Venta</option>
                <option value="purchase">Compra</option>
                <option value="cash">Efectivo</option>
                <option value="bank">Banco</option>
                <option value="general">General</option>
                <option value="purchase-order">Orden de Compra</option>

            </x-wire-native-select>

            <x-wire-native-select label="Compañía" name="company_id">
                @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id')==$company->id)>
                    {{ $company->name }}
                </option>
                @endforeach
            </x-wire-native-select>

            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
