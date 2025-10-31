<div class="flex items-center space-x-4">

    <x-wire-button blue href="{{ route('admin.purchases.edit', $purchase) }}">
        <i class="fa-solid fa-edit"></i>
    </x-wire-button>

    <x-wire-button green wire:click="openModal({{ $purchase->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    <x-wire-button blue href="{{ route('admin.purchases.pdf', $purchase) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
</div>
