<x-admin-layout title="Configuración de POS" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Configuración de POS',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.posconfig.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.posconfig.store') }}" method="post" class="space-y-4">

            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">


                <x-wire-input label="Nombre" name="name" value="{{ old('name') }}" />

                <x-wire-native-select label="Compañía" name="company_id">
                    <option value="">Seleccione una compañía</option>
                    @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id')==$company->id)>
                        {{ $company->name }}
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Almacén" name="warehouse_id">
                    <option value="">Seleccione un almacén</option>
                    @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id')==$warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Cliente por defecto" name="default_customer_id">
                    <option value="">Seleccione un cliente</option>
                    @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('default_customer_id')==$customer->id)>
                        {{ $customer->name }}
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Secuencia de Recibos" name="receipt_sequence_id">
                    <option value="">Seleccione una secuencia</option>
                    @foreach ($sequences as $sequence)
                    <option value="{{ $sequence->id }}" @selected(old('receipt_sequence_id')==$sequence->id)>
                        {{ $sequence->name }} ({{ $sequence->prefix }})
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Secuencia de Facturas" name="invoice_sequence_id">
                    <option value="">Seleccione una secuencia</option>
                    @foreach ($sequences as $sequence)
                    <option value="{{ $sequence->id }}" @selected(old('invoice_sequence_id')==$sequence->id)>
                        {{ $sequence->name }} ({{ $sequence->prefix }})
                    </option>
                    @endforeach
                </x-wire-native-select>

                <div class="flex items-center col-span-1 md:col-span-2">
                    <x-wire-toggle label="Activo" name="is_active" value="1" :checked="old('is_active', true)" />
                </div>
            </div>


            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
