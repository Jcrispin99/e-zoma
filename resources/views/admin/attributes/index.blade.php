<x-admin-layout title="Atributos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Atributos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.attributes.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.attributes.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.attribute-table')

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('.delete-form');
                forms.forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const currentForm = this;

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: 'No podrás revertir esto',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Enviar el formulario
                                currentForm.submit();

                                // No mostramos el mensaje de éxito aquí para evitar confusiones
                                // ya que el formulario se enviará y la página se recargará
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
