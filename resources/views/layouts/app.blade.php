<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name', 'ZoneTools') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#3D63DD">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-gray-900" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

        {{-- Fond sombre derrière la sidebar sur mobile --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity
             class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full transform bg-primary-600 transition-transform duration-200 lg:translate-x-0"
               :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
            @include('layouts.navigation')
        </aside>

        <div class="lg:ps-64 min-h-full flex flex-col">
            {{-- Barre du haut --}}
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" class="-m-2 p-2 text-gray-600 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Ouvrir le menu</span>
                    <x-icon name="menu" class="h-6 w-6" />
                </button>

                <div class="flex-1 min-w-0">
                    @isset($header)
                        <h1 class="truncate text-lg font-semibold text-gray-900">{{ $header }}</h1>
                    @endisset
                </div>

                @isset($actions)
                    <div class="flex items-center gap-2">{{ $actions }}</div>
                @endisset

                {{-- Menu utilisateur --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700">
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden sm:block font-medium">{{ Auth::user()->name }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Déconnexion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <x-flash />
                {{ $slot }}
            </main>

            <footer class="px-4 py-4 text-xs text-gray-400 sm:px-6 lg:px-8">
                © {{ date('Y') }} {{ config('app.name') }} — Gestion des hotspots
            </footer>
        </div>
    </body>
</html>
