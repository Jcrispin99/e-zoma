@php
    // Datos de prueba organizados por secciones con menús y submenús
    $sections = [
        [
            'title' => 'Administrar página',
            'links' => [
                [
                    'name' => 'Dashboard',
                    'icon' => 'fa-solid fa-gauge',
                    'href' => route('admin.dashboard'),
                    'active' => request()->routeIs('admin.dashboard'),
                ],
                [
                    'name' => 'Contenido',
                    'icon' => 'fa-regular fa-file-lines',
                    'href' => '#',
                    'active' => false,
                    'submenu' => [
                        [ 'name' => 'Páginas', 'href' => '#', 'active' => false ],
                        [ 'name' => 'Entradas', 'href' => '#', 'active' => false ],
                        [ 'name' => 'Medios',   'href' => '#', 'active' => false ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Tienda (demo)',
            'links' => [
                [
                    'name' => 'E-commerce',
                    'icon' => 'fa-solid fa-bag-shopping',
                    'href' => '#',
                    'active' => false,
                    'submenu' => [
                        [ 'name' => 'Productos',   'href' => '#', 'active' => false ],
                        [ 'name' => 'Facturación', 'href' => '#', 'active' => false ],
                        [ 'name' => 'Pedidos',     'href' => '#', 'active' => false ],
                    ],
                ],
            ],
        ],
    ];
@endphp


<aside id="logo-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar">
   <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
      <ul class="space-y-4 font-medium">
         @foreach ($sections as $sIndex => $section)
            <li>
               <div class="px-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  {{ $section['title'] }}
               </div>
               <ul class="mt-2 space-y-1">
                  @foreach ($section['links'] as $lIndex => $link)
                     @php
                        $hasSubmenu = isset($link['submenu']) && is_array($link['submenu']) && count($link['submenu']) > 0;
                        $dropdownId = 'dropdown-' . $sIndex . '-' . $lIndex;
                     @endphp

                     <li>
                        @if ($hasSubmenu)
                           <button type="button"
                                   class="flex items-center w-full p-2 text-left text-gray-900 rounded-lg group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 {{ $link['active'] ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                                   aria-controls="{{ $dropdownId }}"
                                   data-collapse-toggle="{{ $dropdownId }}">
                              <span class="inline-flex justify-center items-center w-6 h-6 text-gray-500">
                                 <i class="{{ $link['icon'] ?? 'fa-regular fa-circle' }}"></i>
                              </span>
                              <span class="flex-1 ms-3 whitespace-nowrap">{{ $link['name'] }}</span>
                              <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                 <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                              </svg>
                           </button>
                           <ul id="{{ $dropdownId }}" class="hidden py-2 space-y-1">
                              @foreach ($link['submenu'] as $child)
                                 <li>
                                    <a href="{{ $child['href'] }}" @class([
                                       'flex items-center w-full p-2 text-gray-900 rounded-lg pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
                                       'bg-gray-100 dark:bg-gray-700' => $child['active'] ?? false,
                                    ])>
                                       {{ $child['name'] }}
                                    </a>
                                 </li>
                              @endforeach
                           </ul>
                        @else
                           <a href="{{ $link['href'] }}" @class([
                              'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                              'bg-gray-100 dark:bg-gray-700' => $link['active'],
                           ])>
                              <span class="inline-flex justify-center items-center w-6 h-6 text-gray-500">
                                 <i class="{{ $link['icon'] ?? 'fa-regular fa-circle' }}"></i>
                              </span>
                              <span class="ms-3">{{ $link['name'] }}</span>
                           </a>
                        @endif
                     </li>
                  @endforeach
               </ul>
            </li>
         @endforeach
      </ul>
   </div>
</aside>
