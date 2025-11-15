<div class="flex items-center space-x-4">

    @can('update_purchases', $purchase)
    <x-wire-button blue href="{{ route('admin.purchases.edit', $purchase) }}">
        <i class="fa-solid fa-edit"></i>
    </x-wire-button>
    @endcan

    @can('read_purchases', $purchase)
    <x-wire-button green wire:click="openModal({{ $purchase->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    @endcan

    @can('export_pdf_purchases', $purchase)
    <x-wire-button blue href="{{ route('admin.purchases.pdf', $purchase) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
    @endcan
</div>
