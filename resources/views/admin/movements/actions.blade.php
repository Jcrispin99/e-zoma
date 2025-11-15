<div class="flex items-center space-x-4">
    @can('read_movements', $movement)
    <x-wire-button green wire:click="openModal({{ $movement->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    @endcan

    @can('export_pdf_movements', $movement)
    <x-wire-button blue href="{{ route('admin.movements.pdf', $movement) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
    @endcan
</div>
