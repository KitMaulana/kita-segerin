@props(['title' => null, 'subtitle' => null])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5B2A6E">
    <meta name="versi-aset" content="{{ versi_aset() }}">

    {{-- Pemasangan aplikasi (PWA): public/manifest.webmanifest & public/sw.js --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Segerin">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-frost font-sans text-ink antialiased">

    <a href="#konten-utama"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-berry focus:px-4 focus:py-2 focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    <div class="min-h-full lg:flex">

        @include('layouts.partials.sidebar')

        <div class="flex min-h-full min-w-0 flex-1 flex-col">

            @include('layouts.partials.topbar', ['title' => $title, 'subtitle' => $subtitle])

            <main id="konten-utama" class="flex-1 px-4 pb-28 pt-5 sm:px-6 lg:px-8 lg:pb-10">
                @include('layouts.partials.flash')

                @isset($header)
                    <div class="mb-5">{{ $header }}</div>
                @endisset

                {{ $slot }}
            </main>

            <footer class="hidden border-t border-berry/10 px-8 py-5 text-xs text-ink/45 lg:block">
                {{ config('app.name') }} &middot; Sistem keuangan titip jual es krim
            </footer>
        </div>
    </div>

    @include('layouts.partials.bottom-nav')

    @stack('scripts')
</body>
</html>
