<x-app-layout>
    <x-slot name="title">Nouveau site</x-slot>
    <x-slot name="header">Nouveau site</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('sites.index') }}" class="hover:text-primary-600">Sites</a> / <span class="text-gray-700">Nouveau</span>
    </nav>

    <form method="POST" action="{{ route('sites.store') }}">
        @csrf
        @include('sites._form')
    </form>
</x-app-layout>
