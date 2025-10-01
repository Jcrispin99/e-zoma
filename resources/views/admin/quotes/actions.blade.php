<div class="flex items-center space-x-4">
      <x-wire-button gren href="">
        <i class="fa-solid fa-envelope"></i>
    </x-wire-button>
    <x-wire-button blue href="{{ route('admin.quotes.pdf', $quote) }}">
        <i class="fa-solid fa-file-pdf"></i>
    </x-wire-button>
</div>
