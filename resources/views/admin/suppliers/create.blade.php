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
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.suppliers.store') }}" method="post" class="space-y-6">
            @csrf
            <x-validation-errors class="mb-2" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-native-select label="Tipo de documento" name="identity_id" required>
                        <option value="" disabled @selected(!old('identity_id'))>Seleccione una opción</option>
                        @foreach ($identities as $identity)
                            <option value="{{ $identity->id }}" @selected(old('identity_id') == $identity->id)>
                                {{ $identity->name }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>
                <div>
                    <x-wire-input label="Número de documento" name="document_number" placeholder="Ej: 12345678"
                        value="{{ old('document_number') }}" autocomplete="off" required />
                    <div id="supplier-lookup-status" class="text-xs text-slate-500 mt-1"></div>
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Nombre del proveedor" name="name" placeholder="Nombre completo"
                        value="{{ old('name') }}" autocomplete="name" autofocus required />
                </div>
                <div class="md:col-span-2">
                    <x-wire-input label="Dirección del proveedor" name="address" placeholder="Dirección"
                        value="{{ old('address') }}" autocomplete="name" autofocus required />
                </div>
            </div>
            <div>
                <x-wire-input type="email" label="Correo del proveedor" name="email"
                    placeholder="correo@ejemplo.com" value="{{ old('email') }}" autocomplete="email" required />
            </div>
            <div>
                <x-wire-input type="tel" label="Teléfono del proveedor" name="phone"
                    placeholder="Ej: +51 999 999 999" value="{{ old('phone') }}" autocomplete="tel" required />
            </div>
            </div>
            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>
        </form>

    </x-wire-card>

    <script>
        (function () {
            const docInput = document.querySelector('input[name="document_number"]');
            if (!docInput) return;
            const statusEl = document.getElementById('supplier-lookup-status');
            const nameInput = document.querySelector('input[name="name"]');
            const addressInput = document.querySelector('input[name="address"]');
            const identitySelect = document.querySelector('select[name="identity_id"]');

            const getCookie = (name) => {
                const match = document.cookie.split('; ').find((row) => row.startsWith(name + '='));
                return match ? decodeURIComponent(match.split('=')[1]) : null;
            };

            async function consult() {
                const doc = docInput.value.trim();
                if (!doc) return;
                if (![8, 11].includes(doc.length)) {
                    if (statusEl) statusEl.textContent = 'Documento debe ser DNI (8) o RUC (11).';
                    return;
                }
                try {
                    if (statusEl) statusEl.textContent = 'Consultando APIS Perú...';
                    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
                    const xsrf = getCookie('XSRF-TOKEN');
                    const resp = await fetch('/api/customers/lookup', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-XSRF-TOKEN': xsrf ?? ''
                        },
                        body: JSON.stringify({ document_number: doc })
                    });
                    const data = await resp.json();
                    if (!resp.ok) {
                        throw new Error(data?.message || 'No se pudo consultar APIS Perú');
                    }
                    if (identitySelect && data.identity_id) identitySelect.value = data.identity_id;
                    if (nameInput && data.name) nameInput.value = data.name;
                    if (addressInput && data.address) addressInput.value = data.address;
                    if (statusEl) statusEl.textContent = 'Autocompletado desde APIS Perú';
                } catch (e) {
                    if (statusEl) statusEl.textContent = e.message || 'Error consultando APIS Perú';
                }
            }

            docInput.addEventListener('blur', consult);
        })();
    </script>

</x-admin-layout>
