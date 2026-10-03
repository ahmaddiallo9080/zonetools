<x-app-layout>
    <x-slot name="title">Modifier {{ $superviseur->nom_complet }}</x-slot>
    <x-slot name="header">Modifier le superviseur</x-slot>

    <nav class="mb-4 text-sm text-gray-500">
        <a href="{{ route('superviseurs.index') }}" class="hover:text-primary-600">Superviseurs</a> /
        <a href="{{ route('superviseurs.show', $superviseur) }}" class="hover:text-primary-600">{{ $superviseur->nom_complet }}</a> /
        <span class="text-gray-700">Modifier</span>
    </nav>

    <form method="POST" action="{{ route('superviseurs.update', $superviseur) }}">
        @csrf
        @method('PUT')
        @include('superviseurs._form')
    </form>
</x-app-layout>
