@props(['title' => 'Masuk'])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5B2A6E">

    <title>{{ $title }} — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-frost font-sans text-ink antialiased">

    <div class="flex min-h-full flex-col justify-center px-4 py-10 sm:px-6">
        <div class="mx-auto w-full max-w-sm">

            <div class="mb-7 text-center">
                <a href="{{ route('login') }}" class="inline-block" aria-label="{{ config('app.name') }}">
                    <x-app-logo class="mx-auto h-16 w-16" />
                </a>
                <h1 class="mt-4 text-xl font-extrabold text-berry">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-ink/55">Sistem keuangan titip jual es krim</p>
            </div>

            <div class="rounded-2xl border border-berry/10 bg-white p-6 shadow-sm">
                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-ink/40">
                Akun dibuat oleh pemilik usaha. Hubungi pemilik bila lupa kata sandi.
            </p>
        </div>
    </div>

</body>
</html>
