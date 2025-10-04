<?php
$links = [
    [
        'type' => 'header',
        'title' => 'Principal'
    ],
    [
        'type' => 'link',
        'title' => 'Dashboard',
        'icon' => 'fa-solid fa-gauge',
        'route' => 'admin.dashboard',
        'active' => 'admin.dashboard',
    ],
    [
        'type' => 'group',
        'title' => 'Inventario',
        'icon' => 'fa-solid fa-boxes-stacked',
        'active' => [
            'admin.categories.*',
            'admin.products.*',
            'admin.variants.*',
            'admin.warehouses.*',
            'admin.attributes.*',
        ],
        'items' => [
            [
                'type' => 'link',
                'title' => 'Categorías',
                'route' => 'admin.categories.index',
                'active' => 'admin.categories.*',
            ],
            [
                'type' => 'link',
                'title' => 'Productos',
                'route' => 'admin.products.index',
                'active' => 'admin.products.*',
            ],
            [
                'type' => 'link',
                'title' => 'Variantes',
                'route' => 'admin.variants.index',
                'active' => 'admin.variants.*',
            ],
            [
                'type' => 'link',
                'title' => 'Almacenes',
                'route' => 'admin.warehouses.index',
                'active' => 'admin.warehouses.*',
            ],
            [
                'type' => 'link',
                'title' => 'Atributos',
                'route' => 'admin.attributes.index',
                'active' => 'admin.attributes.*',
            ],
        ],
    ],

    [
        'type' => 'group',
        'title' => 'Compras',
        'icon' => 'fa-solid fa-cart-shopping',
        'route' => '#',
        'active' => ['admin.suppliers.*', 'admin.purchases-orders.*', 'admin.purchases.*'],
        'items' => [
            [
                'type' => 'link',
                'title' => 'Proveedores',
                'route' => 'admin.suppliers.index',
                'active' => 'admin.suppliers.*',
            ],
            [
                'type' => 'link',
                'title' => 'Ordenes de compra',
                'route' => 'admin.purchases-orders.index',
                'active' => 'admin.purchases-orders.*',
            ],
            [
                'type' => 'link',
                'title' => 'Compras',
                'route' => 'admin.purchases.index',
                'active' => 'admin.purchases.*',
            ],
        ],
    ],
    [
        'type' => 'group',
        'title' => 'Ventas',
        'icon' => 'fa-solid fa-cash-register',
        'active' => ['admin.customers.*', 'admin.quotes.*', 'admin.sales.*'],
        'items' => [
            [
                'type' => 'link',
                'title' => 'Clientes',
                'route' => 'admin.customers.index',
                'active' => 'admin.customers.*',
            ],
            [
                'type' => 'link',
                'title' => 'Cotizaciones',
                'route' => 'admin.quotes.index',
                'active' => 'admin.quotes.*',
            ],
            [
                'type' => 'link',
                'title' => 'Ventas',
                'route' => 'admin.sales.index',
                'active' => 'admin.sales.*',
            ],
        ],
    ],
    [
        'type' => 'group',
        'title' => 'Movimientos',
        'icon' => 'fa-solid fa-arrows-rotate',
        'route' => '#',
        'active' => ['admin.movements.*', 'admin.transfers.*'],
        'items' => [
            [
                'type' => 'link',
                'title' => 'Movimientos',
                'route' => 'admin.movements.index',
                'active' => 'admin.movements.*',
            ],
            [
                'type' => 'link',
                'title' => 'Transferencias',
                'route' => 'admin.transfers.index',
                'active' => 'admin.transfers.*',
            ],
        ],
    ],
    [
        'type' => 'group',
        'title' => 'Reportes',
        'icon' => 'fa-solid fa-chart-line',

    ],
    [
        'type' => 'header',
        'title' => 'Configuración',
    ],
    [
        'type' => 'group',
        'title' => 'Configuración',
        'icon' => 'fa-solid fa-gear',
        'route' => '#',
        'active' =>  ['admin.companies.*'],
        'items' => [
            [
                'type' => 'link',
                'title' => 'Empresas',
                'route' => 'admin.companies.index',
                'active' => 'admin.companies.*',
            ],
            [
                'type' => 'link',
                'title' => 'Transferencias',

            ],
        ],
    ],
    [
        'type' => 'link',
        'title' => 'Usuarios',
        'icon' => 'fa-solid fa-user',
        'route' => 'admin.users.index',
        'active' => 'admin.users.*',
    ],
    [
        'type' => 'link',
        'title' => 'Roles',
        'icon' => 'fa-solid fa-users-gear',

    ],
    [
        'type' => 'link',
        'title' => 'Permisos',
        'icon' => 'fa-solid fa-shield',

    ],
    [
        'type' => 'link',
        'title' => 'Ajustes',
        'icon' => 'fa-solid fa-gear',

    ],
];
return $links;
