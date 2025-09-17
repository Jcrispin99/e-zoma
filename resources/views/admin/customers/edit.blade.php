<x-admin-layout title="Clientes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Clientes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.customers.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.customers.update', $customer) }}" method="post" class="space-y-6">
            @csrf
            @method('put')
            <x-validation-errors class="mb-2" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-native-select label="Tipo de documento" name="identity_id" required>
                        <option value="" disabled @selected(!old('identity_id', $customer->identity_id))>Seleccione una opción</option>
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}" @selected(old('identity_id', $customer->identity_id) == $identity->id)>
                                {{ $identity->name }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>
                <div>
                    <x-wire-input label="Número de documento" name="document_number" placeholder="Ej: 12345678"
                        value="{{ old('document_number', $customer->document_number) }}" autocomplete="off" required />
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Nombre" name="name" placeholder="Nombre completo"
                        value="{{ old('name', $customer->name) }}" autocomplete="name" autofocus required />
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Dirección" name="address" placeholder="Dirección"
                        value="{{ old('address', $customer->address) }}" autocomplete="name" autofocus required />
                </div>

            </div>
            <div>
                <x-wire-input type="email" label="Correo" name="email" placeholder="correo@ejemplo.com"
                    value="{{ old('email', $customer->email) }}" autocomplete="email" required />
            </div>
            <div>
                <x-wire-input type="tel" label="Teléfono" name="phone" placeholder="Ej: +51 999 999 999"
                    value="{{ old('phone', $customer->phone) }}" autocomplete="tel" required />
            </div>
            </div>
            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Actualizar" />
            </div>
        </form>

    </x-wire-card>

</x-admin-layout>
