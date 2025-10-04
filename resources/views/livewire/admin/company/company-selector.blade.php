<div x-data="{ open: false }">
    {{-- Botón para abrir/cerrar el selector --}}
    <button @click="open = !open" class="py-2 px-4 border rounded-md">
        <span>Seleccionar Compañía</span>
        <i class="fa-solid fa-chevron-down ml-2"></i>
    </button>

    {{-- Dropdown con la lista de compañías --}}
    <div x-show="open" @click.away="open = false" class="absolute mt-2 w-64 bg-white border rounded-md shadow-lg z-50">
        <ul class="p-3">
            {{-- Iteramos sobre las compañías padre --}}
            @foreach ($allCompanies as $company)
                <li class="mb-2" x-data="{ openChildren: true }">
                    <div>
                        <input type="checkbox" wire:model.live="selectedCompanies" value="{{ $company->id }}" id="company_{{ $company->id }}" class="mr-2">
                        <label for="company_{{ $company->id }}">{{ $company->name }}</label>
                        
                        {{-- Si tiene sucursales, mostramos un botón para expandir/colapsar --}}
                        @if ($company->children->isNotEmpty())
                            <button @click="openChildren = !openChildren" class="ml-2 text-xs text-gray-500">
                                <i class="fa-solid" :class="openChildren ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Lista de sucursales (hijas) --}}
                    @if ($company->children->isNotEmpty())
                        <ul class="ml-6 mt-1" x-show="openChildren">
                            @foreach ($company->children as $child)
                                <li class="mb-1">
                                    <input type="checkbox" wire:model.live="selectedCompanies" value="{{ $child->id }}" id="company_{{ $child->id }}" class="mr-2">
                                    <label for="company_{{ $child->id }}">{{ $child->name }}</label>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>