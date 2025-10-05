<div class="ms-3 relative" wire:ignore.self>
    <x-dropdown align="right" width="60">
        <x-slot name="trigger">
            <span class="inline-flex rounded-md">
                <button type="button"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                    {{ $mainCompanyName }} 
                    @if (count($selectedCompanyIds) > 1)
                        <span class="ml-1 text-xs text-gray-400">(+{{ count($selectedCompanyIds) - 1 }})</span>
                    @endif
                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                    </svg>
                </button>
            </span>
        </x-slot>
        <x-slot name="content">
            <div class="w-60">
                <!-- Company Switcher Table -->
                @if ($companies->count() > 0)
                    <div class="p-1">
                        <table class="w-full border-collapse">
                            <tbody>
                                @foreach ($companies as $company)
                                    @php
                                        $isSelected = $this->isSelected($company->id);
                                        $isActive = $this->isActive($company->id);
                                        $isLastSelected = count($selectedCompanyIds) === 1 && $isSelected;
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out 
                                        {{ $isActive ? 'bg-blue-100' : ($isSelected ? 'bg-gray-100' : '') }}">
                                        <td class="py-2 pl-4 pr-2 border-r border-gray-300 w-10">
                                            <input type="checkbox"
                                                {{ $isSelected ? 'checked' : '' }}
                                                {{ $isLastSelected ? 'disabled' : '' }}
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded 
                                                    {{ $isLastSelected ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
                                                wire:click.stop="toggleCompany({{ $company->id }})"
                                                title="{{ $isLastSelected ? 'Debe mantener al menos una compañía seleccionada' : '' }}">
                                        </td>
                                        <td class="py-2 cursor-pointer {{ $company->isSubsidiary() ? 'pl-2' : 'pl-4' }}"
                                            wire:click="setActiveCompany({{ $company->id }})">
                                            <span class="flex items-center">
                                                @if ($company->isSubsidiary())
                                                    <span class="text-gray-400 mr-2">└─</span>
                                                @endif
                                                <span class="text-sm {{ $isActive ? 'text-blue-700 font-bold' : ($isSelected ? 'text-gray-700 font-semibold' : 'text-gray-700') }}">
                                                    {{ $company->trade_name }}
                                                </span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="block px-4 py-2 text-sm text-gray-500">
                        No hay compañías disponibles
                    </div>
                @endif
            </div>
        </x-slot>
    </x-dropdown>
</div>