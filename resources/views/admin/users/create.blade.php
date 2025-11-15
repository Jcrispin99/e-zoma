<x-admin-layout title="Usuarios" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Usuarios',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.users.index'),
    ],
    [
        'name' => 'Nuevo Usuario',
    ],
]">

    <x-wire-card>
        <h1 class="text-2xl font-semibold mb-4">
            Nuevo Usuario
        </h1>
        <x-validation-errors class="mb-2" />
    </x-wire-card>

    <form action=" {{ route('admin.users.store') }}" method="POST">

        @csrf

        <div class="grid grid-cols-2 gap-4">
            <x-wire-input label="Nombre" name="name" required placeholder="Ingrese el nombre del usuario"
                value="{{ old('name') }}" />

            <x-wire-input label="Correo electrónico" name="email" type="email" required
                placeholder="Ingrese el correo electrónico del usuario" value="{{ old('email') }}" />

            <x-wire-input label="Contraseña" name="password" type="password" required
                placeholder="Ingrese la contraseña del usuario" value="{{ old('password') }}" />

            <x-wire-input label="Confirmar contraseña" name="password_confirmation" type="password" required
                placeholder="Confirme la contraseña del usuario" value="{{ old('password_confirmation') }}" />
        </div>

        <!-- Selector de Compañías -->
        <div class="mt-4">
            <div class="mb-4">
                <x-wire-select label="Compañías" placeholder="Selecciona una o más compañías" multiselect
                    name="companies[]" :options="$companies" option-label="trade_name" option-value="id" required />
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-4">
                <x-wire-select label="Roles" placeholder="Selecciona uno o más roles" multiselect name="roles[]"
                    :options="$roles" option-label="name" option-value="id" />
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <x-wire-button type="submit" blue>
                Crear
            </x-wire-button>
        </div>
    </form>
</x-admin-layout>