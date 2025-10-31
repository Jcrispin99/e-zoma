<x-admin-layout title="Proveedores" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Proveedores',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.suppliers.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.suppliers.update', $supplier) }}" method="post" class="space-y-6">
            @csrf
            @method('put')
            <x-validation-errors class="mb-2" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-native-select label="Tipo de documento" name="identity_id" required>
                        <option value="" disabled @selected(!old('identity_id', $supplier->identity_id))>Seleccione una opción</option>
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}" @selected(old('identity_id', $supplier->identity_id) == $identity->id)>
                                {{ $identity->name }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>
                <div>
                    <x-wire-input label="Número de documento" name="document_number" placeholder="Ej: 12345678"
                        value="{{ old('document_number', $supplier->document_number) }}" autocomplete="off" required />
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Nombre del proveedor" name="name" placeholder="Nombre completo"
                        value="{{ old('name', $supplier->name) }}" autocomplete="name" autofocus required />
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Dirección del proveedor" name="address" placeholder="Dirección"
                        value="{{ old('address', $supplier->address) }}" autocomplete="name" autofocus required />
                </div>

            </div>
            <div>
                <x-wire-input type="email" label="Correo del proveedor" name="email"
                    placeholder="correo@ejemplo.com" value="{{ old('email', $supplier->email) }}" autocomplete="email"
                    required />
            </div>
            <div>
                <x-wire-input type="tel" label="Teléfono del proveedor" name="phone"
                    placeholder="Ej: +51 999 999 999" value="{{ old('phone', $supplier->phone) }}" autocomplete="tel"
                    required />
            </div>
            </div>
            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Actualizar" />
            </div>
        </form>

    </x-wire-card>

</x-admin-layout>
