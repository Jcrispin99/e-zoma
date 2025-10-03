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
        'name' => 'Editar Usuario',
    ]
]">
   
  <x-wire-card>
    <h1 class="text-2xl font-semibold mb-4">
        Nuevo Usuario
    </h1>
   </x-wire-card>

   <form action=" {{ route('admin.users.update', $user) }}" method="POST">

    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-2 gap-4">
        <x-wire-input label="Nombre" name="name" required placeholder="Ingrese el nombre del usuario" value="{{ old('name', $user->name) }}"/>
   
        <x-wire-input label="Correo electrónico" name="email" type="email" required placeholder="Ingrese el correo electrónico del usuario" value="{{ old('email', $user->email) }}"/>
    
        <x-wire-input label="Contraseña" name="password" type="password" placeholder="Ingrese la contraseña del usuario" value="{{ old('password') }}"/>

        <x-wire-input label="Confirmar contraseña" name="password_confirmation" type="password" placeholder="Confirme la contraseña del usuario" value="{{ old('password_confirmation') }}"/>
    </div>
    <div class="flex justify-end mt-4">
        <x-wire-button type="submit" blue>
            Actualizar
        </x-wire-button>
    </div>
    </form>
</x-admin-layout>
