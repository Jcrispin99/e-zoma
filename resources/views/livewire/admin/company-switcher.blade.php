<div class="ms-3 relative">
    <x-dropdown align="right" width="60">
        <x-slot name="trigger">
            <span class="inline-flex rounded-md">
                <button type="button"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                    {{ $currentCompany?->comercial_name ?? 'Seleccionar Compañía' }}

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
                    <div class="p-2">
                        <table class="w-full">
                            <tbody>
                                @foreach ($companies as $company)
                                    <tr wire:click="switchCompany({{ $company->id }})"
                                        class="cursor-pointer hover:bg-gray-50 transition duration-150 ease-in-out {{ $company->id == $selectedCompanyId ? 'bg-gray-100' : '' }}">
                                        <td class="py-2 {{ $company->isSubsidiary() ? 'pl-8' : 'pl-4' }}">
                                            <div class="flex items-center">
                                                <input type="checkbox"
                                                    {{ $company->id == $selectedCompanyId ? 'checked' : '' }}
                                                    class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded pointer-events-none"
                                                    readonly>
                                                @if ($company->isSubsidiary())
                                                    <span class="text-gray-400 mr-2">└─</span>
                                                @endif
                                                <span
                                                    class="text-sm text-gray-700 {{ $company->id == $selectedCompanyId ? 'font-semibold' : '' }}">
                                                    {{ $company->comercial_name }}
                                                </span>
                                            </div>
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
