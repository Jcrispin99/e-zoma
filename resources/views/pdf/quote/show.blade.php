{{-- Vista única para mostrar/descargar la Cotización (admin y minimal) --}}
@php($quote = $quote ?? $model ?? null)
@php($useLayout = isset($useLayout) ? (bool) $useLayout : auth()->check())
@php($isPublic = (bool) ($isPublic ?? false))

@if($useLayout)
<x-admin-layout title="Cotización" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Cotizaciones',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.quotes.index'),
    ],
    [
        'name' => 'Vista PDF',
    ],
]">

    <x-wire-card class="space-y-4">
        <div class="flex items-center justify-between">
            <div></div>
            <div class="flex items-center gap-2">
                <x-wire-button blue href="{{ route('admin.quotes.pdf', $quote) }}">
                    <i class="fa-solid fa-file-pdf mr-1"></i>
                    Descargar PDF
                </x-wire-button>
                <x-wire-button teal :href="URL::signedRoute('public.quotes.pdf.view', ['quote' => $quote])">
                    Ver público
                </x-wire-button>
                <x-wire-button light gray :href="route('admin.quotes.edit', $quote)">
                    Volver a editar
                </x-wire-button>
            </div>
        </div>
        @include('pdf._styles')
        @include('pdf._document_generic', ['model' => $quote, 'documentLabel' => 'Cotización'])

    </x-wire-card>

</x-admin-layout>
@else
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $quote->serie }}-{{ str_pad($quote->correlative, 4, '0', STR_PAD_LEFT) }}</title>
    @if($isPublic)
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @include('pdf._styles')
    </head>

<body class="@if($isPublic) bg-gray-100 @endif">
    <div class="@if($isPublic) max-w-4xl mx-auto p-4 @endif">
        @if($isPublic)
        <div class="flex items-center justify-between mb-4">
            <div></div>
            <div class="flex items-center gap-2">
                <a class="inline-flex items-center px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700"
                    href="{{ route('admin.quotes.pdf', $quote) }}">
                    <span class="mr-1">Descargar PDF</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v2.25a2.25 2.25 0 01-2.25 2.25h-10.5A2.25 2.25 0 014.5 16.5v-2.25m9 0l-3 3m0 0l-3-3m3 3V6.75" />
                    </svg>
                </a>
            </div>
        </div>
        @endif
        @include('pdf._document_generic', ['model' => $quote, 'documentLabel' => 'Cotización'])

        @if($isPublic)
        <div class="mt-4 text-center text-xs text-gray-500">
            <p>Este enlace es de solo lectura. Si requiere cambios, contacte al emisor.</p>
        </div>
        @endif
    </div>
</body>

</html>
@endif