<div class="flex items-center space-x-4">
    <x-wire-button green wire:click="openModal({{ $purchaseOrder->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    <x-wire-button blue href="{{ route('admin.purchases-orders.pdf', $purchaseOrder) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
</div>
