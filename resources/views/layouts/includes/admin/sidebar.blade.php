@php
    $menuItems = [
        [
            'name' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],

        ['header' => 'Productos'],
        [
            'name' => 'Categorías',
            'icon' => 'fa-solid fa-box',
            'href' => route('admin.categories.index'),
            'active' => request()->routeIs('admin.categories.*'),
        ],
        [
            'name' => 'Productos',
            'icon' => 'fa-solid fa-list',
            'href' => '#',
            'active' => request()->routeIs('admin.products.*'),
            'submenu' => [
                ['name' => 'Productos', 'href' => route('admin.products.index'), 'active' => false],
                ['name' => 'Variantes', 'href' => route('admin.variants.index'), 'active' => false],
            ],
        ],
        [
            'name' => 'Clientes',
            'icon' => 'fa-solid fa-user',
            'href' => route('admin.customers.index'),
            'active' => request()->routeIs('admin.customers.*'),
        ],
        [
            'name' => 'Proveedores',
            'icon' => 'fa-solid fa-truck',
            'href' => route('admin.suppliers.index'),
            'active' => request()->routeIs('admin.suppliers.*'),
        ],
        [
            'name' => 'Almacenes',
            'icon' => 'fa-solid fa-warehouse',
            'href' => route('admin.warehouses.index'),
            'active' => request()->routeIs('admin.warehouses.*'),
        ],

    ];
@endphp


<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">
            @foreach ($menuItems as $index => $item)
                @if (isset($item['header']))
                    <li class="pt-4 first:pt-0">
                        <div class="px-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $item['header'] }}
                        </div>
                    </li>
                @else
                    @php
                        $hasSubmenu =
                            isset($item['submenu']) && is_array($item['submenu']) && count($item['submenu']) > 0;
                        $dropdownId = 'dropdown-' . $index;
                    @endphp

                    <li>
                        @if ($hasSubmenu)
                            <button type="button"
                                class="flex items-center w-full p-2 text-base text-left text-gray-900 rounded-lg group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 {{ $item['active'] ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                aria-controls="{{ $dropdownId }}" data-collapse-toggle="{{ $dropdownId }}">
                                <span class="inline-flex justify-center items-center w-6 h-6 text-gray-500">
                                    <i class="{{ $item['icon'] ?? 'fa-regular fa-circle' }}"></i>
                                </span>
                                <span class="flex-1 ms-3 whitespace-nowrap">{{ $item['name'] }}</span>
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </button>
                            <ul id="{{ $dropdownId }}" class="hidden py-2 space-y-1">
                                @foreach ($item['submenu'] as $child)
                                    <li>
                                        <a href="{{ $child['href'] }}" @class([
                                            'flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
                                            'bg-gray-100 dark:bg-gray-700' => $child['active'] ?? false,
                                        ])>
                                            {{ $child['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ $item['href'] }}" @class([
                                'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                                'bg-gray-100 dark:bg-gray-700' => $item['active'],
                            ])>
                                <span class="inline-flex justify-center items-center w-6 h-6 text-gray-500">
                                    <i class="{{ $item['icon'] ?? 'fa-regular fa-circle' }}"></i>
                                </span>
                                <span class="ms-3">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</aside>
