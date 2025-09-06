<x-admin-layout>
    <x-slot name="title">Prueba WireUI</x-slot>
    
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Prueba de Componentes WireUI</h1>
        
        <div class="space-y-6">
            <!-- Botones WireUI -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Botones</h2>
                <div class="space-x-2">
                    <x-button label="Botón Primario" />
                    <x-button label="Botón Secundario" secondary />
                    <x-button label="Botón Positivo" positive />
                    <x-button label="Botón Negativo" negative />
                </div>
            </div>
            
            <!-- Inputs WireUI -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Inputs</h2>
                <div class="space-y-3 max-w-md">
                    <x-input label="Nombre" placeholder="Ingresa tu nombre" />
                    <x-input label="Email" placeholder="tu@email.com" type="email" />
                    <x-textarea label="Mensaje" placeholder="Escribe tu mensaje aquí..." />
                </div>
            </div>
            
            <!-- Select WireUI -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Select</h2>
                <div class="max-w-md">
                    <x-select
                        label="Selecciona una opción"
                        placeholder="Elige una opción"
                        :options="[
                            ['name' => 'Opción 1', 'id' => 1],
                            ['name' => 'Opción 2', 'id' => 2],
                            ['name' => 'Opción 3', 'id' => 3],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>
            </div>
            
            <!-- Toggle WireUI -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Toggle</h2>
                <x-toggle label="Activar notificaciones" />
            </div>
            
            <!-- Card WireUI -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Card</h2>
                <x-card title="Título de la tarjeta">
                    <p class="text-gray-600">
                        Este es el contenido de una tarjeta de WireUI. 
                        Si puedes ver este contenido con estilos correctos, 
                        significa que WireUI está funcionando correctamente.
                    </p>
                    
                    <x-slot name="footer">
                        <div class="flex justify-end">
                            <x-button label="Acción" />
                        </div>
                    </x-slot>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>