<div class="flex items-center space-x-2">
    <x-wire-button href="{{ route('admin.variants.kardex', $variant) }}" green xs>
        <i class="fas fa-boxes-stacked"></i>
    </x-wire-button>

  <x-wire-button href="{{ route('admin.variants.edit', $variant) }}" blue xs>
        <i class="fas fa-edit"></i>
    </x-wire-button>

    <form action="{{ route('admin.variants.destroy', $variant) }}" method="post" class="delete-form">
        @csrf
        @method('delete')
        <x-wire-button type="submit" red xs>
            <i class="fas fa-trash-alt"></i>
        </x-wire-button>
    </form>
</div>
