<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ZoneTools') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#3D63DD">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-gray-900 antialiased">
        <div class="flex min-h-full">
            {{-- Panneau de marque --}}
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-primary-600 p-12 text-white lg:flex">
                <x-logo-complet class="h-auto w-56 text-white" />
                <div class="relative z-10 max-w-md">
                    <h2 class="text-3xl font-bold leading-tight">Pilotez tous vos hotspots depuis un seul endroit.</h2>
                    <p class="mt-4 text-primary-100">Lots de tickets, rapports des agents, commissions et versements : tout est suivi automatiquement.</p>
                </div>
                <p class="text-sm text-primary-200">© {{ date('Y') }} {{ config('app.name') }}</p>
                <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -top-24 right-24 h-64 w-64 rounded-full bg-white/5"></div>
            </div>

            {{-- Formulaire --}}
            <div class="flex flex-1 items-center justify-center bg-gray-50 px-6 py-12">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo class="h-10 w-auto text-primary-600" />
                        <x-logo-texte class="h-6 w-auto text-gray-900" />
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
