<x-wire-modal-card title="Stock por almacen" wire:model="openModal">
    <ul class="space-y-3">
        @forelse ($inventories as $inventory)
            <li class="flex items-center justify-between p-4 bg-gray-10 rounded-lg shadow-sm">

                <div>
                    <p class="text-sm text-gray-600">{{ $inventory->warehouse->name }}</p>
                    <p class="text-sm text-gray-600">{{ $inventory->warehouse->location }}</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-800">
                        {{ $inventory->quantity_balance }}
                    </p>
                </div>

            </li>
        @empty
            <li class="flex items-center justify-between p-4 bg-gray-10 rounded-lg shadow-sm">
                <div>
                    <p class="text-sm text-gray-600">No hay stock</p>
                </div>
            </li>
        @endforelse
    </ul>
</x-wire-modal-card>
