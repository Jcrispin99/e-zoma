<x-admin-layout title="Compañias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Compañias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.companies.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>
        <form action="{{ route('admin.companies.store') }}" method="POST" class="space-y-8" enctype="multipart/form-data">
            @csrf

            {{-- Sección de Información General --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información General</h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
                    <div class="lg:col-span-1" x-data="{ logoPreviewUrl: '{{ asset('storage/images/images.png') }}' }">
                        <label for="logo" class="block">
                            <img x-bind:src="logoPreviewUrl" src="{{ asset('storage/images/images.png') }}" alt="Logo por defecto" class="h-40 w-full object-contain rounded-md cursor-pointer" />
                        </label>
                        <input id="logo" name="logo" type="file" accept="image/*" class="sr-only" @change="
                                   const file = $event.target.files[0];
                                   if (!file) return;
                                   const reader = new FileReader();
                                   reader.onload = e => { logoPreviewUrl = e.target.result };
                                   reader.readAsDataURL(file);
                               " />
                        @error('logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <x-wire-input label="Nombre o Razón Social" name="name" required placeholder="Ej: Mi Empresa S.A.C." value="{{ old('name') }}" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-wire-input label="Nombre Comercial" name="trade_name" placeholder="Ej: Mi Empresa" value="{{ old('trade_name') }}" />
                            <x-wire-select label="Compañía Padre (Sucursal de)" name="parent_id" :options="$parentCompanies" option-label="name" option-value="id" placeholder="Opcional: si es una sucursal" :value="old('parent_id')" clearable />
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Sección de Información Fiscal y Legal --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información Fiscal y Legal</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <x-wire-select label="Tipo de documento" name="identity_id" :options="$identities" option-label="name" option-value="id" placeholder="Seleccione un tipo" :value="old('identity_id')" required />
                    <div class="md:col-span-2">
                        <x-wire-input label="Número de documento" name="document_number" required placeholder="Ingrese el número de documento" value="{{ old('document_number') }}" />
                    </div>
                    <x-wire-input label="Representante Legal" name="legal_representative" placeholder="Nombre completo del representante" value="{{ old('legal_representative') }}" />
                    <div class="md:col-span-2">
                        <x-wire-input label="Dirección Fiscal" name="tax_address" placeholder="La dirección registrada en la entidad tributaria" value="{{ old('tax_address') }}" />
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Sección de Políticas y Slogan --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Políticas y Slogan</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div class="md:col-span-1">
                        <x-wire-input label="Slogan" name="slogan" placeholder="Ej: Calidad y servicio" value="{{ old('slogan') }}" />
                    </div>
                    <div class="md:col-span-2">
                        <x-wire-textarea label="Políticas" name="policies" placeholder="Escribe las políticas de la empresa">{{ old('policies') }}</x-wire-textarea>
                    </div>
                </div>
            </div>
            {{-- Sección de Contacto y Estado --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información de Contacto y Estado</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                    <x-wire-input label="Correo electrónico" name="email" type="email" placeholder="correo@ejemplo.com" value="{{ old('email') }}" />
                    <x-wire-input label="Teléfono" name="phone" placeholder="987654321" value="{{ old('phone') }}" />
                    <x-wire-input label="Dirección Comercial" name="address" placeholder="Dirección de la oficina o tienda" value="{{ old('address') }}" />
                </div>
                <livewire:admin.company.company-create />
                <div class="mt-6">
                    <x-wire-checkbox id="is_active" name="is_active" label="Compañía Activa" value="1" :checked="old('is_active', true)" />
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <x-wire-button type="submit" green>
                    Guardar
                </x-wire-button>
            </div>
        </form>
    </x-wire-card>

    

</x-admin-layout>
