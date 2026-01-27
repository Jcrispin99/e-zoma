<div class="flex items-center space-x-4">
    @can('update_purchase-orders', $purchaseOrder)
    <x-wire-button href="{{ route('admin.purchases-orders.edit', $purchaseOrder) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('read_purchase-orders', $purchaseOrder)
    <x-wire-button green wire:click="openModal({{ $purchaseOrder->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    @endcan

    @can('export_pdf_purchase-orders', $purchaseOrder)
    <x-wire-button blue href="{{ route('admin.purchases-orders.pdf', $purchaseOrder) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
    @endcan

    @can('read_purchase-orders', $purchaseOrder)
    <x-wire-button dark href="{{ route('admin.qr.labels', ['type' => 'purchase-order', 'id' => $purchaseOrder->id]) }}">
        <i class="fa-solid fa-qrcode"></i>
    </x-wire-button>
    @endcan
</div>
