<div class="flex items-center space-x-4">

    @can('update_sales', $sale)
    <x-wire-button href="{{ route('admin.sales.edit', $sale) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('read_sales', $sale)
    <x-wire-button green wire:click="openModal({{ $sale->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    @endcan

    @can('export_pdf_sales', $sale)
    <x-wire-button blue href="{{ route('admin.sales.pdf', $sale) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
    @endcan
</div>
