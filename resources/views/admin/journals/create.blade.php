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
            </x-wire-native-select>

            <x-wire-checkbox id="is_fiscal" name="is_fiscal" label="Documento fiscal" value="1" :checked="(bool) old('is_fiscal', false)" />

            <x-wire-native-select label="Tipo de documento SUNAT" name="document_type_code">
                <option value="" @selected(old('document_type_code')==null)>-- Selecciona --</option>
                <option value="01" @selected(old('document_type_code')=='01')>Factura (01)</option>
                <option value="03" @selected(old('document_type_code')=='03')>Boleta (03)</option>
                <option value="07" @selected(old('document_type_code')=='07')>Nota de Crédito (07)</option>
                <option value="08" @selected(old('document_type_code')=='08')>Nota de Débito (08)</option>
            </x-wire-native-select>

            <x-wire-native-select label="Compañía" name="company_id">
                @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id')==$company->id)>
                    {{ $company->name }}
                </option>
                @endforeach
            </x-wire-native-select>

            <div class="flex justify-end">
                <x-button type="submit">Crear</x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
