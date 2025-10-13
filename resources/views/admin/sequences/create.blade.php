<x-admin-layout title="Secuencias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Secuencias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.sequences.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

     <x-wire-card>

         <form action="{{ route('admin.sequences.store') }}" method="post" class="space-y-4">

             @csrf
             <x-wire-input label="Tamaño de la secuencia" name="sequence_size" placeholder="e.g. 5" type="number" min="1" />
             <x-wire-input label="Paso" name="step" type="number" value="1" />
             <x-wire-input label="Siguiente número" name="next_number" type="number" value="1" />
             <div class="flex justify-end">
                 <x-wire-button type="submit" green label="Guardar" />
             </div>

         </form>

     </x-wire-card>

 </x-admin-layout>
