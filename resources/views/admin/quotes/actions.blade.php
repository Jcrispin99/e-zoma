<div class="flex items-center space-x-4">
    @can('update_quotes', $quote)
    <x-wire-button href="{{ route('admin.quotes.edit', $quote) }}" blue xs>
        Editar
    </x-wire-button>
    @endcan

    @can('read_quotes', $quote)
    <x-wire-button green wire:click="openModal({{ $quote->id }})">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    @endcan

    @can('export_pdf_quotes', $quote)
    <x-wire-button blue href="{{ route('admin.quotes.pdf', $quote) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
    @endcan

</div>
