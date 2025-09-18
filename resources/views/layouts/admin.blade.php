@props([
    'title' => config('app.name', 'Laravel'),
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- switalert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- fontawesome --}}
    <script src="https://kit.fontawesome.com/da8c4aaac7.js" crossorigin="anonymous"></script>

    <!-- Styles -->
    @livewireStyles

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- wireui --}}
    <wireui:scripts />

    @stack('css')
</head>

<body class="font-sans antialiased">
    @include('layouts.includes.admin.navigation')
    @include('layouts.includes.admin.sidebar')

    <div class="p-4 sm:ml-64">
        <div class="mt-14">

            <div class="mt-14 flex items-center">
                @include('layouts.includes.admin.breadcrumb')

                @isset($action)
                    <div class="ml-auto">
                        {{ $action }}
                    </div>
                @endisset

            </div>
        </div>
        {{ $slot }}
    </div>

    @stack('modals')

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>

    <script>
        Livewire.on('swal', (data) => {
            Swal.fire(data[0]);
        });
    </script>


    @if (session()->has('swalt'))
        <script>
            Swal.fire(@json(session('swalt')));
        </script>
    @endif

    @stack('js')

</body>

</html>
