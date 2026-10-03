<x-app-layout>
    <x-slot name="title">Modifier {{ $site->nom }}</x-slot>
    <x-slot name="header">Modifier le site</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('sites.index') }}" class="hover:text-primary-600">Sites</a> /
        <a href="{{ route('sites.show', $site) }}" class="hover:text-primary-600">{{ $site->nom }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('sites.update', $site) }}">
        @csrf
        @method('PUT')
        @include('sites._form')
    </form>
</x-app-layout>
