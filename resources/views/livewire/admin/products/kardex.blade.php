<div>

    <x-wire-alert title="{{ $variant->product->name }}" info class="mb-6">
        <x-slot name="slot" class="italic">
            <p>
                <span>SKU:</span>
                {{ $variant->sku }}
            </p>
            <p>
                <span>Barcode:</span>
                {{ $variant->barcode }}
            </p>
        </x-slot>
    </x-wire-alert>

    <x-wire-card class="mb-12">
        <div class="grid grid-cols=2">
            <x-wire-input label="Fecha Inicial" type="date" wire:model.live="fecha_inicial"/>
            <x-wire-input label="Fecha Final" type="date" wire:model.live="fecha_final"/>

            <x-wire-select label="Almacen" wire:model.live="warehouse_id" 
            :options="$warehouses->select('id', 'name')"
            option-label="name"
            option-value="id"
            />
        </div>
    </x-wire-card>

    <h2 class="text-2xl font-bold mb-4">Kardex de Productos</h2>

    @if($inventories->count())

        <div class="overflow-x-auto rounded-md border border-gray-200 shadow-md">
            <table class="min-w-full bg-white text-gray-800">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-center bg-gray-100 text-gray-700" rowspan="2">Detalle</th>
                        <th class="px-4 py-2 text-center bg-green-100 text-green-800" colspan="3">Entradas</th>
                        <th class="px-4 py-2 text-center bg-red-100 text-red-800" colspan="3">Salidas</th>
                        <th class="px-4 py-2 text-center bg-blue-100 text-blue-800" colspan="3">balance</th>
                        <th class="px-4 py-2 text-center bg-gray-100 text-gray-700" rowspan="2">Fecha</th>
                    </tr>
                    <tr class="text-gray-700">
                        <th class="px-2 py-1 text-center bg-green-50">Cant.</th>
                        <th class="px-2 py-1 text-center bg-green-50">Costo</th>
                        <th class="px-2 py-1 text-center bg-green-50">Total</th>
                        <th class="px-2 py-1 text-center bg-red-50">Cant.</th>
                        <th class="px-2 py-1 text-center bg-red-50">Costo</th>
                        <th class="px-2 py-1 text-center bg-red-50">Total</th>
                        <th class="px-2 py-1 text-center bg-blue-50">Cant.</th>
                        <th class="px-2 py-1 text-center bg-blue-50">Costo</th>
                        <th class="px-2 py-1 text-center bg-blue-50">Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($inventories as $inventory)
                        <tr class="text-gray-700">
                            <td class="px-2 py-2 text-center">{{ $inventory->detail }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->quantity_in }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->cost_in }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->total_in }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->quantity_out }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->cost_out }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->total_out }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->quantity_balance }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->cost_balance }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->total_balance }}</td>
                            <td class="px-2 py-2 text-center">{{ $inventory->created_at->format('Y-M-D') }}</td>
                    @endforeach
                </tbody>
            </table>
            
        </div>

        <div class="mt-4">
            {{ $inventories->links() }}
        </div>
    @else
       <x-wire-card >
           <p class="text-lg font-semibold text-center">No se encontraron resultados</p>
           <p class="text-sm text-gray-500 text-center">Todavia no se han registrado movimientos de productos</p>
       </x-wire-card>
    @endif

</div>
